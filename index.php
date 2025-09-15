<?php
session_start();

// Configuração da conexão com o banco de dados
$servername = "sites_sql";
$username = "mysql";
$password = "Miguel@18032018";
$dbname = "sites";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Lógica de roteamento
$page = $_GET['page'] ?? 'home';
$is_authenticated = isset($_SESSION['user_id']);
$user_name = $is_authenticated ? $_SESSION['user_name'] : '';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leilão Online - Seu Site de Oportunidades</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <script>
        const IS_AUTHENTICATED = <?php echo json_encode($is_authenticated); ?>;
        const USER_NAME = <?php echo json_encode($user_name); ?>;
    </script>
</head>

<body>

    <!-- Cabeçalho -->
    <header class="header-bg py-4 px-6 shadow-md">
        <div class="container flex justify-between items-center">
            <h1 class="text-3xl font-bold">Leilão Online</h1>
            <div class="flex-grow max-w-xl mx-8">
                <input type="text" placeholder="Buscar produtos, marcas e mais..." class="search-input">
            </div>
            <nav id="header-nav" class="flex items-center">
                <?php if ($is_authenticated): ?>
                <a href="?page=vender" id="sell-button-nav" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-full transition duration-300 mr-4">Vender</a>
                <a href="?page=compras" id="my-bids-button" class="text-white hover:underline mr-4">Minhas Compras</a>
                <a href="?page=vendas" id="my-sales-button" class="text-white hover:underline mr-4">Minhas Vendas</a>
                <span id="user-info-nav" class="text-white font-medium mr-4">Olá, <?php echo htmlspecialchars($user_name); ?></span>
                <button id="logout-button-nav" class="text-white hover:underline">Sair</button>
                <?php else: ?>
                <a href="#" id="sell-button-nav" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-full transition duration-300 mr-4">Vender</a>
                <a href="#" id="my-bids-button" class="text-white hover:underline mr-4">Minhas Compras</a>
                <a href="#" id="my-sales-button" class="text-white hover:underline mr-4">Minhas Vendas</a>
                <a href="#" id="login-button-nav" class="text-white hover:underline mr-4">Entrar</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Botão de Voltar -->
    <button id="back-button" class="back-button hidden bg-gray-700 text-white p-2 rounded-full shadow-md hover:bg-gray-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </button>

    <!-- Conteúdo Principal -->
    <main id="main-content" class="container mt-8">
        <?php if ($page === 'home'): ?>
        <section class="hero-section mb-12">
            <h2 class="text-3xl md:text-5xl font-extrabold mb-4">O Leilão de Oportunidades é Agora!</h2>
            <p class="text-lg md:text-xl text-gray-300">Encontre itens únicos a preços incríveis. Dê seu lance e não perca tempo!</p>
        </section>
        <section class="mb-8">
            <h2 class="text-2xl font-bold text-gray-200 mb-4">Leilões em Destaque</h2>
            <div id="loading" class="text-center text-gray-400 text-lg mt-8">Carregando leilões...</div>
            <div id="auctions-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"></div>
        </section>
        <?php elseif ($page === 'vender'): ?>
        <div id="vender" class="container mt-8 p-8 bg-gray-800 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-gray-200 mb-6">Anunciar Novo Produto</h2>
            <div class="mb-4">
                <label for="product-name" class="block text-gray-400 font-semibold mb-2">Nome do Produto</label>
                <input type="text" id="product-name" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200">
            </div>
            <div class="mb-4">
                <label for="product-description" class="block text-gray-400 font-semibold mb-2">Descrição</label>
                <textarea id="product-description" rows="4" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200"></textarea>
            </div>
            <div class="mb-4">
                <label for="product-category" class="block text-gray-400 font-semibold mb-2">Categoria</label>
                <select id="product-category" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200">
                    <option value="">Selecione uma categoria...</option>
                    <option value="brinquedos">Brinquedos</option>
                    <option value="celulares">Celulares</option>
                    <option value="computadores">Computadores</option>
                    <option value="video-games">Video-Games</option>
                    <option value="carros">Carros</option>
                    <option value="casas">Casas</option>
                    <option value="barcos">Barcos</option>
                    <option value="outros">Outros</option>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="product-condition" class="block text-gray-400 font-semibold mb-2">Condição</label>
                    <select id="product-condition" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200">
                        <option value="">Selecione a condição...</option>
                        <option value="novo">Novo</option>
                        <option value="usado">Usado</option>
                        <option value="remanufaturado">Remanufaturado</option>
                    </select>
                </div>
                <div>
                    <label for="product-market-value" class="block text-gray-400 font-semibold mb-2">Valor de Mercado Estimado (R$)</label>
                    <input type="number" id="product-market-value" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200" placeholder="Valor de mercado novo/usado">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="fipe-value" class="block text-gray-400 font-semibold mb-2">Valor Tabela FIPE (R$)</label>
                    <input type="number" id="fipe-value" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200" placeholder="Apenas para veículos">
                </div>
                <div>
                    <label for="functional-condition" class="block text-gray-400 font-semibold mb-2">Estado de Funcionamento</label>
                    <select id="functional-condition" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200">
                        <option value="">Selecione o estado...</option>
                        <option value="novo-sem-marcas">Novo, funcionando normalmente e sem marcas de uso</option>
                        <option value="usado-normal">Usado, funcionando normalmente</option>
                        <option value="falta-acessorios">Funcionando, mas falta acessórios</option>
                        <option value="amassado-riscado-quebrado">Funcionando, mas amassado, riscado ou quebrado</option>
                        <option value="nao-liga">Não liga</option>
                        <option value="outros-detalhes">Outros detalhes</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 font-semibold mb-2">Definir Preço Mínimo de Venda?</label>
                <div class="flex items-center">
                    <input type="checkbox" id="min-price-check" class="form-checkbox h-5 w-5 text-blue-600 rounded">
                    <label for="min-price-check" class="ml-2 text-gray-400">Sim</label>
                </div>
            </div>
            <div id="min-price-section" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="min-price" class="block text-gray-400 font-semibold mb-2">Valor Mínimo (R$)</label>
                        <input type="number" id="min-price" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200">
                    </div>
                    <div>
                        <label for="start-price" class="block text-gray-400 font-semibold mb-2">Lance Inicial (R$)</label>
                        <input type="number" id="start-price" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200">
                    </div>
                </div>
                <p class="text-sm text-gray-400 mb-4" id="fee-message"></p>
            </div>
            <div class="mb-4">
                <label for="auction-end-date" class="block text-gray-400 font-semibold mb-2">Data e Hora de Término (Prazo de 7 a 30 dias)</label>
                <input type="datetime-local" id="auction-end-date" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200">
            </div>
            <div class="mb-6">
                <label for="image-upload" class="block text-gray-400 font-semibold mb-2">Adicionar Foto do Produto</label>
                <input type="file" id="image-upload" accept="image/*" class="w-full p-3 border border-gray-600 rounded-lg bg-gray-700 text-gray-200">
                <p class="text-sm text-gray-500 mt-1">A análise da foto e da descrição será feita antes do anúncio.</p>
            </div>
            <button id="submit-ad" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg w-full transition duration-300">Anunciar Produto</button>
            <div id="validation-loading" class="mt-4 text-center hidden">
                <div class="flex items-center justify-center">
                    <div class="w-6 h-6 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mr-2"></div>
                    <p class="text-gray-400">Analisando produto com IA...</p>
                </div>
            </div>
            <p id="validation-error" class="text-red-400 text-sm mt-2 text-center hidden"></p>
        </div>
        <?php elseif ($page === 'compras'): ?>
        <div id="my-bids-page" class="container mt-8">
            <h2 class="text-2xl font-bold text-gray-200 mb-4">Minhas Compras</h2>
            <div id="my-bids-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <p id="no-bids-message" class="text-center text-gray-400 text-lg">Você ainda não tem compras.</p>
            </div>
        </div>
        <?php elseif ($page === 'vendas'): ?>
        <div id="my-sales-page" class="container mt-8">
            <h2 class="text-2xl font-bold text-gray-200 mb-4">Minhas Vendas</h2>
            <div id="my-sales-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <p id="no-sales-message" class="text-center text-gray-400 text-lg">Você ainda não realizou vendas.</p>
            </div>
        </div>
        <?php elseif (isset($_GET['auction_id'])): ?>
        <div id="product-detail-page" class="container mt-8">
            <div id="product-detail-content" class="bg-gray-800 p-8 rounded-lg shadow-md flex flex-col md:flex-row gap-8"></div>
        </div>
        <?php else: ?>
        <div class="container mt-8 p-8 text-center text-gray-400">Página não encontrada.</div>
        <?php endif; ?>
    </main>

    <!-- Modais e toasts -->
    <div id="auth-modal" class="modal hidden">
        <div class="modal-content">
            <div class="flex justify-end"><button id="close-modal" class="text-gray-400 hover:text-gray-200 text-2xl font-bold">&times;</button></div>
            <div id="login-form">
                <h3 class="text-2xl font-bold mb-4">Entrar na sua conta</h3>
                <input type="email" id="login-email" placeholder="E-mail" class="w-full p-3 border border-gray-600 rounded-lg mb-4 bg-gray-700 text-gray-200">
                <input type="password" id="login-password" placeholder="Senha" class="w-full p-3 border border-gray-600 rounded-lg mb-4 bg-gray-700 text-gray-200">
                <button id="login-button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg w-full transition duration-300">Entrar</button>
                <p class="text-center text-sm text-gray-400 mt-4">Ainda não tem conta? <a href="#" id="show-register" class="text-blue-400 hover:underline">Cadastre-se</a></p>
            </div>
            <div id="register-form" class="hidden">
                <h3 class="text-2xl font-bold mb-4">Criar uma nova conta</h3>
                <input type="text" id="register-name" placeholder="Nome completo" class="w-full p-3 border border-gray-600 rounded-lg mb-4 bg-gray-700 text-gray-200">
                <input type="email" id="register-email" placeholder="E-mail" class="w-full p-3 border border-gray-600 rounded-lg mb-4 bg-gray-700 text-gray-200">
                <input type="password" id="register-password" placeholder="Senha" class="w-full p-3 border border-gray-600 rounded-lg mb-4 bg-gray-700 text-gray-200">
                <button id="register-button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg w-full transition duration-300">Criar Conta</button>
                <p class="text-center text-sm text-gray-400 mt-4">Já tem uma conta? <a href="#" id="show-login" class="text-blue-400 hover:underline">Entrar</a></p>
            </div>
            <p id="auth-error-message" class="text-red-400 text-sm mt-4 text-center hidden"></p>
        </div>
    </div>
    <div id="pix-modal" class="modal hidden">
        <div class="modal-content text-center">
            <div class="flex justify-end"><button id="close-pix-modal" class="text-gray-400 hover:text-gray-200 text-2xl font-bold">&times;</button></div>
            <h3 class="text-2xl font-bold mb-4">Pagar com Pix</h3>
            <p class="text-gray-400 mb-4">Use a câmera do seu celular para escanear o QR Code ou copie o código Pix abaixo.</p>
            <img id="pix-qrcode" src="https://placehold.co/200x200/D3D3D3/000000?text=QR+Code+Pix" alt="QR Code Pix" class="mx-auto my-4 rounded-lg">
            <div class="relative p-4 bg-gray-700 rounded-lg">
                <p id="pix-code" class="break-all text-gray-200 font-mono text-sm">c0de-pix-simulado-para-pagamento-123456</p>
                <button id="copy-pix-button" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-full transition duration-300">Copiar Código Pix</button>
            </div>
        </div>
    </div>
    <div id="notification-toast" class="hidden"><p id="toast-message"></p></div>

    <script src="script.js" type="module"></script>
</body>

</html>
