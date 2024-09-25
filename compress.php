<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$mensagem = '';
if (isset($_POST['compress'])) {
    $target_dir = "compressed_images/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $total = count($_FILES['imagens']['name']);
    $compressed_files = [];

    for ($i = 0; $i < $total; $i++) {
        $temp_file = $_FILES['imagens']['tmp_name'][$i];
        $imageFileType = strtolower(pathinfo($_FILES['imagens']['name'][$i], PATHINFO_EXTENSION));
        $unique_name = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $unique_name;

        if (resize_and_compress_image($temp_file, $target_file)) {
            $compressed_files[] = $unique_name;
        }
    }

    if (!empty($compressed_files)) {
        $mensagem = "<div class='alert alert-success'>Imagens comprimidas com sucesso!</div>";
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao comprimir as imagens.</div>";
    }
}

function resize_and_compress_image($source_path, $target_path, $max_width = 800, $max_height = 600, $quality = 75) {
    list($width, $height, $type) = getimagesize($source_path);
    $ratio = $width / $height;

    if ($width > $height) {
        $new_width = min($max_width, $width);
        $new_height = $new_width / $ratio;
    } else {
        $new_height = min($max_height, $height);
        $new_width = $new_height * $ratio;
    }

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

    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($image_p, $target_path, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($image_p, $target_path, 6); // PNG compression level
            break;
        case IMAGETYPE_GIF:
            imagegif($image_p, $target_path);
            break;
    }

    imagedestroy($image);
    imagedestroy($image_p);

    return true;
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compressão de Imagens</title>
    <link rel="stylesheet" href="./css/admin.css">
</head>
<body>
    <div class="compress-container">
        <h1>Compressão de Imagens</h1>

        <?php if ($mensagem): ?>
            <?php echo $mensagem; ?>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="file" name="imagens[]" multiple required>
            <button type="submit" name="compress">Comprimir Imagens</button>
        </form>

        <h2>Imagens Comprimidas</h2>
        <div id="progress" style="display: none;">
            <div id="progress-bar" style="width: 0%; background: green; height: 20px;"></div>
        </div>
    </div>
</body>
</html>
