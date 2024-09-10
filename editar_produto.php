<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Verifica se o ID do produto foi passado
if (!isset($_GET['id'])) {
    header("Location: admin.php"); // Redireciona de volta para a página de administração se o ID não estiver presente
    exit();
}

$id = $_GET['id'];

// Busca o produto a ser editado
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Função para lidar com a atualização do produto
if (isset($_POST['update'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

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

    $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ? WHERE id = ?");
    $stmt->bind_param("ssii", $nome, $descricao, $preco, $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin.php"); // Redireciona para a página de administração após a atualização
    exit();
}

// Carregar imagens associadas ao produto
function get_imagens($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM imagens WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
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
        }
        .admin-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Editar Produto</h1>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" placeholder="Nome" required>
                <textarea name="descricao" placeholder="Descrição"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                <input type="text" name="preco" value="<?php echo htmlspecialchars($produto['preco']); ?>" placeholder="Preço" required>
                <input type="file" name="imagens[]" multiple>
            </div>
            <button type="submit" name="update">Atualizar Produto</button>
        </form>
    </div>
</body>
</html>
