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
    
    if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
        throw new Exception('Name, email, and message are required');
    }
    

    $columns = $pdo->query("SHOW COLUMNS FROM contact_info")->fetchAll(PDO::FETCH_COLUMN);
    
    $insertColumns = [];
    $insertPlaceholders = [];
    $insertValues = [];
    
   
    $fieldMappings = [
        'name' => ['name', 'full_name', 'customer_name'],
        'email' => ['email', 'email_address'],
        'phone' => ['phone', 'phone_number', 'telephone'],
        'subject' => ['subject', 'inquiry_type', 'type'],
        'message' => ['message', 'content', 'inquiry']
    ];
    
    foreach ($fieldMappings as $formField => $possibleColumns) {
        foreach ($possibleColumns as $col) {
            if (in_array($col, $columns)) {
                $insertColumns[] = $col;
                $insertPlaceholders[] = '?';
                $insertValues[] = $data[$formField] ?? null;
                break;
            }
        }
    }
    
    if (empty($insertColumns)) {
        throw new Exception('No matching columns found in contact_info table');
    }
    
    $sql = "INSERT INTO contact_info (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($insertValues);
    
    $contactId = $pdo->lastInsertId();
    
  
    try {
        $logStmt = $pdo->prepare("INSERT INTO activity_log (activity_type, description) VALUES (?, ?)");
        $logStmt->execute(['contact_received', "New contact from {$data['name']} ({$data['email']}) - Subject: " . ($data['subject'] ?? 'N/A')]);
    } catch (Exception $e) {
        
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully',
        'id' => $contactId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving message: ' . $e->getMessage()
    ]);
}
?>

