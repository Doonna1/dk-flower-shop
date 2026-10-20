<?php
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $type = isset($_GET['type']) ? $_GET['type'] : null; 
    

    $sql = "SELECT id, name, image_url, category, price, description FROM products WHERE 1=1";
    $params = [];
    
   
    if ($category && $category !== 'all') {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
   
    if ($type === 'home') {
      
        $sql .= " AND category IN ('popular', 'exotic', 'greens', 'cactuses', 'winter')";
    } elseif ($type === 'gifts') {
      
        $sql .= " AND category IN ('happy-birthday', 'i-love-you', 'congratulations', 'im-sorry')";
    }
    
    $sql .= " ORDER BY id ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
    $formattedProducts = array_map(function($product) {
        return [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'image' => $product['image_url'],
            'category' => $product['category'],
            'price' => (float)$product['price'],
            'description' => $product['description']
        ];
    }, $products);
    
    echo json_encode([
        'success' => true,
        'products' => $formattedProducts,
        'count' => count($formattedProducts)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching products: ' . $e->getMessage()
    ]);
}
?>

