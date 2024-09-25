<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Funções para carregar imagens e categorias associadas ao produto
function get_imagens($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM imagens WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}

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

    $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nome, $descricao, $preco);
    if ($stmt->execute()) {
        $produto_id = $stmt->insert_id;
        $stmt->close();

        if (isset($_POST['categorias']) && is_array($_POST['categorias'])) {
            foreach ($_POST['categorias'] as $categoria_id) {
                $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $produto_id, $categoria_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        if (isset($_FILES['imagens'])) {
            $target_dir = "images/";
            $formData = new FormData();

            // Adiciona as imagens ao FormData
            foreach ($_FILES['imagens']['name'] as $key => $name) {
                $formData.append('imagens[]', $_FILES['imagens']['tmp_name'][$key], $name);
            }

            // Envia as imagens para o endpoint de compressão
            $response = file_get_contents('http://localhost:3000/compress', false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: multipart/form-data\r\n",
                    'content' => $formData->getBuffer(),
                ],
            ]));

            $compressedImages = json_decode($response, true);
            foreach ($compressedImages as $compressedImage) {
                // Salva o caminho da imagem comprimida no banco de dados
                $stmt = $conn->prepare("INSERT INTO imagens (produto_id, imagem) VALUES (?, ?)");
                $stmt->bind_param("is", $produto_id, $compressedImage['path']);
                if (!$stmt->execute()) {
                    $mensagem = "<div class='alert alert-danger'>Erro ao adicionar a imagem: " . htmlspecialchars($stmt->error) . "</div>";
                }
                $stmt->close();
            }
        }

        if (empty($mensagem)) {
            $mensagem = "<div class='alert alert-success'>Produto adicionado com sucesso!</div>";
        }
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao adicionar o produto: " . htmlspecialchars($stmt->error) . "</div>";
    }
}

$stmt = $conn->prepare("SELECT * FROM produtos");
$stmt->execute();
$produtos = $stmt->get_result();
$stmt->close();

$tem_produtos = ($produtos->num_rows > 0);
$categorias_result = $conn->query("SELECT * FROM categorias");

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
    <link rel="stylesheet" href="../css/admin.css">
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
                <input type="file" name="imagens[]" id="upload" multiple>

                <!-- Seção de seleção de categorias -->
                <h3>
                    Selecionar Categorias
                    <a href="gerenciar_categorias.php" style="margin-left: 10px; text-decoration: none; color: #007bff; font-size: 14px;">Gerenciar Categorias</a>
                </h3>
                <div class="category-list">
                    <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                        <label style="border: 1px solid; border-radius: 3px; padding: 4px; font-size: 14px; margin-right: 6px">
                            <input type="checkbox" name="categorias[]" value="<?php echo $categoria['id']; ?>">
                            <?php echo htmlspecialchars($categoria['nome']); ?>
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
                                <img src="<?php echo htmlspecialchars($imagem['imagem']); ?>" alt="Imagem" style="width: 50px; height: 50px;">
                            <?php endwhile; ?>
                        </td>
                        <td>
                            <form method="post" action="">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($produto['id']); ?>">
                                <button type="submit" name="delete">Deletar</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>Nenhum produto encontrado.</p>
        <?php endif; ?>
    </div>
</body>
</html>
