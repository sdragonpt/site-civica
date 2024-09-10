<?php
include('config.php'); // Inclua o arquivo de configuração do banco de dados

// Função para adicionar categorias
if (isset($_POST['add_category'])) {
    $nome_categoria = $_POST['nome_categoria'];
    $stmt = $conn->prepare("INSERT INTO categorias (nome) VALUES (?)");
    $stmt->bind_param("s", $nome_categoria);
    $stmt->execute();
    $stmt->close();
}

// Função para adicionar produtos
if (isset($_POST['add'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $categorias = isset($_POST['categorias']) ? $_POST['categorias'] : [];

    $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nome, $descricao, $preco);
    $stmt->execute();
    $produto_id = $stmt->insert_id;
    $stmt->close();

    // Associa categorias ao produto
    foreach ($categorias as $categoria_id) {
        $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $produto_id, $categoria_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Função para editar produtos
if (isset($_POST['update'])) {
    $produto_id = $_POST['produto_id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $categorias = isset($_POST['categorias']) ? $_POST['categorias'] : [];

    $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ? WHERE id = ?");
    $stmt->bind_param("ssii", $nome, $descricao, $preco, $produto_id);
    $stmt->execute();
    $stmt->close();

    // Remove categorias antigas
    $stmt = $conn->prepare("DELETE FROM produto_categoria WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->close();

    // Adiciona novas categorias
    foreach ($categorias as $categoria_id) {
        $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $produto_id, $categoria_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Função para deletar produtos
if (isset($_GET['delete'])) {
    $produto_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->close();
}

// Função para deletar categorias
if (isset($_GET['delete_category'])) {
    $categoria_id = $_GET['delete_category'];
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
    $stmt->bind_param("i", $categoria_id);
    $stmt->execute();
    $stmt->close();
}

// Obter produtos e categorias
$produtos_result = $conn->query("SELECT * FROM produtos");
$categorias_result = $conn->query("SELECT * FROM categorias");

// Obter produtos associados às categorias
if (isset($_GET['edit'])) {
    $produto_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT categoria_id FROM produto_categoria WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $categorias_produto_result = $stmt->get_result();
    $categorias_produto = [];
    while ($row = $categorias_produto_result->fetch_assoc()) {
        $categorias_produto[] = $row['categoria_id'];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $produto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração</title>
</head>
<body>
    <h1>Administração</h1>

    <!-- Adicionar Categoria -->
    <h2>Adicionar Categoria</h2>
    <form method="post" action="">
        <input type="text" name="nome_categoria" placeholder="Nome da Categoria" required>
        <button type="submit" name="add_category">Adicionar Categoria</button>
    </form>

    <!-- Adicionar Produto -->
    <h2>Adicionar Produto</h2>
    <form method="post" action="" enctype="multipart/form-data">
        <input type="text" name="nome" placeholder="Nome do Produto" required>
        <textarea name="descricao" placeholder="Descrição"></textarea>
        <input type="text" name="preco" placeholder="Preço" required>
        <input type="file" name="imagens[]" multiple>
        <select name="categorias[]" multiple>
            <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
            <?php endwhile; ?>
        </select>
        <button type="submit" name="add">Adicionar Produto</button>
    </form>

    <!-- Editar Produto -->
    <?php if (isset($produto)): ?>
        <h2>Editar Produto</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
            <input type="text" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" required>
            <textarea name="descricao"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
            <input type="text" name="preco" value="<?php echo htmlspecialchars($produto['preco']); ?>" required>
            <input type="file" name="imagens[]" multiple>
            <select name="categorias[]" multiple>
                <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo in_array($categoria['id'], $categorias_produto) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="update">Atualizar Produto</button>
        </form>
    <?php endif; ?>

    <!-- Listar Produtos -->
    <h2>Produtos</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Preço</th>
            <th>Ações</th>
        </tr>
        <?php while ($produto = $produtos_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $produto['id']; ?></td>
                <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                <td><?php echo htmlspecialchars($produto['descricao']); ?></td>
                <td><?php echo htmlspecialchars($produto['preco']); ?></td>
                <td>
                    <a href="?edit=<?php echo $produto['id']; ?>">Editar</a>
                    <a href="?delete=<?php echo $produto['id']; ?>" onclick="return confirm('Tem certeza de que deseja excluir este produto?');">Deletar</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- Listar Categorias -->
    <h2>Categorias</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Ações</th>
        </tr>
        <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $categoria['id']; ?></td>
                <td><?php echo htmlspecialchars($categoria['nome']); ?></td>
                <td>
                    <a href="?delete_category=<?php echo $categoria['id']; ?>" onclick="return confirm('Tem certeza de que deseja excluir esta categoria?');">Deletar</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>