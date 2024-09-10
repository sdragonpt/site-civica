<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bem-vindo ao nosso site! Confira o nosso estoque de equipamentos, máquinas e ferramentas para construção civil e obras públicas, com preços imbatíveis.">
    <meta name="keywords" content="maquinaria, equipamento, construção, exportação, importação, aluguel, máquinas, ferramentas, obras públicas">
    <meta name="author" content="Civica Engenharia">
    <script src="https://kit.fontawesome.com/753ec85429.js" crossorigin="anonymous"></script>
    <title>Civica Equipamentos - Oficial Webpage</title>
    
    <style>
        /* Estilo para rolagem suave */
        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            text-align: center;
        }

        #container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 20px 0px 20px;
        }

        header {
            background-color: #f4f4f4;
            color: #fff;
            padding-top: 10px;
        }

        header img {
            max-width: 400px;
            float: left;
        }

        nav {
            margin: 30px 0;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 15px;
            padding: 10px;
            background-color: #ff6600;
            border-radius: 4px;
        }

        nav a:hover {
            background-color: #cc5200;
        }

        .content {
            margin-top: 26px;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .content h2 {
            color: #004080;
        }

        .content img {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
        }

        .product-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }

        .product-item {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin: 10px;
            width: 22%; /* Ajuste para caber 4 cards por linha */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative; /* Para posicionar o botão */
        }

        .product-item img {
            max-width: 200px;
            height: auto; /* Mantém a proporção da imagem */
            object-fit: cover; /* Ajusta a imagem ao tamanho do contêiner */
        }

        .product-item h3 {
            font-size: 18px;
            margin: 10px 0;
        }

        .product-item p {
            font-size: 16px;
        }

        .product-item button {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: #ff6600;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px;
            cursor: pointer;
        }

        .product-item button:hover {
            background-color: #cc5200;
        }

        footer {
            margin-top: 20px;
            background-color: #333;
            color: white;
            padding: 20px;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        footer .footer-section {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            text-align: left;
            padding: 20px;
        }

        footer .footer-section div {
            flex: 1;
            margin: 10px;
        }

        footer .footer-section div h3 {
            color: #ffcc00;
        }

        footer p {
            margin: 5px 0;
            font-size: 15px;
        }

        footer .social {
            text-align: center;
            margin-top: 20px;
        }

        footer .social a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            header img {
                max-width: 200px;
            }

            nav {
                display: flex;
                flex-direction: column;
            }

            nav a {
                margin: 10px 0;
            }

            footer .footer-section {
                flex-direction: column;
            }

            footer .social {
                margin-top: 40px;
            }

            /* Estilo para botões de categorias */
            .category-buttons {
                margin-top: 20px;
            }

            .category-buttons a {
                display: inline-block;
                padding: 10px 20px;
                margin: 5px;
                background-color: #ff6600;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
            }

            .category-buttons a:hover {
                background-color: #cc5200;
            }
        }
    </style>
</head>
<body>
    <div id="container">
        <!-- Cabeçalho -->
        <header>
            <img src="images/Logotipo Civica 2019 - Ver1.png" alt="Logotipo Civica">
            <h1>Civica Equipamentos</h1>
            <p>Venda e Aluguer de Máquinas e Equipamentos</p>
            <hr style="margin-bottom: 0px">
        </header>

        <!-- Navegação -->
        <nav>
            <a href="#categorias">Categorias</a>
            <a href="#contacto">Contacto</a>
        </nav>

        <!-- Conteúdo Principal -->
        <?php
        include('config.php'); // Inclua o arquivo de configuração do banco de dados

        // Função para obter os produtos recentes com suas imagens
        function get_produtos_recentes() {
            global $conn;
            $sql = "SELECT p.id, p.nome, p.descricao, p.preco, i.imagem 
                    FROM produtos p
                    LEFT JOIN imagens i ON p.id = i.produto_id
                    WHERE i.imagem IS NOT NULL
                    ORDER BY p.id DESC
                    LIMIT 5"; // Ajuste o limite conforme necessário
            return $conn->query($sql);
        }

        // Obtém os produtos recentes
        $produtos_recentes = get_produtos_recentes();
        ?>

        <div class="content">
            <h2>Últimos Produtos</h2>
            <div class="product-list">
                <?php while ($produto = $produtos_recentes->fetch_assoc()): ?>
                    <div class="product-item">
                        <img src="images/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                        <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                        <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
                        <p>Preço: €<?php echo htmlspecialchars($produto['preco']); ?></p>
                        <button>Escolher Ação</button>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Botões de categorias -->
        <div class="category-buttons">
            <?php
            // Obter todas as categorias
            $categorias_result = $conn->query("SELECT * FROM categorias");
            while ($categoria = $categorias_result->fetch_assoc()): 
                $categoria_nome = htmlspecialchars($categoria['nome']);
                $categoria_id = htmlspecialchars($categoria['id']);
            ?>
                <a href="#<?php echo $categoria_nome; ?>"><?php echo $categoria_nome; ?></a>
            <?php endwhile; ?>
        </div>

        <!-- Produtos por Categorias -->
        <?php
        // Exibir produtos por categorias
        while ($categoria = $categorias_result->fetch_assoc()): 
            $categoria_id = $categoria['id'];
            $categoria_nome = $categoria['nome'];

            // Obter produtos para a categoria atual
            $stmt = $conn->prepare("
                SELECT p.* 
                FROM produtos p
                INNER JOIN produto_categoria pc ON p.id = pc.produto_id
                WHERE pc.categoria_id = ?
            ");
            $stmt->bind_param("i", $categoria_id);
            $stmt->execute();
            $produtos_result = $stmt->get_result();
        ?>
            <div class="content" id="<?php echo htmlspecialchars($categoria_nome); ?>">
                <h2><?php echo htmlspecialchars($categoria_nome); ?></h2>
                <div class="product-list">
                    <?php while ($produto = $produtos_result->fetch_assoc()): ?>
                        <div class="product-item">
                            <img src="images/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                            <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                            <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
                            <p>Preço: €<?php echo htmlspecialchars($produto['preco']); ?></p>
                            <button>Escolher Ação</button>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endwhile; ?>

        <!-- Rodapé -->
        <footer id="contacto">
            <div class="footer-section">
                <div class="contact-info" style="margin-right: -5em;">
                    <h3>Contacto</h3>
                    <p>Tel/Fax: +351 259 351 024</p>
                    <p>Móvel: +351 967 571 033</p>
                    <p>WhatsApp: +351 967 571 033</p>
                    <p>Email: <a href="mailto:civica@civica.pt" style="color: #ffcc00;">civica@civica.pt</a></p>
                </div>

                <div class="location">
                    <h3>Localização</h3>
                    <p>Zona Industrial de Constantim, Lote 143 e 144</p>
                    <p>5000-082 Vila Real, Portugal</p>
                    <p>GPS: Lat. 41°16'43'' N - Long. 7°42'22'' W</p>
                </div>

                <div class="about">
                    <h3>Sobre Nós</h3>
                    <p>Especializados na importação, exportação e comercialização de maquinaria e equipamentos.</p>
                </div>
            </div>

            <div class="social">
                <a href="#"><i class="fa-brands fa-facebook" style="margin-right: 6px;"></i>Facebook</a>
                <a href="#"><i class="fa-brands fa-instagram" style="margin-right: 6px;"></i>Instagram</a>
                <p>&copy; 2024 Civica - Todos os direitos reservados</p>
            </div>
        </footer>
    </div>
</body>
</html>

                           
