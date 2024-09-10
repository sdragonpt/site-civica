<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php';

// Função para carregar o produto
if (!isset($_GET['id'])) {
    header("Location: admin.php"); // Redireciona se o ID do produto não for fornecido
    exit();
}
$produto_id = $_GET['id'];

// Carrega o produto
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $produto_id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produto) {
    header("Location: admin.php"); // Redireciona se o produto não for encontrado
    exit();
}

// Função para carregar imagens associadas ao produto
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

// Carrega categorias existentes
$categorias_result = $conn->query("SELECT * FROM categorias");
$categorias_produto = get_categorias($produto_id);
$imagens_produto = get_imagens($produto_id);

// Atualiza o produto
if (isset($_POST['update'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    // Atualiza o produto
    $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ? WHERE id = ?");
    $stmt->bind_param("ssii", $nome, $descricao, $preco, $produto_id);
    $stmt->execute();
    $stmt->close();

    // Atualiza categorias associadas
    $stmt = $conn->prepare("DELETE FROM produto_categoria WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->close();

    if (isset($_POST['categorias']) && is_array($_POST['categorias'])) {
        foreach ($_POST['categorias'] as $categoria_id) {
            $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $produto_id, $categoria_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Manipula o upload de novas imagens
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
                    $stmt->bind_param("is", $produto_id, $name);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    echo "Desculpe, ocorreu um erro ao fazer upload da imagem.";
                }
            } else {
                echo "O arquivo não é uma imagem.";
            }
        }
    }

    header("Location: admin.php"); // Redireciona após atualização
    exit();
}

// Remove uma imagem
if (isset($_POST['delete_imagem'])) {
    $imagem_id = $_POST['imagem_id'];
    
    $stmt = $conn->prepare("SELECT imagem FROM imagens WHERE id = ?");
    $stmt->bind_param("i", $imagem_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $imagem = $result->fetch_assoc()['imagem'];
    $stmt->close();
    
    if (file_exists("images/$imagem")) {
        unlink("images/$imagem");
    }
    
    $stmt = $conn->prepare("DELETE FROM imagens WHERE id = ?");
    $stmt->bind_param("i", $imagem_id);
    $stmt->execute();
    $stmt->close();

    header("Location: editar_produto.php?id=$produto_id"); // Redireciona após exclusão
    exit();
}

// Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
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
        /* Estilos para a página de edição de produtos */
        /* (Use o mesmo estilo da página admin.php ou ajuste conforme necessário) */
    </style>
</head>
<body>
    <div class="admin-container">
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1>Editar Produto</h1>

        <!-- Formulário para editar o produto -->
        <h2>Editar Produto</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" placeholder="Nome do Produto" required>
                <textarea name="descricao" placeholder="Descrição" required><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                <input type="text" name="preco" value="<?php echo htmlspecialchars($produto['preco']); ?>" placeholder="Preço" required>
                <input type="file" name="imagens[]" multiple>
                
                <!-- Seção de seleção de categorias -->
                <h3>
                    Selecionar Categorias
                </h3>
                <div class="category-list">
                    <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                        <label style="border: 1px solid; border-radius: 3px; padding: 4px; font-size: 14px; margin-right: 6px">
                            <div style="width: 13px; margin: 0px; float: left;">
                                <input type="checkbox" name="categorias[]" value="<?php echo $categoria['id']; ?>"
                                <?php
                                    $categorias_produto->data_seek(0);
                                    while ($cat_produto = $categorias_produto->fetch_assoc()) {
                                        if ($categoria['id'] == $cat_produto['id']) {
                                            echo ' checked';
                                            break;
                                        }
                                    }
                                ?>>
                            </div>
                            <div style="float: left; margin-top: 3px; margin-left: 5px;"><?php echo htmlspecialchars($categoria['nome']); ?></div>
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>
            <button type="submit" name="update">Atualizar Produto</button>
        </form>

        <!-- Imagens associadas ao produto -->
        <h2>Imagens do Produto</h2>
        <div>
            <?php while ($imagem = $imagens_produto->fetch_assoc()): ?>
                <div style="display: inline-block; position: relative; margin-right: 10px;">
                    <img src="images/<?php echo htmlspecialchars($imagem['imagem']); ?>" alt="Imagem" style="width: 100px; height: 100px; object-fit: cover;">
                    <form method="post" action="" style="position: absolute; top: 0; right: 0;">
                        <input type="hidden" name="imagem_id" value="<?php echo htmlspecialchars($imagem['id']); ?>">
                        <button type="submit" name="delete_imagem" style="background-color: #ff0000; color: #fff; border: none; padding: 5px;">Excluir</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Botão de voltar -->
        <a href="admin.php" style="display: inline-block; margin-top: 20px; text-decoration: none; color: #007bff;">Voltar à Administração</a>
    </div>
</body>
</html>
