<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bem-vindo ao nosso site! Confira o nosso estoque de equipamentos, máquinas e ferramentas para construção civil e obras públicas, com preços imbatíveis.">
    <meta name="keywords" content="maquinaria, equipamento, construção, exportação, importação, aluguel, máquinas, ferramentas, obras públicas">
    <meta name="author" content="Civica Engenharia">
    <title>Civica Equipamentos - Oficial Webpage</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/index.css">
</head>
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
                    <a class="nav-link" href="index.php">Início <span class="sr-only">(página atual)</span></a>
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
        <div class="row">
            <!-- Grelha Lateral para Categorias -->
            <div class="col-md-3">
                <h4>Categorias</h4>
                <ul class="list-group">
                    <?php
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
                            // Resetar o ponteiro do resultado para reusar a variável
                            $categorias_result->data_seek(0);
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

                        $sql = "SELECT p.id, p.nome, p.descricao, p.preco, p.categoria_id, i.imagem 
                                FROM produtos p
                                LEFT JOIN imagens i ON p.id = i.produto_id
                                WHERE i.imagem IS NOT NULL";

                        if ($search) {
                            $search = $conn->real_escape_string($search);
                            $sql .= " AND p.nome LIKE '%$search%'";
                        }

                        if ($categoria_id) {
                            $categoria_id = intval($categoria_id);
                            $sql .= " AND p.categoria_id = $categoria_id";
                        }

                        $sql .= " GROUP BY p.id ORDER BY $order_by";

                        return $conn->query($sql);
                    }

                    // Processar filtros e ordenação
                    $search = isset($_GET['search']) ? $_GET['search'] : '';
                    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'recent';
                    $categoria_id = isset($_GET['categoria']) ? $_GET['categoria'] : null;

                    $produtos = get_produtos($search, $sort_by, $categoria_id);

                    // Verificar se há produtos
                    if ($produtos && $produtos->num_rows > 0) {
                        // Exibir os produtos
                        while ($produto = $produtos->fetch_assoc()) {
                            $produto_id = htmlspecialchars($produto['id']);
                            $produto_nome = htmlspecialchars($produto['nome']);
                            $produto_descricao = htmlspecialchars($produto['descricao']);
                            $produto_preco = htmlspecialchars($produto['preco']);
                            $imagem_url = htmlspecialchars($produto['imagem']);
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <img src="<?php echo 'images/' . $imagem_url; ?>" class="card-img-top" alt="<?php echo $produto_nome; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $produto_nome; ?></h5>
                                <p class="card-text"><?php echo $produto_descricao; ?></p>
                                <p class="card-text"><strong>Preço:</strong> <?php echo $produto_preco; ?>€</p>
                                <a href="produto.php?id=<?php echo htmlspecialchars($produto_id); ?>" class="btn btn-primary">Mais Detalhes</a>
                            </div>
                        </div>
                    </div>
                    <?php 
                        }
                    } else {
                        echo '<div class="col-md-12">Nenhum produto encontrado.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <div class="fim" style="background-color: #333; margin-top: 8vw">
        <footer id="contacto" style="background-color: #333; color: #ffffff;">
            <div class="container" style="max-width: 1300px; padding: 10px 20px;">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <h5>Sobre Nós</h5>
                        <p>Bem-vindo à Civica, onde oferecemos a melhor maquinaria e equipamentos para construção civil e obras públicas.</p>
                    </div>
                    <div class="col-md-4 mb-4">
                        <h5>Contacte-nos</h5>
                        <p>Email: info@civica.com</p>
                        <p>Telefone: +351 912 345 678</p>
                    </div>
                    <div class="col-md-4 mb-4">
                        <h5>Siga-nos</h5>
                        <p><a href="#" style="color: #ffffff;">Facebook</a></p>
                        <p><a href="#" style="color: #ffffff;">Instagram</a></p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts do Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>