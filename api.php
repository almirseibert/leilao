<?php
session_start();
header('Content-Type: application/json');

// Configuração da conexão com o banco de dados
$servername = "sites_sql";
$username = "mysql";
$password = "Miguel@18032018";
$dbname = "sites";

$conn = new mysqli($servername, $username, $password, $dbname);

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

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password_hash);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso!', 'userId' => $stmt->insert_id, 'userName' => $name]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar usuário. Tente outro e-mail.']);
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
            echo json_encode(['success' => true, 'message' => 'Login bem-sucedido!', 'userId' => $user['id'], 'userName' => $user['name']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'E-mail ou senha incorretos.']);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Sessão encerrada com sucesso.']);
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
        $data = json_decode(file_get_contents('php://input'), true);
        $auction_id = $data['auction_id'] ?? 0;
        $bid_amount = $data['bid_amount'] ?? 0;
        $bidder_id = $_SESSION['user_id'] ?? 0;

        $stmt = $conn->prepare("SELECT current_bid FROM auctions WHERE id = ?");
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_bid = $result->fetch_assoc()['current_bid'];
        $stmt->close();

        if ($bid_amount <= $current_bid) {
            echo json_encode(['success' => false, 'message' => 'Seu lance deve ser maior que o lance atual.']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE auctions SET current_bid = ?, bids = bids + 1, highest_bidder_id = ?, end_time = DATE_ADD(end_time, INTERVAL 1 MINUTE) WHERE id = ? AND end_time > NOW()");
        $stmt->bind_param("dii", $bid_amount, $bidder_id, $auction_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Lance realizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar o lance.']);
        }
        $stmt->close();
        break;

    case 'create_auction':
        $data = json_decode(file_get_contents('php://input'), true);
        // ... (Validação e inserção de dados no banco de dados)
        // A lógica de validação do Gemini AI seria aqui no servidor antes de salvar no DB
        // ...
        $stmt = $conn->prepare("INSERT INTO auctions (product_name, description, category, `condition`, market_value, fipe_value, functional_condition, has_min_price, min_price, start_price, current_bid, bids, end_time, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssddisiddisi", $data['name'], $data['description'], $data['category'], $data['condition'], $data['market_value'], $data['fipe_value'], $data['functional_condition'], $data['has_min_price'], $data['min_price'], $data['start_price'], $data['start_price'], $data['bids'], $data['end_time'], $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Produto anunciado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao anunciar o produto.']);
        }
        $stmt->close();
        break;
        
    case 'my_bids':
        $user_id = $_SESSION['user_id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM auctions WHERE highest_bidder_id = ? AND end_time < NOW()");
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
        $user_id = $_SESSION['user_id'] ?? 0;
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
?>
