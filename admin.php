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

function get_imagem_principal($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT imagem FROM imagens WHERE produto_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
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

/// Mensagem de sucesso ou erro
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

        if (isset($_FILES['imagens']) && $_FILES['imagens']['error'][0] == UPLOAD_ERR_OK) {
            $target_dir = "images/";
            foreach ($_FILES['imagens']['name'] as $key => $name) {
                $imageFileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $unique_name = uniqid() . '.' . $imageFileType;
                $target_file = $target_dir . $unique_name;
                $temp_file = $_FILES['imagens']['tmp_name'][$key];

                $check = getimagesize($temp_file);
                if ($check !== false) {
                    // Chamar o script Python para redimensionar e comprimir a imagem
                    $command = "scripts/compress_image.py $temp_file $target_file";
                    $output = null;
                    $return_var = null;
                    exec($command, $output, $return_var);

                    if ($return_var !== 0) {
                        $mensagem = "<div class='alert alert-danger'>Desculpe, ocorreu um erro com Python.<br>Erro: " . implode("<br>", $output) . "</div>";
                    }

                    // Verifica se o comando foi executado corretamente
                    if ($return_var === 0) {
                        $stmt = $conn->prepare("INSERT INTO imagens (produto_id, imagem) VALUES (?, ?)");
                        $stmt->bind_param("is", $produto_id, $unique_name);
                        if (!$stmt->execute()) {
                            $mensagem = "<div class='alert alert-danger'>Erro ao adicionar a imagem: " . htmlspecialchars($stmt->error) . "</div>";
                        }
                        $stmt->close();
                    } else {
                        $mensagem = "<div class='alert alert-danger'>Desculpe, ocorreu um erro ao redimensionar e comprimir a imagem com Python.</div>";
                    }
                } else {
                    $mensagem = "<div class='alert alert-danger'>O arquivo não é uma imagem.</div>";
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
    <script src="https://cdn.jsdelivr.net/npm/pica@8.1.1/dist/pica.min.js"></script>
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
                <div><input type="text" name="nome" placeholder="Nome do Produto" required></div>
                <div><textarea name="descricao" placeholder="Descrição" required></textarea></div>
                <div><input type="text" name="preco" placeholder="Preço" required></div>
                <div><input type="file" name="imagens[]" id="upload" multiple></div>
                
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
    <script>
        document.getElementById('upload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const pica = new Pica();
                    canvas.width = 800; // Defina a largura desejada
                    canvas.height = img.height * (800 / img.width); // Mantém a proporção
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    pica.toBlob(canvas, 'image/jpeg', 0.8) // 0.8 = qualidade
                        .then(function (blob) {
                            // Crie um FormData para o upload
                            const formData = new FormData();
                            formData.append('image', blob, file.name);

                            // Envie o FormData para o servidor
                            fetch('upload.php', {
                                method: 'POST',
                                body: formData
                            }).then(response => response.text())
                            .then(result => console.log(result));
                        });
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    </script>
</body>
</html>
