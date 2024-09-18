<?php
include('config.php'); // Inclua o arquivo de configuração do banco de dados

// Verifica se o ID do produto foi passado via GET
if (isset($_GET['id'])) {
    $produto_id = intval($_GET['id']);
    
    // Obtém as informações do produto
    $produto_result = $conn->query("SELECT * FROM produtos WHERE id = $produto_id");
    $produto = $produto_result->fetch_assoc();
    
    // Obtém as imagens do produto
    $imagens_result = $conn->query("SELECT imagem FROM imagens WHERE produto_id = $produto_id");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Detalhes do produto">
    <title><?php echo htmlspecialchars($produto['nome']); ?> - Detalhes</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: #f8f9fa; /* Cor de fundo mais escura */
        }
        .container {
            max-width: 1600px; /* Ajuste o valor conforme necessário */
        }
        .card-img-top {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .card {
            width: 100%; /* Ajusta a largura do card para ocupar o máximo possível */
            max-width: 300px; /* Ajuste a largura máxima do card conforme necessário */
        }
        .navbar-brand img {
            height: 60px; /* Ajuste o tamanho da imagem conforme necessário */
        }
        .navbar-nav {
            flex-direction: row;
        }
        .form-control-sm {
            width: auto;
            display: inline-block;
        }
        .list-group {
            margin-right: 10px; /* Ajuste a margem conforme necessário */
        }
        .card-categories {
            font-size: 0.875rem; /* Tamanho da fonte menor para as categorias */
            color: #6c757d; /* Cor do texto das categorias (opcional) */
        }
        .container {
            padding-left: 0;
            padding-right: 0;
        }
        .row {
            margin-left: 0;
            margin-right: 0;
        }
        .navbar-light .navbar-nav .nav-link {
            color: #ffffff; /* Cor do texto dos itens de menu */
        }
        .form-control-sm, .btn-outline-success {
            color: #343a40; /* Cor do texto da busca e botão */
            border-color: #343a40 !important;
        }
        .btn-outline-success {
            border-color: #343a40; /* Cor da borda do botão */
        }
        .btn-outline-success:hover {
            background-color: #343a40; /* Cor de fundo ao passar o mouse */
            color: #ffffff; /* Cor do texto ao passar o mouse */
        }

        a.btn.btn-primary {
            background-color: #343a40;
            border-color: #343a40 !important;
        }

        a.btn.btn-primary:hover{
            background-color: #555;
            border-color: #555 !important;
        }

        a.nav-link{
            font-size: 20px;
            font-weight: bold;
        }

        a.nav-link:hover{
            font-weight: bold;
            color: #555 !important;
        }

        /* Estilos do Rodapé */
        .footer-section {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            background-color: #333;
            color: #ffffff;
        }
        .footer-section h3 {
            margin-top: 0;
        }
        .footer-section p {
            margin: 0;
        }
        .social {
            text-align: center;
            padding: 20px;
            background-color: #212529;
            color: #ffffff;
        }
        .social a {
            color: #ffcc00;
            margin: 0 10px;
        }
        .social i {
            margin-right: 6px;
        }

        li.list-group-item{
            color: #333 !important;
        }
    </style>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="index.php">
            <img src="images/Logotipo Civica 2019 - Ver1.png" alt="Civica">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="index.html">Início <span class="sr-only">(página atual)</span></a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="#contacto">Contactos <span class="sr-only">(página atual)</span></a>
                </li>
                <!-- Adicione mais itens de menu conforme necessário -->
            </ul>
            <form class="form-inline my-2 my-lg-0" action="index.php" method="GET">
                <input class="form-control form-control-sm mr-sm-2" type="search" name="search" placeholder="Buscar" aria-label="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button class="btn btn-outline-success btn-sm my-2 my-sm-0" type="submit">Buscar</button>
            </form>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($produto): ?>
            <h2><?php echo htmlspecialchars($produto['nome']); ?></h2>
            <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
            <p><strong><?php echo htmlspecialchars($produto['preco']); ?> €</strong></p>
            
            <!-- Galeria de Imagens -->
            <div class="row">
                <?php while ($imagem = $imagens_result->fetch_assoc()): ?>
                    <div class="col-md-3 mb-4">
                        <img src="images/<?php echo htmlspecialchars($imagem['imagem']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Produto não encontrado.</p>
        <?php endif; ?>
    </div>

    <!-- Rodapé -->
    <div class="fim" style="background-color: #333; margin-top: 8vw">
        <footer id="contacto" style="background-color: #333; color: #ffffff;">
            <div class="container" style="max-width: 1300px; padding: 20px 0;">
                <div class="footer-section row">
                    <div class="contact-info col-md-4">
                        <h3>Contacto</h3>
                        <p>Tel/Fax: +351 259 351 024</p>
                        <p>Móvel: +351 967 571 033</p>
                        <p>WhatsApp: +351 967 571 033</p>
                        <p>Email: <a href="mailto:civica@civica.pt" style="color: #ffcc00;">civica@civica.pt</a></p>
                    </div>

                    <div class="location col-md-4">
                        <h3>Localização</h3>
                        <p>Zona Industrial de Constantim, Lote 143 e 144</p>
                        <p>5000-082 Vila Real, Portugal</p>
                        <p>GPS: Lat. 41°16'43'' N - Long. 7°42'22'' W</p>
                    </div>

                    <div class="about col-md-4">
                        <h3>Sobre Nós</h3>
                        <p>Cívica - Construções, Engenharia e Equipamentos, Lda</p>
                        <p>Sociedade por Quotas</p>
                        <p>Capital Social 100.000,00€</p>
                        <p>NIF/EORI: PT 504 117 246</p>
                        <p>Alvará: nº 43194</p>
                    </div>
                </div>
            </div>

            <!-- Seção Social -->
            <div class="social" style="background-color: #222; color: #ffffff; padding: 20px 0; text-align: center;">
                <a href="#" style="color: #ffcc00; margin-right: 6px;"><i class="fa-brands fa-facebook"></i> Facebook</a>
                <a href="#" style="color: #ffcc00; margin-right: 6px;"><i class="fa-brands fa-instagram"></i> Instagram</a>
                <p class="mt-2">&copy; 2024 Civica - Todos os direitos reservados</p>
            </div>
        </footer>
    </div>


    <!-- Bootstrap JS, Popper.js, e jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
