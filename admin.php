<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

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
        SELECT c.* 
        FROM categorias c 
        JOIN produto_categoria pc ON c.id = pc.categoria_id 
        WHERE pc.produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Função para adicionar produtos
if (isset($_POST['add'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    // Insere o produto
    $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nome, $descricao, $preco);
    $stmt->execute();
    $produto_id = $stmt->insert_id; // Obtém o ID do produto inserido
    $stmt->close();

    // Associa categorias ao produto
    if (isset($_POST['categorias']) && is_array($_POST['categorias'])) {
        foreach ($_POST['categorias'] as $categoria_id) {
            $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $produto_id, $categoria_id);
            if (!$stmt->execute()) {
                echo "Erro ao associar categoria ao produto: " . $stmt->error;
            }
        }
    }

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
                    $stmt->bind_param("is", $produto_id, $name);
                    if (!$stmt->execute()) {
                        echo "Erro ao salvar imagem no banco de dados: " . $stmt->error;
                    }
                } else {
                    echo "Desculpe, ocorreu um erro ao fazer upload da imagem.";
                }
            } else {
                echo "O arquivo não é uma imagem.";
            }
        }
    }

    echo "Produto adicionado com sucesso!";
}

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

// Função para carregar categorias existentes
$categorias_result = $conn->query("SELECT * FROM categorias");

// Função para buscar todos os produtos
$stmt = $conn->prepare("SELECT * FROM produtos");
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
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Civica</title>
    <style>
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
        .category-selector {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .category-box {
            padding: 10px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: background-color 0.3s ease;
        }
        .category-box.selected {
            background-color: #00cc00;
            color: #fff;
        }
    </style>
    <script>
        function toggleCategory(categoryBox, categoryId) {
            categoryBox.classList.toggle('selected');
            const selectedCategories = document.getElementById('selected_categories');
            const index = Array.from(selectedCategories.options).findIndex(option => option.value == categoryId);
            if (categoryBox.classList.contains('selected')) {
                if (index === -1) {
                    const option = document.createElement('option');
                    option.value = categoryId;
                    option.textContent = categoryBox.textContent;
                    selectedCategories.appendChild(option);
                }
            } else {
                if (index !== -1) {
                    selectedCategories.remove(index);
                }
            }
        }
    </script>
</head>
<body>
    <div class="admin-container">
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1>Admin - Civica</h1>

        <!-- Formulário para adicionar produto -->
        <h2>Adicionar Produto</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="nome" placeholder="Nome do Produto" required>
                <textarea name="descricao" placeholder="Descrição" required></textarea>
                <input type="text" name="preco" placeholder="Preço" required>
                <input type="file" name="imagens[]" multiple>
                <div class="form-group">
                    <h3>Selecionar Categorias</h3>
                    <div class="category-selector">
                        <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                            <div class="category-box" onclick="toggleCategory(this, <?php echo $categoria['id']; ?>)">
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <select id="selected_categories" name="categorias[]" multiple style="display: none;"></select>
                </div>
            </div>
            <button type="submit" name="add">Adicionar Produto</button>
        </form>

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
                            <a href="editar_produto.php?id=<?php echo htmlspecialchars($produto['id']); ?>" class="edit-button">Editar</a>
                            <form method="post" action="" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($produto['id']); ?>">
                                <button type="submit" name="delete" class="delete-button">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Gerenciar Categorias -->
        <h2>Categorias <a href="gerenciar_categorias.php" class="edit-button">Gerir</a></h2>
    </div>
</body>
</html>
