<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Principal da Loja</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 1600px; /* Ajuste o valor conforme necessário */
        }
        .card-img-top {
            width: 100%;
            object-fit: cover;
        }
        .card {
            width: 100%; /* Ajusta a largura do card para ocupar o máximo possível */
            width: 300px; /* Ajuste a largura máxima do card conforme necessário */
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
        /* Ajuste o padding para evitar que o conteúdo fique muito próximo da borda */
        .container {
            padding-left: 0;
            padding-right: 0;
        }
        /* Ajusta o layout para garantir que as colunas não fiquem uma em cima da outra */
        .row {
            margin-left: 0;
            margin-right: 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
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
            <form class="form-inline my-2 my-lg-0">
                <input class="form-control form-control-sm mr-sm-2" type="search" placeholder="Buscar" aria-label="Search">
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
                        <li class="list-group-item"><?php echo $categoria_nome; ?></li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <h2>Produtos</h2>
                <div class="form-group">
                    <label for="sortByName">Ordenar por:</label>
                    <select class="form-control form-control-sm" id="sortByName">
                        <option value="name">Nome</option>
                        <option value="price">Preço</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filterCategory">Categoria:</label>
                    <select class="form-control form-control-sm" id="filterCategory">
                        <!-- Categorias serão carregadas da base de dados -->
                        <option value="">Todas</option>
                        <?php
                        $categorias_result->data_seek(0); // Resetar o ponteiro do resultado para reusar a variável
                        while ($categoria = $categorias_result->fetch_assoc()): 
                            $categoria_id = htmlspecialchars($categoria['id']);
                            $categoria_nome = htmlspecialchars($categoria['nome']);
                        ?>
                            <option value="<?php echo $categoria_id; ?>"><?php echo $categoria_nome; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="row">
                    <?php
                    // Função para obter os produtos recentes com suas imagens
                    function get_produtos_recentes() {
                        global $conn;
                        $sql = "SELECT p.id, p.nome, p.descricao, p.preco, i.imagem 
                                FROM produtos p
                                LEFT JOIN imagens i ON p.id = i.produto_id
                                WHERE i.imagem IS NOT NULL
                                ORDER BY p.id DESC
                                LIMIT 8"; // Ajuste o limite conforme necessário
                        return $conn->query($sql);
                    }

                    // Obtém os produtos recentes
                    $produtos_recentes = get_produtos_recentes();
                    while ($produto = $produtos_recentes->fetch_assoc()): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card">
                                <img src="images/<?php echo htmlspecialchars($produto['imagem']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                <div class="card-body">
                                    <p class="card-categories">Categoria 1, Categoria 2</p> <!-- Adicione as categorias aqui -->
                                    <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                    <p class="card-text"><strong>R$ <?php echo htmlspecialchars($produto['preco']); ?></strong></p>
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
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
