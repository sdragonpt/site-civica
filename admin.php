<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
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

function resize_and_compress_image($source_file, $target_file, $quality = 75) {
    $info = getimagesize($source_file);

    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source_file);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source_file);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source_file);
    } else {
        return false;
    }

    // Salva a imagem redimensionada com compressão
    imagejpeg($image, $target_file, $quality);
    imagedestroy($image);
    
    return true;
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

        if (isset($_FILES['imagens']) && $_FILES['imagens']['error'][0] == UPLOAD_ERR_OK) {
            $target_dir = "images/";
            foreach ($_FILES['imagens']['name'] as $key => $name) {
                $target_file = $target_dir . basename($name);
                $temp_file = $_FILES['imagens']['tmp_name'][$key];
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                $unique_name = uniqid() . '.' . $imageFileType;
                $target_file = $target_dir . $unique_name;

                $check = getimagesize($temp_file);
                if ($check !== false) {
                    if (resize_and_compress_image($temp_file, $target_file)) {
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
                    $mensagem = "<div class='alert alert-danger'>O arquivo não é uma imagem válida.</div>";
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

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Civica</title>
    <script src="https://cdn.jsdelivr.net/npm/pica@8.1.1/dist/pica.min.js"></script>
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
        <form id="produto-form" method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <div><input type="text" name="nome" placeholder="Nome do Produto" required></div>
                <div><textarea name="descricao" placeholder="Descrição" required></textarea></div>
                <div><input type="text" name="preco" placeholder="Preço" required></div>
                <div><input type="file" name="imagens[]" id="upload" multiple required></div>

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
            <button type="button" onclick="compressAndUpload()">Adicionar Produto</button>
        </form>
    </div>

    <script>
        function compressAndUpload() {
            const files = document.getElementById('upload').files;
            const pica = window.pica();
            const uploadForm = new FormData(document.getElementById('produto-form'));

            Array.from(files).forEach((file, index) => {
                const img = new Image();
                const reader = new FileReader();

                reader.onload = function(e) {
                    img.src = e.target.result;
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const MAX_WIDTH = 800;
                        const scaleFactor = MAX_WIDTH / img.width;
                        canvas.width = MAX_WIDTH;
                        canvas.height = img.height * scaleFactor;

                        pica.resize(img, canvas)
                            .then(result => pica.toBlob(result, 'image/jpeg', 0.8))
                            .then(blob => {
                                uploadForm.append('imagens[]', blob, file.name);

                                // Após a última imagem, envia o formulário
                                if (index === files.length - 1) {
                                    uploadFiles(uploadForm);
                                }
                            });
                    };
                };

                reader.readAsDataURL(file);
            });
        }

        function uploadFiles(formData) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    alert('Imagens enviadas com sucesso!');
                    window.location.reload();
                } else {
                    alert('Erro ao enviar as imagens.');
                }
            };
            xhr.send(formData);
        }
    </script>

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
