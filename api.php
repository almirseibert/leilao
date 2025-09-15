<?php
session_start();
header('Content-Type: application/json');

// Credenciais do banco de dados (Host Interno)
$servername = "sites_sql";
$username = "mysql";
$password = "Miguel@18032018";
$dbname = "sites";

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão e retorna um erro se falhar
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Falha na conexão: " . $conn->connect_error]);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos.']);
            exit();
        }

        // Criptografa a senha antes de salvar
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password_hash);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar. Tente outro e-mail.']);
        }
        $stmt->close();
        break;

    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, name, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            echo json_encode(['success' => true, 'message' => 'Login bem-sucedido!', 'userName' => $user['name']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'E-mail ou senha incorretos.']);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Sessão encerrada.']);
        break;

    case 'list_auctions':
        $sql = "SELECT a.*, u.name AS seller_name FROM auctions a JOIN users u ON a.seller_id = u.id WHERE a.end_time > NOW() ORDER BY a.end_time ASC";
        $result = $conn->query($sql);
        $auctions = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $auctions[] = $row;
            }
        }
        echo json_encode(['success' => true, 'auctions' => $auctions]);
        break;

    case 'get_auction_detail':
        $auction_id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("SELECT a.*, u.name AS seller_name FROM auctions a JOIN users u ON a.seller_id = u.id WHERE a.id = ?");
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $auction = $result->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => true, 'auction' => $auction]);
        break;

    case 'place_bid':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para dar um lance.']);
            exit();
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $auction_id = $data['auction_id'] ?? 0;
        $bid_amount = $data['bid_amount'] ?? 0;
        $bidder_id = $_SESSION['user_id'];

        // Recupera o lance atual
        $stmt = $conn->prepare("SELECT current_bid, highest_bidder_id FROM auctions WHERE id = ?");
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $auction = $result->fetch_assoc();
        $stmt->close();

        if ($auction_id === $bidder_id) {
            echo json_encode(['success' => false, 'message' => 'Você não pode dar lances em seus próprios leilões.']);
            exit();
        }

        if ($bid_amount <= $auction['current_bid']) {
            echo json_encode(['success' => false, 'message' => 'Seu lance deve ser maior que o lance atual.']);
            exit();
        }

        // Adiciona um tempo extra ao leilão
        $extra_time = 0;
        $now = new DateTime();
        $end_time = new DateTime($auction['end_time']);
        $time_left = $now->diff($end_time)->s + ($now->diff($end_time)->i * 60);

        if ($time_left < 1800) { // 30 minutos
            $extra_time = 60; // 1 minuto
        }
        if ($time_left < 60) { // 1 minuto
            $extra_time = 15; // 15 segundos
        }
        
        $stmt = $conn->prepare("UPDATE auctions SET current_bid = ?, bids = bids + 1, highest_bidder_id = ?, end_time = DATE_ADD(end_time, INTERVAL ? SECOND) WHERE id = ?");
        $stmt->bind_param("diii", $bid_amount, $bidder_id, $extra_time, $auction_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Lance realizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar o lance.']);
        }
        $stmt->close();
        break;

    case 'create_auction':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para anunciar um produto.']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        // Simulação de validação da IA
        if (strpos(strtolower($data['description']), 'arma') !== false || strpos(strtolower($data['product_name']), 'arma') !== false) {
            echo json_encode(['success' => false, 'message' => 'Anúncio reprovado pela análise de IA. Motivo: Conteúdo proibido.']);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO auctions (product_name, description, category, `condition`, market_value, fipe_value, functional_condition, has_min_price, min_price, start_price, current_bid, bids, end_time, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssddisdddisi", $data['name'], $data['description'], $data['category'], $data['condition'], $data['market_value'], $data['fipe_value'], $data['functional_condition'], $data['has_min_price'], $data['min_price'], $data['start_price'], $data['start_price'], $data['bids'], $data['end_time'], $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Produto anunciado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao anunciar o produto.']);
        }
        $stmt->close();
        break;
        
    case 'my_bids':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para ver suas compras.']);
            exit();
        }
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT a.*, u.name AS seller_name FROM auctions a JOIN users u ON a.seller_id = u.id WHERE a.highest_bidder_id = ? AND a.end_time < NOW()");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $auctions = [];
        while ($row = $result->fetch_assoc()) {
            $auctions[] = $row;
        }
        echo json_encode(['success' => true, 'auctions' => $auctions]);
        $stmt->close();
        break;
        
    case 'my_sales':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para ver suas vendas.']);
            exit();
        }
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM auctions WHERE seller_id = ? AND end_time < NOW()");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $auctions = [];
        while ($row = $result->fetch_assoc()) {
            $auctions[] = $row;
        }
        echo json_encode(['success' => true, 'auctions' => $auctions]);
        $stmt->close();
        break;
        
    case 'confirm_receipt':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para confirmar o recebimento.']);
            exit();
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $auction_id = $data['auction_id'] ?? 0;
        $stmt = $conn->prepare("UPDATE auctions SET payment_status = 'Valor liberado para saque' WHERE id = ?");
        $stmt->bind_param("i", $auction_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Recebimento confirmado! O valor será liberado para o vendedor em 7 dias.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao confirmar o recebimento.']);
        }
        $stmt->close();
        break;
        
    case 'pay_now':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para realizar o pagamento.']);
            exit();
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $auction_id = $data['auction_id'] ?? 0;
        $stmt = $conn->prepare("UPDATE auctions SET payment_status = 'Aguardando confirmação de recebimento' WHERE id = ?");
        $stmt->bind_param("i", $auction_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pagamento simulado. O valor está retido.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao processar o pagamento.']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação não encontrada.']);
        break;
}

$conn->close();
