<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Verifica se o ID da imagem foi fornecido
if (isset($_GET['id'])) {
    $imageId = intval($_GET['id']);

    // Obtém o nome do arquivo da imagem para poder excluí-lo do servidor
    $stmt = $conn->prepare("SELECT imagem FROM imagens WHERE id = ?");
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $image = $result->fetch_assoc();

    if ($image) {
        // Exclui o arquivo da imagem do servidor
        $filePath = 'images/' . $image['imagem'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Exclui a imagem do banco de dados
        $stmt = $conn->prepare("DELETE FROM imagens WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $stmt->close();
    }

    // Redireciona de volta para a página de edição
    header("Location: edit_product.php?id=" . intval($_GET['product_id']));
    exit();
} else {
    echo "ID da imagem não fornecido.";
}
?>
