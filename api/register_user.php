<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
 
    if (empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
        throw new Exception('Missing required fields');
    }
    
  
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $checkStmt->execute([':email' => $data['email']]);
    if ($checkStmt->fetch()) {
        throw new Exception('Email already registered');
    }
    
   
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
  
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password, phone, address)
        VALUES (:full_name, :email, :password, :phone, :address)
    ");
    
    $stmt->execute([
        ':full_name' => $data['full_name'],
        ':email' => $data['email'],
        ':password' => $hashedPassword,
        ':phone' => $data['phone'] ?? null,
        ':address' => $data['address'] ?? null
    ]);
    
    $userId = $pdo->lastInsertId();
    

    $logStmt = $pdo->prepare("
        INSERT INTO activity_log (activity_type, user_id, description)
        VALUES ('user_registration', :user_id, :description)
    ");
    
    $logStmt->execute([
        ':user_id' => $userId,
        ':description' => "New user registered: {$data['full_name']} ({$data['email']})"
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User registered successfully',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error registering user: ' . $e->getMessage()
    ]);
}
?>

