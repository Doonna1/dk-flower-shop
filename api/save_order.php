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
    
   
    if (empty($data['customerName']) || empty($data['customerAddress']) || empty($data['items'])) {
        throw new Exception('Missing required fields');
    }
    
 
    $pdo->beginTransaction();
    
  
    $orderColumns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
    
    $insertCols = [];
    $insertVals = [];
    
    
    $columnMappings = [
        ['data_key' => 'customerName', 'possible_cols' => ['customer_name', 'name'], 'value' => $data['customerName']],
        ['data_key' => 'customerAddress', 'possible_cols' => ['customer_address', 'address'], 'value' => $data['customerAddress']],
        ['data_key' => 'customerEmail', 'possible_cols' => ['customer_email', 'email'], 'value' => $data['customerEmail'] ?? null],
        ['data_key' => 'customerPhone', 'possible_cols' => ['customer_phone', 'phone'], 'value' => $data['customerPhone'] ?? null],
        ['data_key' => 'total', 'possible_cols' => ['order_total', 'total', 'total_amount'], 'value' => $data['total']],
        ['data_key' => 'subtotal', 'possible_cols' => ['subtotal'], 'value' => $data['subtotal']],
        ['data_key' => 'shipping', 'possible_cols' => ['shipping', 'shipping_cost'], 'value' => $data['shipping']],
        ['data_key' => 'status', 'possible_cols' => ['order_status', 'status'], 'value' => 'pending']
    ];
    
    foreach ($columnMappings as $mapping) {
        foreach ($mapping['possible_cols'] as $col) {
            if (in_array($col, $orderColumns)) {
                $insertCols[] = $col;
                $insertVals[] = $mapping['value'];
                break;
            }
        }
    }
    
    if (empty($insertCols)) {
        throw new Exception('No matching columns found in orders table');
    }
    
    $placeholders = array_fill(0, count($insertCols), '?');
    $sql = "INSERT INTO orders (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($insertVals);
    
    $orderId = $pdo->lastInsertId();
    
    $columns = $pdo->query("SHOW COLUMNS FROM order_items")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($data['items'] as $item) {
       
        $insertCols = ['order_id', 'product_id', 'quantity', 'price'];
        $insertVals = [$orderId, $item['id'], $item['quantity'], $item['price']];
        
        if (in_array('product_name', $columns)) {
            $insertCols[] = 'product_name';
            $insertVals[] = $item['name'];
        }
        if (in_array('product_image', $columns)) {
            $insertCols[] = 'product_image';
            $insertVals[] = $item['image'] ?? null;
        }
        if (in_array('subtotal', $columns)) {
            $insertCols[] = 'subtotal';
            $insertVals[] = $item['price'] * $item['quantity'];
        }
        
        $placeholders = array_fill(0, count($insertCols), '?');
        $sql = "INSERT INTO order_items (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $itemStmt = $pdo->prepare($sql);
        $itemStmt->execute($insertVals);
    }
  
    $logStmt = $pdo->prepare("
        INSERT INTO activity_log (activity_type, order_id, description)
        VALUES ('order_placed', :order_id, :description)
    ");
    
    $logStmt->execute([
        ':order_id' => $orderId,
        ':description' => "Order #{$orderId} placed by {$data['customerName']} - Total: $" . number_format($data['total'], 2)
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order saved successfully',
        'order_id' => $orderId
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving order: ' . $e->getMessage()
    ]);
}
?>

