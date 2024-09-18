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
    // Obtém informações sobre a imagem
    list($width, $height, $type) = getimagesize($source_path);
    
    // Calcula a nova largura e altura
    $ratio = $width / $height;
    if ($width > $height) {
        $new_width = min($max_width, $width);
        $new_height = $new_width / $ratio;
    } else {
        $new_height = min($max_height, $height);
        $new_width = $new_height * $ratio;
    }

    // Cria uma nova imagem em branco com a nova largura e altura
    $image_p = imagecreatetruecolor($new_width, $new_height);

    // Carrega a imagem original
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

    // Redimensiona a imagem
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Salva a imagem redimensionada e compactada no caminho de destino
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($image_p, $target_path, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($image_p, $target_path, 9); // PNG não usa qualidade, mas o parâmetro define o nível de compressão
            break;
        case IMAGETYPE_GIF:
            imagegif($image_p, $target_path);
            break;
    }

    // Libera a memória
    imagedestroy($image);
    imagedestroy($image_p);

    return true;
}



// Função para carregar imagens associadas ao produto
function get_imagens($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM imagens WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}

function get_imagem_principal($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT imagem FROM imagens WHERE produto_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Função para carregar categorias associadas ao produto
function get_categorias($produto_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT c.nome 
        FROM categorias c 
        JOIN produto_categoria pc ON c.id = pc.categoria_id 
        WHERE pc.produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Mensagem de sucesso ou erro
$mensagem = '';
if (isset($_POST['add'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    // Função para adicionar produtos
    if (isset($_POST['add'])) {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $preco = $_POST['preco'];
    
        // Insere o produto
        $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nome, $descricao, $preco);
        if ($stmt->execute()) {
            $produto_id = $stmt->insert_id; // Obtém o ID do produto inserido
            $stmt->close();
    
            // Associa categorias ao produto
            if (isset($_POST['categorias']) && is_array($_POST['categorias'])) {
                foreach ($_POST['categorias'] as $categoria_id) {
                    $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $produto_id, $categoria_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
    
            // Manipula o upload de imagens
            if (isset($_FILES['imagens']) && $_FILES['imagens']['error'][0] == UPLOAD_ERR_OK) {
                $target_dir = "images/";
                foreach ($_FILES['imagens']['name'] as $key => $name) {
                    $target_file = $target_dir . basename($name);
                    $temp_file = $_FILES['imagens']['tmp_name'][$key];
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    $check = getimagesize($temp_file);
                    
                    // Verifica se o arquivo é uma imagem
                    if ($check !== false) {
                        // Gera um nome único para a imagem
                        $unique_name = uniqid() . '.' . $imageFileType;
                        $target_file = $target_dir . $unique_name;
                        
                        // Redimensiona e comprime a imagem
                        if (resize_and_compress_image($temp_file, $target_file)) {
                            // Insere a imagem na base de dados
                            $stmt = $conn->prepare("INSERT INTO imagens (produto_id, imagem) VALUES (?, ?)");
                            $stmt->bind_param("is", $produto_id, $unique_name);
                            if (!$stmt->execute()) {
                                $mensagem = "<div class='alert error'>Erro ao adicionar a imagem: " . htmlspecialchars($stmt->error) . "</div>";
                            }
                            $stmt->close();
                        } else {
                            $mensagem = "Desculpe, ocorreu um erro ao redimensionar e comprimir a imagem.";
                        }
                    } else {
                        $mensagem = "O arquivo não é uma imagem.";
                    }
                }
            }
    
            if (empty($mensagem)) {
                $mensagem = "<div class='alert alert-success'>Produto adicionado com sucesso!</div>";
            }
        } else {
            $mensagem = "<div class='alert error'>Erro ao adicionar o produto: " . htmlspecialchars($stmt->error) . "</div>";
        }
    }
}

// Função para buscar todos os produtos
$stmt = $conn->prepare("SELECT * FROM produtos");
$stmt->execute();
$produtos = $stmt->get_result();
$stmt->close();

// Verifica se há produtos
$tem_produtos = ($produtos->num_rows > 0);

// Função para carregar categorias existentes
$categorias_result = $conn->query("SELECT * FROM categorias");

// Função para excluir produtos
if (isset($_POST['delete'])) {
    $produto_id = $_POST['id'];

    // Remove o produto da tabela produto_categoria
    $stmt = $conn->prepare("DELETE FROM produto_categoria WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->close();

    // Remove imagens associadas
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

    // Remove o produto
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
    <style>
        h1{
            color: #ffcc00;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            margin-right: 10%;
            margin-left: 10%;
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
            background-color: #333;
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
            max-width: 300px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .delete-button, .edit-button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .delete-button {
            background-color: #ff0000;
            color: #fff;
        }
        .delete-button:hover {
            background-color: #cc0000;
        }
        .edit-button {
            background-color: #ffcc00;
            color: #fff;
        }
        .edit-button:hover {
            background-color: #cca700;
        }
        .category-list {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .category-list input[type="checkbox"] {
            margin-right: 10px;
        }

        /* Estilos para a mensagem de alerta */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            position: relative;
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert.fade-out {
            opacity: 0;
        }
    </style>
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
                <div><input type="text" name="nome" placeholder="Nome do Produto" required></div>
                <div><textarea name="descricao" placeholder="Descrição" required></textarea></div>
                <div><input type="text" name="preco" placeholder="Preço" required></div>
                <div><input type="file" name="imagens[]" multiple></div>
                
                <!-- Seção de seleção de categorias -->
                <h3>
                    Selecionar Categorias
                    <a href="gerenciar_categorias.php" style="margin-left: 10px; text-decoration: none; color: #007bff; font-size: 14px;">Gerenciar Categorias</a>
                </h3>
                <div class="category-list">
                    <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                        <label style="border: 1px solid; border-radius: 3px; padding: 4px; font-size: 14px; margin-right: 6px">
                            <div style="width: 13px; margin: 0px; float: left;"><input type="checkbox" name="categorias[]" value="<?php echo $categoria['id']; ?>"></div>
                            <div style="float: left; margin-top: 3px; margin-left: 5px;"><?php echo htmlspecialchars($categoria['nome']); ?></div>
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>
            <button type="submit" name="add">Adicionar Produto</button>
        </form>

        <!-- Verifica se há produtos -->
        <?php if ($tem_produtos): ?>
        <!-- Lista de produtos -->
        <h2>Produtos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Categorias</th>
                    <th>Imagens</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($produto = $produtos->fetch_assoc()): ?>
                    <?php
                    $categorias_produto = get_categorias($produto['id']);
                    $imagens_produto = get_imagens($produto['id']);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produto['id']); ?></td>
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['descricao']); ?></td>
                        <td><?php echo htmlspecialchars($produto['preco']); ?></td>
                        <td>
                            <?php while ($categoria = $categorias_produto->fetch_assoc()): ?>
                                <?php echo htmlspecialchars($categoria['nome']) . '<br>'; ?>
                            <?php endwhile; ?>
                        </td>
                        <td>
                            <?php while ($imagem = $imagens_produto->fetch_assoc()): ?>
                                <img src="images/<?php echo htmlspecialchars($imagem['imagem']); ?>" alt="Imagem" style="width: 50px; height: 50px; object-fit: cover; margin-right: 5px;">
                            <?php endwhile; ?>
                        </td>
                        <td>
                            <a style="text-decoration: none;" href="editar_produto.php?id=<?php echo htmlspecialchars($produto['id']); ?>" class="edit-button">Editar</a>
                            <form method="post" action="" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($produto['id']); ?>">
                                <button type="submit" name="delete" class="delete-button">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>Nenhum produto foi encontrado.</p>
        <?php endif; ?>
    </div>

    <!-- Script para desaparecer a mensagem após 5 segundos -->
    <script>
        window.onload = function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.classList.add('fade-out');
                }, 5000);
            });
        };
    </script>
</body>
</html>
