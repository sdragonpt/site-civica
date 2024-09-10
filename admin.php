<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Função para lidar com a exclusão de produtos
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Função para adicionar produtos
if (isset($_POST['add'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    
    // Manipula o upload de imagem
    $imagem = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "images/";
        $target_file = $target_dir . basename($_FILES["imagem"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["imagem"]["tmp_name"]);
        
        // Verifica se o arquivo é uma imagem
        if ($check !== false) {
            if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_file)) {
                $imagem = basename($_FILES["imagem"]["name"]);
            } else {
                echo "Desculpe, ocorreu um erro ao fazer upload da imagem.";
            }
        } else {
            echo "O arquivo não é uma imagem.";
        }
    }

    $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, imagem) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $nome, $descricao, $preco, $imagem);
    $stmt->execute();
    $stmt->close();
}

// Função para editar produtos
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    // Manipula o upload de imagem
    $imagem = $_POST['existing_imagem']; // Mantém a imagem existente
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "images/";
        $target_file = $target_dir . basename($_FILES["imagem"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["imagem"]["tmp_name"]);
        
        // Verifica se o arquivo é uma imagem
        if ($check !== false) {
            if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_file)) {
                $imagem = basename($_FILES["imagem"]["name"]);
            } else {
                echo "Desculpe, ocorreu um erro ao fazer upload da imagem.";
            }
        } else {
            echo "O arquivo não é uma imagem.";
        }
    }

    $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, imagem = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $nome, $descricao, $preco, $imagem, $id);
    $stmt->execute();
    $stmt->close();
}

// Função para procurar produtos
$search = '';
if (isset($_POST['search'])) {
    $search = $_POST['search'];
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE nome LIKE ?");
    $stmt->bind_param("s", $search);
    $search = "%$search%";
} else {
    $stmt = $conn->prepare("SELECT * FROM produtos");
}

// Executa a consulta
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
            background-color: #ff6600;
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
        .delete-button {
            background-color: #ff0000;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-button:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1>Gerenciar Produtos</h1>
        
        <!-- Formulário para adicionar produtos -->
        <h2>Adicionar Produto</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="nome" placeholder="Nome" required>
                <textarea name="descricao" placeholder="Descrição"></textarea>
                <input type="text" name="preco" placeholder="Preço" required>
                <input type="file" name="imagem">
            </div>
            <button type="submit" name="add">Adicionar Produto</button>
        </form>

        <!-- Formulário para editar produtos -->
        <h2>Editar Produto</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="id" placeholder="ID do Produto" required>
                <input type="text" name="nome" placeholder="Nome">
                <textarea name="descricao" placeholder="Descrição"></textarea>
                <input type="text" name="preco" placeholder="Preço">
                <input type="file" name="imagem">
                <input type="hidden" name="existing_imagem" value="<?php echo isset($row['imagem']) ? htmlspecialchars($row['imagem']) : ''; ?>">
            </div>
            <button type="submit" name="edit">Editar Produto</button>
        </form>

        <!-- Formulário para procurar produtos -->
        <h2>Procurar Produtos</h2>
        <form method="post" action="">
            <div class="form-group">
                <input type="text" name="search" placeholder="Nome do Produto">
            </div>
            <button type="submit" name="search">Buscar</button>
        </form>

        <!-- Tabela de produtos -->
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
                <?php while ($row = $produtos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                    <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                    <td><?php echo htmlspecialchars($row['preco']); ?></td>
                    <td><img src="uploads/<?php echo htmlspecialchars($row['imagem']); ?>" alt="<?php echo htmlspecialchars($row['nome']); ?>" style="width: 100px;"></td>
                    <td>
                        <!-- Formulário para deletar produto -->
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <button type="submit" name="delete" class="delete-button">Deletar</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
