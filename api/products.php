<?php


require_once '../config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'GET':
          
            if (isset($_GET['id'])) {
             
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $product = $stmt->fetch();

                if ($product) {
                    if (isset($product['image_url']) && !isset($product['image'])) {
                        $product['image'] = $product['image_url'];
                    }
                    $response = ['success' => true, 'data' => $product];
                } else {
                    $response = ['success' => false, 'message' => 'Product not found'];
                }
            } else {
               
                $category = $_GET['category'] ?? null;
                $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';

                $sql = "SELECT * FROM products WHERE 1=1";
                $params = [];

                if ($category) {
                    $sql .= " AND category = ?";
                    $params[] = $category;
                }

                
                if ($activeOnly) {
                    $columns = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_active'")->fetchAll();
                    if (!empty($columns)) {
                        $sql .= " AND is_active = 1";
                    }
                }

                $sql .= " ORDER BY id DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $products = $stmt->fetchAll();

               
                foreach ($products as &$p) {
                    if (isset($p['image_url']) && !isset($p['image'])) {
                        $p['image'] = $p['image_url'];
                    }
                }
                unset($p);

                $response = ['success' => true, 'data' => $products, 'count' => count($products)];
            }
            break;
            
        case 'POST':
           
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name']) || empty($data['price']) || empty($data['image'])) {
                throw new Exception('Name, price, and image are required');
            }
         
            $columns = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_active'")->fetchAll();
            if (!empty($columns)) {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, image_url, category, price, description, stock, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $data['name'],
                    $data['image'],
                    $data['category'] ?? null,
                    $data['price'],
                    $data['description'] ?? null,
                    $data['stock'] ?? 0,
                    isset($data['is_active']) ? (int)$data['is_active'] : 1
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, image_url, category, price, description, stock)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $data['name'],
                    $data['image'],
                    $data['category'] ?? null,
                    $data['price'],
                    $data['description'] ?? null,
                    $data['stock'] ?? 0
                ]);
            }
            
            $productId = $pdo->lastInsertId();
            
         
            $logStmt = $pdo->prepare("INSERT INTO activity_log (activity_type, description) VALUES (?, ?)");
            $logStmt->execute(['product_created', "New product created: {$data['name']} (ID: {$productId})"]);
            
            $response = ['success' => true, 'message' => 'Product created successfully', 'id' => $productId];
            break;
            
        case 'PUT':
         
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception('Product ID is required');
            }
            
            if (empty($data['name']) || empty($data['price']) || empty($data['image'])) {
                throw new Exception('Name, price, and image are required');
            }
            
            $columns = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_active'")->fetchAll();
            if (!empty($columns)) {
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, image_url = ?, category = ?, price = ?, description = ?, stock = ?, is_active = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $data['name'],
                    $data['image'],
                    $data['category'] ?? null,
                    $data['price'],
                    $data['description'] ?? null,
                    $data['stock'] ?? 0,
                    isset($data['is_active']) ? (int)$data['is_active'] : 1,
                    $data['id']
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, image_url = ?, category = ?, price = ?, description = ?, stock = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $data['name'],
                    $data['image'],
                    $data['category'] ?? null,
                    $data['price'],
                    $data['description'] ?? null,
                    $data['stock'] ?? 0,
                    $data['id']
                ]);
            }
            
           
            $logStmt = $pdo->prepare("INSERT INTO activity_log (activity_type, description) VALUES (?, ?)");
            $logStmt->execute(['product_updated', "Product updated: {$data['name']} (ID: {$data['id']})"]);
            
            $response = ['success' => true, 'message' => 'Product updated successfully'];
            break;
            
        case 'DELETE':
         
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception('Product ID is required');
            }
            
          
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$data['id']]);
            $product = $stmt->fetch();
            
           
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$data['id']]);
            
           
            if ($product) {
                $logStmt = $pdo->prepare("INSERT INTO activity_log (activity_type, description) VALUES (?, ?)");
                $logStmt->execute(['product_deleted', "Product deleted: {$product['name']} (ID: {$data['id']})"]);
            }
            
            $response = ['success' => true, 'message' => 'Product deleted successfully'];
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
?>

