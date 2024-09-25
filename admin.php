<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Função para redimensionar e compactar a imagem
function resize_and_compress_image($source_path, $target_path, $max_width = 800, $max_height = 600, $quality = 75) {
    list($width, $height, $type) = getimagesize($source_path);
    $ratio = $width / $height;

    // Calcula novas dimensões
    if ($width > $height) {
        $new_width = min($max_width, $width);
        $new_height = $new_width / $ratio;
    } else {
        $new_height = min($max_height, $height);
        $new_width = $new_height * $ratio;
    }

    // Cria nova imagem
    $image_p = imagecreatetruecolor($new_width, $new_height);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source_path);
            imagealphablending($image_p, false);
            imagesavealpha($image_p, true);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }

    // Redimensiona e copia a imagem
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Salva a imagem no formato correto
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($image_p, $target_path, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($image_p, $target_path, 6); // Nível de compressão PNG
            break;
        case IMAGETYPE_GIF:
            imagegif($image_p, $target_path);
            break;
    }

    imagedestroy($image);
    imagedestroy($image_p);

    return true;
}

// Mensagem de sucesso ou erro
$mensagem = '';
if (isset($_POST['add'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    // Insere o produto
    $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nome, $descricao, $preco);
    if ($stmt->execute()) {
        $produto_id = $stmt->insert_id;
        $stmt->close();

        // Adiciona categorias
        if (isset($_POST['categorias']) && is_array($_POST['categorias'])) {
            foreach ($_POST['categorias'] as $categoria_id) {
                $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $produto_id, $categoria_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Processa imagens
        if (isset($_FILES['imagens']) && count($_FILES['imagens']['name']) > 0) {
            $target_dir = "images/";
            $totalFiles = count($_FILES['imagens']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['imagens']['error'][$i] == UPLOAD_ERR_OK) {
                    $temp_file = $_FILES['imagens']['tmp_name'][$i];
                    $imageFileType = strtolower(pathinfo($_FILES['imagens']['name'][$i], PATHINFO_EXTENSION));
                    $unique_name = uniqid() . '.' . $imageFileType;
                    $target_file = $target_dir . $unique_name;

                    // Verifica se é uma imagem
                    $check = getimagesize($temp_file);
                    if ($check !== false) {
                        // Redimensiona e comprime a imagem
                        if (resize_and_compress_image($temp_file, $target_file)) {
                            // Insere a imagem no banco de dados
                            $stmt = $conn->prepare("INSERT INTO imagens (produto_id, imagem) VALUES (?, ?)");
                            $stmt->bind_param("is", $produto_id, $unique_name);
                            if (!$stmt->execute()) {
                                $mensagem = "<div class='alert alert-danger'>Erro ao adicionar a imagem: " . htmlspecialchars($stmt->error) . "</div>";
                            }
                            $stmt->close();
                        } else {
                            $mensagem = "<div class='alert alert-danger'>Desculpe, ocorreu um erro ao redimensionar e comprimir a imagem.</div>";
                        }
                    } else {
                        $mensagem = "<div class='alert alert-danger'>O arquivo não é uma imagem.</div>";
                    }
                } else {
                    $mensagem = "<div class='alert alert-danger'>Erro ao carregar a imagem.</div>";
                }
            }
        }

        if (empty($mensagem)) {
            $mensagem = "<div class='alert alert-success'>Produto adicionado com sucesso!</div>";
        }
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao adicionar o produto: " . htmlspecialchars($stmt->error) . "</div>";
    }
}

// Lista produtos
$stmt = $conn->prepare("SELECT * FROM produtos");
$stmt->execute();
$produtos = $stmt->get_result();
$stmt->close();

$tem_produtos = ($produtos->num_rows > 0);

// Obtém categorias
$categorias_result = $conn->query("SELECT * FROM categorias");

// Processa a eliminação de produtos
if (isset($_POST['delete'])) {
    $produto_id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM produto_categoria WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT imagem FROM imagens WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($img = $result->fetch_assoc()) {
        $imagem = $img['imagem'];
        if (file_exists("images/$imagem")) {
            unlink("images/$imagem");
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php");
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
    <title>Admin - Civica</title>
    <link rel="stylesheet" href="./css/admin.css">
</head>
<body>
    <div class="admin-container">
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1>Admin - Civica</h1>

        <!-- Exibe a mensagem se existir -->
        <?php if ($mensagem): ?>
            <?php echo $mensagem; ?>
        <?php endif; ?>

        <!-- Formulário para adicionar produto -->
        <h2>Adicionar Produto</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="nome" placeholder="Nome do Produto" required>
                <textarea name="descricao" placeholder="Descrição" required></textarea>
                <input type="text" name="preco" placeholder="Preço" required>
                <input type="file" name="imagens[]" multiple required>
                <button type="submit" name="add">Adicionar Produto</button>
            </div>
        </form>

        <h2>Lista de Produtos</h2>
        <?php if ($tem_produtos): ?>
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Ações</th>
                </tr>
                <?php while ($produto = $produtos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['preco']); ?> €</td>
                        <td>
                            <form method="post" action="">
                                <input type="hidden" name="id" value="<?php echo $produto['id']; ?>">
                                <button type="submit" name="delete">Eliminar</button>
                                <button type="button" onclick="window.location.href='edit_product.php?id=<?php echo $produto['id']; ?>'">Editar</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Não há produtos cadastrados.</p>
        <?php endif; ?>
    </div>
</body>
</html>
