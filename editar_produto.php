<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Função para carregar as imagens associadas ao produto
function get_imagens($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM imagens WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Função para carregar categorias associadas ao produto
function get_categorias($produto_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT c.id, c.nome 
        FROM categorias c 
        JOIN produto_categoria pc ON c.id = pc.categoria_id 
        WHERE pc.produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Função para carregar todas as categorias
function get_all_categorias() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM categorias");
    $stmt->execute();
    return $stmt->get_result();
}

// Função para remover uma imagem
if (isset($_GET['remove_image'])) {
    $imagem_id = $_GET['remove_image'];
    $produto_id = $_GET['id']; // Captura o ID do produto

    // Obtém o nome do arquivo da imagem
    $stmt = $conn->prepare("SELECT imagem FROM imagens WHERE id = ?");
    $stmt->bind_param("i", $imagem_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $imagem = $result->fetch_assoc()['imagem'];
    $stmt->close();

    // Remove o arquivo da pasta de imagens
    if (file_exists("images/$imagem")) {
        unlink("images/$imagem");
    }

    // Remove a entrada do banco de dados
    $stmt = $conn->prepare("DELETE FROM imagens WHERE id = ?");
    $stmt->bind_param("i", $imagem_id);
    $stmt->execute();
    $stmt->close();

    header("Location: editar_produto.php?id=$produto_id"); // Redireciona de volta para a página de edição do produto
    exit();
}

// Função para editar produtos
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    // Atualiza as informações do produto
    $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ? WHERE id = ?");
    $stmt->bind_param("ssii", $nome, $descricao, $preco, $id);
    $stmt->execute();
    $stmt->close();

    // Manipula o upload de imagens
    if (isset($_FILES['imagens']) && $_FILES['imagens']['error'][0] == UPLOAD_ERR_OK) {
        $target_dir = "images/";
        foreach ($_FILES['imagens']['name'] as $key => $name) {
            $target_file = $target_dir . basename($name);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $check = getimagesize($_FILES["imagens"]["tmp_name"][$key]);
            
            // Verifica se o arquivo é uma imagem
            if ($check !== false) {
                if (move_uploaded_file($_FILES["imagens"]["tmp_name"][$key], $target_file)) {
                    $stmt = $conn->prepare("INSERT INTO imagens (produto_id, imagem) VALUES (?, ?)");
                    $stmt->bind_param("is", $id, $name);
                    $stmt->execute();
                } else {
                    echo "Desculpe, ocorreu um erro ao fazer upload da imagem.";
                }
            } else {
                echo "O arquivo não é uma imagem.";
            }
        }
    }

    // Atualiza categorias associadas ao produto
    if (isset($_POST['categorias']) && is_array($_POST['categorias'])) {
        // Remove categorias antigas
        $stmt = $conn->prepare("DELETE FROM produto_categoria WHERE produto_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Adiciona categorias novas
        foreach ($_POST['categorias'] as $categoria_id) {
            $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $id, $categoria_id);
            $stmt->execute();
        }
    }
}

// Obtém o ID do produto para editar
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Obtém as imagens do produto
$imagens = get_imagens($id);

// Obtém categorias associadas ao produto
$categorias_produto = get_categorias($id);

// Obtém todas as categorias disponíveis
$categorias_result = get_all_categorias();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto - Civica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            margin-left: 20%;
            margin-right: 20%;
        }
        .admin-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .logout-button, .back-button {
            position: absolute;
            top: 20px;
            padding: 10px 20px;
            background-color: #333;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .back-button {
            right: 120px;
        }
        .logout-button {
            right: 20px;
        }
        .back-button:hover, .logout-button:hover {
            background-color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group button {
            padding: 10px 20px;
            background-color: #ff6600;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #333;
        }
        .image-container {
            position: relative;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .image-container img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .remove-button {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #ff0000;
            color: #fff;
            border: none;
            padding: 5px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
        }
        .remove-button:hover {
            background-color: #cc0000;
        }
        .add-button {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 10px;
            background-color: #00cc00;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-button:hover {
            background-color: #009900;
        }
        .info-box {
            background-color: #e0f7fa; /* Azul suave */
            border: 1px solid #b2ebf2;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .category-list {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .category-list input[type="checkbox"] {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <button class="back-button" onclick="window.location.href='admin.php'">Voltar</button>
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1 style="color: #ffcc00">Editar Produto</h1>

        <!-- Formulário para editar produto -->
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="id" value="<?php echo htmlspecialchars($produto['id']); ?>" readonly>
                <input type="text" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" placeholder="Nome">
                <textarea name="descricao" placeholder="Descrição"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                <input type="text" name="preco" value="<?php echo htmlspecialchars($produto['preco']); ?>" placeholder="Preço">
                <input type="file" name="imagens[]" multiple>
            </div>

            <!-- Seletor de Categorias -->
            <div class="form-group">
                <label>Categorias:</label>
                <div class="category-list">
                    <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                        <label style="border: 1px solid; border-radius: 3px; padding: 4px; font-size: 14px; margin-right: 6px">
                        <div style="width: 13px; margin: 0px; float: left;"><input type="checkbox" name="categorias[]" value="<?php echo htmlspecialchars($categoria['id']); ?>"
                            <?php if (in_array($categoria['id'], array_column($categorias_produto->fetch_all(MYSQLI_ASSOC), 'id'))): ?>
                                checked
                            <?php endif; ?>
                            ></div>
                            <div style="float: left; margin-top: 3px; margin-left: 5px;"><?php echo htmlspecialchars($categoria['nome']); ?></div>
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>

            <button type="submit" name="edit">Salvar Alterações</button>
        </form>

        <!-- Exibe as imagens existentes com opção de remoção -->
        <h2>Imagens Atuais</h2>
        <div>
            <?php while ($img = $imagens->fetch_assoc()): ?>
                <div class="image-container">
                    <img src="images/<?php echo htmlspecialchars($img['imagem']); ?>" alt="Imagem do produto">
                    <a style="text-decoration: none;" href="editar_produto.php?id=<?php echo htmlspecialchars($produto['id']); ?>&remove_image=<?php echo htmlspecialchars($img['id']); ?>" class="remove-button">X</a>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Aviso -->
        <div class="info-box">
            Info: Depois de apagares uma foto é normal a informação do produto desaparecer não te preocupes! Dá refresh à página!
        </div>
    </div>
</body>
</html>
