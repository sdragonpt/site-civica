<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bem-vindo ao nosso site! Confira o nosso estoque de equipamentos, máquinas e ferramentas para construção civil e obras públicas, com preços imbatíveis.">
    <meta name="keywords" content="maquinaria, equipamento, construção, exportação, importação, aluguel, máquinas, ferramentas, obras públicas">
    <meta name="author" content="Civica Engenharia">
    <script src="https://kit.fontawesome.com/753ec85429.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="./css/styles.css">
    <title>Civica Equipamentos - Oficial Webpage</title>
    
    <style>
       
    </style>
</head>
<body>
    <div id="container">
        <!-- Cabeçalho -->
        <header>
            <img src="images/Logotipo Civica 2019 - Ver1.png" alt="Logotipo Civica">
            <h1 style="color: black; font-weight:bold;">Civica Equipamentos</h1>
            <p style="color: black;">Venda e Aluguer de Máquinas e Equipamentos</p>
        </header>

        <!-- Navegação -->
        <nav>
            <hr>
            <a href="#categorias">Categorias</a>
            <a href="#contacto">Contacto</a>
            <hr>
        </nav>

    </div>

        <div id="container">
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
            <h2 style="font-weight: bold;">Últimos Produtos</h2>
            <div class="product-list">
                <?php while ($produto = $produtos_recentes->fetch_assoc()): ?>
                    <div class="product-item">
                        <img src="images/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                        <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                        <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
                        <p>Preço: €<?php echo htmlspecialchars($produto['preco']); ?></p>
                        <button>Ver Mais</button> <!-- Botão para ações futuras -->
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Botões de categorias -->
        <div class="category-buttons" id="categorias">
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

        <!-- Exibir produtos por categorias -->
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
            <div class="content" id="<?php echo $categoria_nome; ?>">
                <h2 style="font-weight:bold;"><?php echo $categoria_nome; ?></h2>
                <div class="product-list">
                    <?php while ($produto = $produtos_result->fetch_assoc()): ?>
                        <div class="product-item">
                            <img src="images/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                            <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                            <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
                            <p>Preço: €<?php echo htmlspecialchars($produto['preco']); ?></p>
                            <button>Ver Mais</button> <!-- Botão para ações futuras -->
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <!-- Rodapé -->
    <div class="fim">
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
                    <p>Cívica - Construções, Engenharia e Equipamentos, Lda</p>
                    <p>Sociedade por Quotas</p>
                    <p>Capital Social 100.000,00€</p>
                    <p>NIF/EORI: PT 504 117 246</p>
                    <p>Alvará: nº 43194</p>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</html>