<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
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

// Excluir imagem
if (isset($_GET['delete_image'])) {
    $image_id = $_GET['delete_image'];
    $stmt = $conn->prepare("DELETE FROM imagens WHERE id = ?");
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $stmt->close();
    header("Location: editar_produto.php?id=" . $_GET['produto_id']);
    exit();
}

// Atualizar produto
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    // Atualiza o produto
    $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ? WHERE id = ?");
    $stmt->bind_param("ssii", $nome, $descricao, $preco, $id);
    $stmt->execute();
    $stmt->close();

    // Adiciona novas imagens
    if (isset($_FILES['imagens']) && $_FILES['imagens']['error'][0] == UPLOAD_ERR_OK) {
        $target_dir = "images/";
        foreach ($_FILES['imagens']['name'] as $key => $name) {
            $target_file = $target_dir . basename($name);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $check = getimagesize($_FILES["imagens"]["tmp_name"][$key]);

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

if (!isset($_GET['id'])) {
    echo "ID do produto não fornecido.";
    exit();
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto - Civica</title>
    <style>
        /* Adicione seu estilo aqui */
    </style>
</head>
<body>
    <h1>Editar Produto</h1>
    <form method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($produto['id']); ?>">
        <div>
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" required>
        </div>
        <div>
            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
        </div>
        <div>
            <label for="preco">Preço:</label>
            <input type="text" id="preco" name="preco" value="<?php echo htmlspecialchars($produto['preco']); ?>" required>
        </div>

        <h2>Imagens Existentes</h2>
        <?php
        $imagens = get_imagens($id);
        while ($img = $imagens->fetch_assoc()) {
            echo '<div style="display:inline-block; position:relative; margin-right:10px;">
                    <img src="images/' . htmlspecialchars($img['imagem']) . '" alt="' . htmlspecialchars($produto['nome']) . '" style="width:100px;">
                    <a href="editar_produto.php?id=' . htmlspecialchars($id) . '&delete_image=' . htmlspecialchars($img['id']) . '" style="position:absolute; top:0; right:0; color:red;">&times;</a>
                  </div>';
        }
        ?>

        <div>
            <label for="imagens">Adicionar Imagens:</label>
            <input type="file" id="imagens" name="imagens[]" multiple>
        </div>
        <button type="submit" name="update">Atualizar Produto</button>
    </form>
</body>
</html>
