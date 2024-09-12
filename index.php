<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Principal da Loja</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
        .navbar-light {
            background-color: #343a40; /* Cor de fundo escura para a navbar */
        }
        .navbar-light .navbar-nav .nav-link {
            color: #ffffff; /* Cor do texto dos itens de menu */
        }
        .form-control-sm, .btn-outline-success {
            color: #343a40; /* Cor do texto da busca e botão */
        }
        .btn-outline-success {
            border-color: #343a40; /* Cor da borda do botão */
        }
        .btn-outline-success:hover {
            background-color: #343a40; /* Cor de fundo ao passar o mouse */
            color: #ffffff; /* Cor do texto ao passar o mouse */
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="#">
            <img src="images/Logotipo Civica 2019 - Ver1.png" alt="Civica">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="#">Início <span class="sr-only">(página atual)</span></a>
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
        <div class="row">
            <!-- Grelha Lateral para Categorias -->
            <div class="col-md-3">
                <h4>Categorias</h4>
                <ul class="list-group">
                    <?php
                    include('config.php'); // Inclua o arquivo de configuração do banco de dados

                    // Obter todas as categorias
                    $categorias_result = $conn->query("SELECT * FROM categorias");
                    while ($categoria = $categorias_result->fetch_assoc()): 
                        $categoria_nome = htmlspecialchars($categoria['nome']);
                    ?>
                        <li class="list-group-item">
                            <a href="?categoria=<?php echo htmlspecialchars($categoria['id']); ?>">
                                <?php echo $categoria_nome; ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <h2>Produtos</h2>
                <form method="GET" id="filtersForm">
                    <div class="form-group">
                        <label for="sortByName">Ordenar por:</label>
                        <select class="form-control form-control-sm" id="sortByName" name="sort">
                            <option value="recent" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'recent' ? 'selected' : ''; ?>>Recente</option>
                            <option value="name" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'name' ? 'selected' : ''; ?>>Nome</option>
                            <option value="price" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'price' ? 'selected' : ''; ?>>Preço</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filterCategory">Categoria:</label>
                        <select class="form-control form-control-sm" id="filterCategory" name="categoria">
                            <option value="">Todas</option>
                            <?php
                            $categorias_result->data_seek(0); // Resetar o ponteiro do resultado para reusar a variável
                            while ($categoria = $categorias_result->fetch_assoc()): 
                                $categoria_id = htmlspecialchars($categoria['id']);
                                $categoria_nome = htmlspecialchars($categoria['nome']);
                            ?>
                                <option value="<?php echo $categoria_id; ?>" <?php echo isset($_GET['categoria']) && $_GET['categoria'] == $categoria_id ? 'selected' : ''; ?>>
                                    <?php echo $categoria_nome; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>

                <div class="row">
                    <?php
                    // Função para obter os produtos com base nos filtros e ordenação
                    function get_produtos($search = '', $sort_by = 'recent', $categoria_id = null) {
                        global $conn;
                        $order_by = 'p.id DESC';
                        if ($sort_by === 'name') {
                            $order_by = 'p.nome ASC';
                        } elseif ($sort_by === 'price') {
                            $order_by = 'p.preco ASC';
                        }

                        $sql = "SELECT p.id, p.nome, p.descricao, p.preco, i.imagem 
                                FROM produtos p
                                LEFT JOIN imagens i ON p.id = i.produto_id
                                WHERE i.imagem IS NOT NULL";
                        
                        if ($search) {
                            $search = $conn->real_escape_string($search);
                            $sql .= " AND p.nome LIKE '%$search%'";
                        }
                        
                        if ($categoria_id) {
                            $sql .= " AND p.id IN (SELECT produto_id FROM produto_categoria WHERE categoria_id = $categoria_id)";
                        }
                        
                        $sql .= " ORDER BY $order_by
                                LIMIT 8"; // Ajuste o limite conforme necessário
                        return $conn->query($sql);
                    }

                    // Função para obter as categorias de um produto específico
                    function get_categorias_por_produto($produto_id) {
                        global $conn;
                        $stmt = $conn->prepare("
                            SELECT c.nome 
                            FROM categorias c
                            INNER JOIN produto_categoria pc ON c.id = pc.categoria_id
                            WHERE pc.produto_id = ?
                        ");
                        $stmt->bind_param("i", $produto_id);
                        $stmt->execute();
                        return $stmt->get_result();
                    }

                    // Obtém os parâmetros de ordenação, pesquisa e filtro
                    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'recent';
                    $categoria_id = isset($_GET['categoria']) ? $_GET['categoria'] : null;
                    $search = isset($_GET['search']) ? $_GET['search'] : '';

                    // Obtém os produtos com base nos filtros e ordenação
                    $produtos = get_produtos($search, $sort_by, $categoria_id);
                    while ($produto = $produtos->fetch_assoc()): 
                        $produto_id = $produto['id'];
                        $categorias_result = get_categorias_por_produto($produto_id);
                        $categorias = [];
                        while ($categoria = $categorias_result->fetch_assoc()) {
                            $categorias[] = htmlspecialchars($categoria['nome']);
                        }
                        $categorias_str = implode(', ', $categorias);
                    ?>
                        <div class="col-md-3 mb-4">
                            <div class="card">
                                <img src="images/<?php echo htmlspecialchars($produto['imagem']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                <div class="card-body">
                                    <p class="card-categories"><?php echo $categorias_str; ?></p>
                                    <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                    <p class="card-text"><strong><?php echo htmlspecialchars($produto['preco']); ?> €</strong></p>
                                    <a href="#" class="btn btn-primary">Botão</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS, Popper.js, e jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Submete o formulário automaticamente ao alterar a ordenação ou a categoria
        document.getElementById('sortByName').addEventListener('change', function() {
            document.getElementById('filtersForm').submit();
        });

        document.getElementById('filterCategory').addEventListener('change', function() {
            // Limpa o campo de pesquisa ao selecionar uma nova categoria
            var searchInput = document.querySelector('input[name="search"]');
            searchInput.value = '';
            document.getElementById('filtersForm').submit();
        });
    </script>
</body>
</html>
