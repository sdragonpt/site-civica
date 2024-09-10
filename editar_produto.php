<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Carregar imagens associadas ao produto
function get_imagens($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM imagens WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Função para editar produtos
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

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
}

// Função para procurar produtos
$search = '';
if (isset($_POST['search'])) {
    $search = $_POST['search'];
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE nome LIKE ?");
    $stmt->bind_param("s", $search);
    $search = "%$search%";
} else {
    $stmt = $conn->prepare("SELECT * FROM produtos");
}

// Executa a consulta
$stmt->execute();
$produtos = $stmt->get_result();
$stmt->close();

// Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Mostrar o produto para edição
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Editar Produto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .admin-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .logout-button {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #ff6600;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .logout-button:hover {
            background-color: #cc5200;
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
            background-color: #cc5200;
        }
        .image-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .image-container img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            position: relative;
        }
        .image-container .remove-button {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(255, 0, 0, 0.5);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .add-image-container {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .add-image-container input {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1>Editar Produto</h1>

        <?php if ($product): ?>
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
            <div class="form-group">
                <input type="text" name="nome" value="<?php echo htmlspecialchars($product['nome']); ?>" placeholder="Nome" required>
                <textarea name="descricao" placeholder="Descrição"><?php echo htmlspecialchars($product['descricao']); ?></textarea>
                <input type="text" name="preco" value="<?php echo htmlspecialchars($product['preco']); ?>" placeholder="Preço" required>
            </div>

            <div class="form-group">
                <label>Imagens Existentes:</label>
                <div class="image-container">
                    <?php
                    $imagens = get_imagens($product['id']);
                    while ($img = $imagens->fetch_assoc()) {
                        echo '<div>';
                        echo '<img src="images/' . htmlspecialchars($img['imagem']) . '" alt="' . htmlspecialchars($product['nome']) . '">';
                        echo '<button class="remove-button" onclick="removeImage(' . htmlspecialchars($img['id']) . ')">X</button>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <div class="form-group">
                <label>Adicionar Imagens:</label>
                <input type="file" name="imagens[]" multiple>
            </div>
            <button type="submit" name="edit">Salvar Alterações</button>
        </form>
        <?php else: ?>
        <p>Produto não encontrado.</p>
        <?php endif; ?>
    </div>

    <script>
        function removeImage(imageId) {
            if (confirm("Tem certeza de que deseja excluir esta imagem?")) {
                window.location.href = 'remove_image.php?id=' + imageId;
            }
        }
    </script>
</body>
</html>