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

// Função para carregar a imagem de capa associada ao produto
function get_imagem_capa($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM imagem_capa WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
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

// Função para remover a imagem de capa
if (isset($_GET['remove_capa'])) {
    $produto_id = $_GET['id']; // Captura o ID do produto

    // Obtém o nome do arquivo da imagem de capa
    $stmt = $conn->prepare("SELECT imagem_capa FROM imagem_capa WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $imagem_capa = $result->fetch_assoc()['imagem_capa'];
    $stmt->close();

    // Remove o arquivo da pasta de imagens
    if (file_exists("images/$imagem_capa")) {
        unlink("images/$imagem_capa");
    }

    // Remove a entrada do banco de dados
    $stmt = $conn->prepare("DELETE FROM imagem_capa WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
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

    // Manipula o upload da imagem de capa
    if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "images/";
        $imagem_capa_nome = basename($_FILES['imagem_capa']['name']);
        $target_file = $target_dir . $imagem_capa_nome;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["imagem_capa"]["tmp_name"]);
        
        // Verifica se o arquivo é uma imagem
        if ($check !== false) {
            if (move_uploaded_file($_FILES["imagem_capa"]["tmp_name"], $target_file)) {
                // Remove a imagem de capa antiga, se houver
                $stmt = $conn->prepare("SELECT imagem_capa FROM imagem_capa WHERE produto_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $imagem_capa_antiga = $result->fetch_assoc()['imagem_capa'];
                $stmt->close();

                if ($imagem_capa_antiga && file_exists("images/$imagem_capa_antiga")) {
                    unlink("images/$imagem_capa_antiga");
                }

                // Atualiza ou insere a nova imagem de capa
                $stmt = $conn->prepare("REPLACE INTO imagem_capa (produto_id, imagem_capa) VALUES (?, ?)");
                $stmt->bind_param("is", $id, $imagem_capa_nome);
                $stmt->execute();
                $stmt->close();
            } else {
                echo "Desculpe, ocorreu um erro ao fazer upload da imagem de capa.";
            }
        } else {
            echo "O arquivo de capa não é uma imagem.";
        }
    }

    // Manipula o upload de imagens adicionais
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

// Obtém a imagem de capa do produto
$imagem_capa_result = get_imagem_capa($id);
$imagem_capa = $imagem_capa_result->fetch_assoc();
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
            padding: 0;
        }
        .admin-container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .back-button, .logout-button {
            background-color: #ffcc00;
            border: none;
            padding: 10px 20px;
            color: #fff;
            cursor: pointer;
            border-radius: 5px;
        }
        .back-button {
            margin-right: 10px;
        }
        .image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 10px;
        }
        .image-container img {
            max-width: 200px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .remove-button {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(255, 0, 0, 0.7);
            color: #fff;
            padding: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .category-list label {
            display: block;
            margin-bottom: 5px;
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
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($produto['id']); ?>">

            <div class="form-group">
                <input type="text" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" placeholder="Nome">
                <textarea name="descricao" placeholder="Descrição"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                <input type="text" name="preco" value="<?php echo htmlspecialchars($produto['preco']); ?>" placeholder="Preço">
                
                <!-- Campo para upload da imagem de capa -->
                <div class="form-group">
                    <label>Imagem de Capa:</label>
                    <?php if ($imagem_capa): ?>
                        <div class="image-container">
                            <img src="images/<?php echo htmlspecialchars($imagem_capa['imagem_capa']); ?>" alt="Imagem de Capa">
                            <a href="editar_produto.php?id=<?php echo htmlspecialchars($produto['id']); ?>&remove_capa=true" class="remove-button">X</a>
                        </div>
                    <?php else: ?>
                        <p>Nenhuma imagem de capa definida.</p>
                    <?php endif; ?>
                    <input type="file" name="imagem_capa">
                </div>
                
                <!-- Campo para upload de imagens adicionais -->
                <div class="form-group">
                    <label>Imagens Adicionais:</label>
                    <input type="file" name="imagens[]" multiple>
                </div>
                
                <!-- Seletor de Categorias -->
                <div class="form-group">
                    <label>Categorias:</label>
                    <div class="category-list">
                        <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                            <label>
                                <input type="checkbox" name="categorias[]" value="<?php echo htmlspecialchars($categoria['id']); ?>"
                                    <?php echo in_array($categoria['id'], array_column($categorias_produto->fetch_all(MYSQLI_ASSOC), 'id')) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <button type="submit" name="edit">Salvar Alterações</button>
        </form>

        <!-- Exibe as imagens adicionais -->
        <h2>Imagens Adicionais</h2>
        <div class="image-container">
            <?php while ($imagem = $imagens->fetch_assoc()): ?>
                <div class="image-container">
                    <img src="images/<?php echo htmlspecialchars($imagem['imagem']); ?>" alt="Imagem Adicional">
                    <a href="editar_produto.php?id=<?php echo htmlspecialchars($produto['id']); ?>&remove_image=<?php echo htmlspecialchars($imagem['id']); ?>" class="remove-button">X</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
