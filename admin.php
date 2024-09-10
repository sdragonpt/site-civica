<?php
include('config.php');

// Adiciona produto
if (isset($_POST['adicionar'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $imagem = $_FILES['imagem']['name'];
    $categoria_ids = $_POST['categorias'];

    // Verifica se a imagem foi enviada
    if (!empty($imagem)) {
        $target = "images/" . basename($imagem);
        move_uploaded_file($_FILES['imagem']['tmp_name'], $target);
    }

    // Insere o produto
    $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, imagem) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $nome, $descricao, $preco, $imagem);
    $stmt->execute();
    $produto_id = $stmt->insert_id;

    // Associa o produto às categorias
    foreach ($categoria_ids as $categoria_id) {
        $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $produto_id, $categoria_id);
        $stmt->execute();
    }

    echo "<p>Produto adicionado com sucesso!</p>";
}

// Remove produto
if (isset($_GET['delete'])) {
    $produto_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    echo "<p>Produto removido com sucesso!</p>";
}

// Atualiza produto
if (isset($_POST['atualizar'])) {
    $produto_id = $_POST['produto_id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $imagem = $_FILES['imagem']['name'];
    $categoria_ids = $_POST['categorias'];

    // Atualiza o produto
    $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, imagem = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $nome, $descricao, $preco, $imagem, $produto_id);
    $stmt->execute();

    // Remove associações antigas
    $stmt = $conn->prepare("DELETE FROM produto_categoria WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();

    // Associa o produto às novas categorias
    foreach ($categoria_ids as $categoria_id) {
        $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $produto_id, $categoria_id);
        $stmt->execute();
    }

    echo "<p>Produto atualizado com sucesso!</p>";
}

// Logout
if (isset($_GET['logout'])) {
    session_start();
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
    <title>Admin - Civica Equipamentos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #333;
            color: #fff;
            padding: 10px;
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #004080;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #004080;
            color: #fff;
        }
        .btn {
            padding: 10px 20px;
            color: #fff;
            background-color: #ff6600;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #cc5200;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group input[type="file"] {
            padding: 3px;
        }
        .actions {
            margin-top: 20px;
        }
        .actions a {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Admin - Civica Equipamentos</h1>
        <a href="admin.php?logout" class="btn">Logout</a>
    </header>
    <div class="container">
        <h2>Adicionar Produto</h2>
        <form action="admin.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="preco">Preço:</label>
                <input type="number" id="preco" name="preco" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="imagem">Imagem:</label>
                <input type="file" id="imagem" name="imagem">
            </div>
            <div class="form-group">
                <label for="categorias">Categorias:</label>
                <?php
                $categorias_result = $conn->query("SELECT * FROM categorias");
                while ($categoria = $categorias_result->fetch_assoc()):
                ?>
                    <div>
                        <input type="checkbox" id="categoria_<?php echo $categoria['id']; ?>" name="categorias[]" value="<?php echo $categoria['id']; ?>">
                        <label for="categoria_<?php echo $categoria['id']; ?>"><?php echo $categoria['nome']; ?></label>
                    </div>
                <?php endwhile; ?>
            </div>
            <button type="submit" name="adicionar" class="btn">Adicionar Produto</button>
        </form>

        <h2>Produtos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Imagem</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $produtos_result = $conn->query("SELECT * FROM produtos");
                while ($produto = $produtos_result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $produto['id']; ?></td>
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['descricao']); ?></td>
                        <td>€<?php echo htmlspecialchars($produto['preco']); ?></td>
                        <td><img src="images/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" style="width: 100px;"></td>
                        <td>
                            <a href="editar.php?id=<?php echo $produto['id']; ?>" class="btn">Editar</a>
                            <a href="admin.php?delete=<?php echo $produto['id']; ?>" class="btn" onclick="return confirm('Tem certeza de que deseja excluir este produto?')">Deletar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
