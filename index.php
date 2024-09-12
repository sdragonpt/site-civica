<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bem-vindo ao nosso site! Confira o nosso estoque de equipamentos, máquinas e ferramentas para construção civil e obras públicas, com preços imbatíveis.">
    <meta name="keywords" content="maquinaria, equipamento, construção, exportação, importação, aluguel, máquinas, ferramentas, obras públicas">
    <meta name="author" content="Civica Engenharia">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <title>Civica Equipamentos - Oficial Webpage</title>
    <style>
        /* Customizações adicionais */
        .hero-section {
            background-color: #f4f4f4;
            padding: 20px 0;
        }

        .product-item img {
            max-width: 100%;
            height: auto;
            object-fit: cover;
        }

        .footer-section {
            background-color: #333;
            color: black;
            padding: 20px 0; /* Adicione algum padding para garantir que o footer tenha altura suficiente */
            position: relative; /* Garantir que o footer não esteja sendo posicionado fora da tela */
            z-index: 1000; /* Para garantir que o footer fique acima de outros elementos */
        }


        .footer-section h3 {
            color: #ffcc00;
        }

        .social a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <header class="hero-section text-center mb-4">
        <div class="container">
            <img src="images/Logotipo Civica 2019 - Ver1.png" alt="Logotipo Civica" class="img-fluid">
            <h1 class="mt-3">Civica Equipamentos</h1>
            <p>Venda e Aluguer de Máquinas e Equipamentos</p>
        </div>
    </header>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Civica Equipamentos</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#categorias">Categorias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contacto">Contacto</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container">
        <section class="mb-5">
            <h2>Últimos Produtos</h2>
            <div class="row">
                <?php while ($produto = $produtos_recentes->fetch_assoc()): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card">
                            <img src="images/<?php echo htmlspecialchars($produto['imagem']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                <p class="card-text">Preço: €<?php echo htmlspecialchars($produto['preco']); ?></p>
                                <a href="#" class="btn btn-primary">Ver Mais</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="mb-5" id="categorias">
            <h2>Categorias</h2>
            <div class="btn-group" role="group" aria-label="Categorias">
                <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                    <a href="#<?php echo htmlspecialchars($categoria['nome']); ?>" class="btn btn-warning"><?php echo htmlspecialchars($categoria['nome']); ?></a>
                <?php endwhile; ?>
            </div>
        </section>

        <?php
        $categorias_result->data_seek(0); // Resetar o ponteiro do resultado para reusar a variável

        while ($categoria = $categorias_result->fetch_assoc()): 
            $categoria_id = $categoria['id'];
            $categoria_nome = htmlspecialchars($categoria['nome']);

            // Obter produtos para a categoria atual
            $stmt = $conn->prepare("
                SELECT p.*, i.imagem 
                FROM produtos p
                INNER JOIN produto_categoria pc ON p.id = pc.produto_id
                LEFT JOIN imagens i ON p.id = i.produto_id
                WHERE pc.categoria_id = ?
                AND i.imagem IS NOT NULL
            ");
            $stmt->bind_param("i", $categoria_id);
            $stmt->execute();
            $produtos_result = $stmt->get_result();
        ?>
            <section class="mb-5" id="<?php echo $categoria_nome; ?>">
                <h2><?php echo $categoria_nome; ?></h2>
                <div class="row">
                    <?php while ($produto = $produtos_result->fetch_assoc()): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card">
                                <img src="images/<?php echo htmlspecialchars($produto['imagem']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                    <p class="card-text">Preço: €<?php echo htmlspecialchars($produto['preco']); ?></p>
                                    <a href="#" class="btn btn-primary">Ver Mais</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endwhile; ?>
    </main>

    <footer class="footer-section text-white text-center pt-4 pb-2">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h3>Contacto</h3>
                    <p>Tel/Fax: +351 259 351 024</p>
                    <p>Móvel: +351 967 571 033</p>
                    <p>WhatsApp: +351 967 571 033</p>
                    <p>Email: <a href="mailto:civica@civica.pt" class="text-warning">civica@civica.pt</a></p>
                </div>
                <div class="col-md-4 mb-4">
                    <h3>Localização</h3>
                    <p>Zona Industrial de Constantim, Lote 143 e 144</p>
                    <p>5000-082 Vila Real, Portugal</p>
                    <p>GPS: Lat. 41°16'43'' N - Long. 7°42'22'' W</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h3>Sobre Nós</h3>
                    <p>Cívica - Construções, Engenharia e Equipamentos, Lda</p>
                    <p>Sociedade por Quotas</p>
                    <p>Capital Social 100.000,00€</p>
                    <p>NIF/EORI: PT 504 117 246</p>
                    <p>Alvará: nº 43194</p>
                </div>
            </div>
            <div class="social mt-3">
                <a href="#" class="me-2"><i class="fa-brands fa-facebook"></i> Facebook</a>
                <a href="#" class="me-2"><i class="fa-brands fa-instagram"></i> Instagram</a>
                <p>&copy; 2024 Civica - Todos os direitos reservados</p>
            </div>
        </div>
    </footer>

</body>
</html>