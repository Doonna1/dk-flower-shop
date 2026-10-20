<?php
require_once '../config.php';
session_start();
$admin_password = 'admin123'; 

if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'Incorrect password';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login - DK Flower Boutique</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
            input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
            button { width: 100%; padding: 10px; background: #f2cbd2; border: none; cursor: pointer; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h2>Admin Login</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter admin password" required>
            <button type="submit" name="login">Login</button>
        </form>
        <p><small>Default password: admin123 (change this in admin/index.php)</small></p>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DK Flower Boutique</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .header { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #f2cbd2; }
        .logout { padding: 8px 16px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #f2cbd2; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { margin-bottom: 15px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f8f8; font-weight: 600; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge.pending { background: #ffc107; color: #000; }
        .badge.completed { background: #28a745; color: white; }
        .activity-item { padding: 10px; border-bottom: 1px solid #eee; }
        .activity-item:last-child { border-bottom: none; }
        .activity-time { color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌹 Admin Dashboard - DK Flower Boutique</h1>
        <div style="display: flex; gap: 10px;">
            <a href="products.php" style="padding: 8px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">Manage Products</a>
            <a href="?logout=1" class="logout">Logout</a>
        </div>
    </div>

    <?php
   
    $tablesExist = true;
    try {
        $tables = $pdo->query("SHOW TABLES LIKE 'orders'")->fetchAll();
        if (empty($tables)) {
            $tablesExist = false;
        }
    } catch (Exception $e) {
        $tablesExist = false;
    }
    
    if (!$tablesExist) {
        echo "<div class='db-warning' style='background: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px; border-radius: 8px;'>";
        echo "<h2>⚠️ Database Setup Required</h2>";
        echo "<p>Please run the <code>database_setup.sql</code> file in phpMyAdmin to create the necessary tables.</p>";
        echo "<p><strong>Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
        echo "<li>Select the 'dk_flowershop' database</li>";
        echo "<li>Click on the 'SQL' tab</li>";
        echo "<li>Copy and paste the contents of <code>database_setup.sql</code></li>";
        echo "<li>Click 'Go' to execute</li>";
        echo "</ol>";
        echo "<p><a href='../database_setup.sql' target='_blank'>View database_setup.sql file</a></p>";
        echo "</div>";
    }
    ?>

    <?php
    

    if ($tablesExist) {
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch();
            $totalOrders = $result ? ($result['count'] ?? 0) : 0;
        } catch (Exception $e) {
            $totalOrders = 0;
        }
        
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
            $totalUsers = $result ? ($result['count'] ?? 0) : 0;
        } catch (Exception $e) {
            $totalUsers = 0;
        }
        
        try {
            $columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'order_status'")->fetchAll();
            if (!empty($columns)) {
                $result = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch();
                $pendingOrders = $result ? ($result['count'] ?? 0) : 0;
            } else {
                $result = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch();
                $pendingOrders = $result ? ($result['count'] ?? 0) : 0;
            }
        } catch (Exception $e) {
            $pendingOrders = 0;
        }
        
        try {
            $result = null;
            $columns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('order_total', $columns)) {
                $result = $pdo->query("SELECT SUM(order_total) as total FROM orders")->fetch();
            } elseif (in_array('total_amount', $columns)) {
                $result = $pdo->query("SELECT SUM(total_amount) as total FROM orders")->fetch();
            } elseif (in_array('total', $columns)) {
                $result = $pdo->query("SELECT SUM(total) as total FROM orders")->fetch();
            }
            $totalRevenue = $result ? ($result['total'] ?? 0) : 0;
        } catch (Exception $e) {
            $totalRevenue = 0;
        }
    } else {
        $totalOrders = 0;
        $totalUsers = 0;
        $pendingOrders = 0;
        $totalRevenue = 0;
    }
    ?>

    <div class="stats">
        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="number"><?php echo $totalOrders; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Users</h3>
            <div class="number"><?php echo $totalUsers; ?></div>
        </div>
        <div class="stat-card">
            <h3>Pending Orders</h3>
            <div class="number"><?php echo $pendingOrders; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Revenue</h3>
            <div class="number">$<?php echo number_format($totalRevenue, 2); ?></div>
        </div>
    </div>

    <div class="section">
        <h2>📋 Recent Orders</h2>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Address</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                  
                    $dateColumn = 'created_at';
                    $columns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
                    if (!in_array('created_at', $columns)) {
                        if (in_array('order_date', $columns)) {
                            $dateColumn = 'order_date';
                        } elseif (in_array('date', $columns)) {
                            $dateColumn = 'date';
                        } else {
                         
                            $dateColumn = 'id';
                        }
                    }
                    
                    $orders = $pdo->query("
                        SELECT * FROM orders 
                        ORDER BY {$dateColumn} DESC 
                        LIMIT 20
                    ")->fetchAll();
                    
                    if (empty($orders)) {
                        echo "<tr><td colspan='6' style='text-align: center; padding: 20px; color: #999;'>No orders yet</td></tr>";
                    } else {
                        foreach ($orders as $order) {
                            echo "<tr>";
                            echo "<td>#{$order['id']}</td>";
                            echo "<td>" . ($order['customer_name'] ?? 'N/A') . "</td>";
                            $address = $order['customer_address'] ?? $order['address'] ?? 'N/A';
                            echo "<td>" . htmlspecialchars(substr($address, 0, 50)) . ($address !== 'N/A' ? "..." : "") . "</td>";
                            $total = $order['order_total'] ?? $order['total_amount'] ?? $order['total'] ?? 0;
                            echo "<td>$" . number_format($total, 2) . "</td>";
                            $status = isset($order['order_status']) ? $order['order_status'] : 'pending';
                            echo "<td><span class='badge {$status}'>{$status}</span></td>";
                            $dateValue = $order[$dateColumn] ?? null;
                            if ($dateValue && $dateColumn !== 'id') {
                                echo "<td>" . date('M d, Y H:i', strtotime($dateValue)) . "</td>";
                            } else {
                                echo "<td>N/A</td>";
                            }
                            echo "</tr>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<tr><td colspan='6' style='text-align: center; padding: 20px; color: red;'>Error loading orders: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>👥 Recent User Registrations</h2>
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Registered Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                 
                    $dateColumn = 'created_at';
                    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
                    if (!in_array('created_at', $columns)) {
                        if (in_array('registration_date', $columns)) {
                            $dateColumn = 'registration_date';
                        } elseif (in_array('date', $columns)) {
                            $dateColumn = 'date';
                        } else {
                            $dateColumn = 'id';
                        }
                    }
                    
                    $users = $pdo->query("
                        SELECT * FROM users 
                        ORDER BY {$dateColumn} DESC 
                        LIMIT 20
                    ")->fetchAll();
                    
                    if (empty($users)) {
                        echo "<tr><td colspan='5' style='text-align: center; padding: 20px; color: #999;'>No users registered yet</td></tr>";
                    } else {
                        foreach ($users as $user) {
                            echo "<tr>";
                            echo "<td>#{$user['id']}</td>";
                            echo "<td>" . ($user['full_name'] ?? 'N/A') . "</td>";
                            echo "<td>" . ($user['email'] ?? 'N/A') . "</td>";
                            echo "<td>" . ($user['phone'] ?? '-') . "</td>";
                            $dateValue = $user[$dateColumn] ?? null;
                            if ($dateValue && $dateColumn !== 'id') {
                                echo "<td>" . date('M d, Y H:i', strtotime($dateValue)) . "</td>";
                            } else {
                                echo "<td>N/A</td>";
                            }
                            echo "</tr>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<tr><td colspan='5' style='text-align: center; padding: 20px; color: red;'>Error loading users: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>📬 Contact Messages</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                 
                    $tables = $pdo->query("SHOW TABLES LIKE 'contact_info'")->fetchAll();
                    if (!empty($tables)) {
                        $contacts = $pdo->query("SELECT * FROM contact_info ORDER BY id DESC LIMIT 10")->fetchAll();
                        
                        if (empty($contacts)) {
                            echo "<tr><td colspan='6' style='text-align: center; padding: 20px; color: #999;'>No contact messages yet</td></tr>";
                        } else {
                            foreach ($contacts as $contact) {
                                echo "<tr>";
                                echo "<td>#{$contact['id']}</td>";
                                echo "<td>" . ($contact['name'] ?? $contact['full_name'] ?? 'N/A') . "</td>";
                                echo "<td>" . ($contact['email'] ?? 'N/A') . "</td>";
                                echo "<td>" . ($contact['subject'] ?? 'N/A') . "</td>";
                                $message = $contact['message'] ?? $contact['content'] ?? 'N/A';
                                echo "<td>" . htmlspecialchars(substr($message, 0, 50)) . (strlen($message) > 50 ? "..." : "") . "</td>";
                                $date = $contact['created_at'] ?? $contact['date'] ?? null;
                                echo "<td>" . ($date ? date('M d, Y H:i', strtotime($date)) : 'N/A') . "</td>";
                                echo "</tr>";
                            }
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center; padding: 20px; color: #999;'>Contact table not found</td></tr>";
                    }
                } catch (Exception $e) {
                    echo "<tr><td colspan='6' style='text-align: center; padding: 20px; color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>📊 Activity Log</h2>
        <div>
            <?php
            try {
          
                $tables = $pdo->query("SHOW TABLES LIKE 'activity_log'")->fetchAll();
                if (empty($tables)) {
                    echo "<p style='text-align: center; padding: 20px; color: #999;'>Activity log table not found</p>";
                } else {
                    $dateColumn = 'created_at';
                    $columns = $pdo->query("SHOW COLUMNS FROM activity_log")->fetchAll(PDO::FETCH_COLUMN);
                    if (!in_array('created_at', $columns)) {
                        if (in_array('timestamp', $columns)) {
                            $dateColumn = 'timestamp';
                        } elseif (in_array('date', $columns)) {
                            $dateColumn = 'date';
                        } else {
                            $dateColumn = 'id';
                        }
                    }
                    
                    $activities = $pdo->query("
                        SELECT * FROM activity_log 
                        ORDER BY {$dateColumn} DESC 
                        LIMIT 30
                    ")->fetchAll();
                    
                    if (empty($activities)) {
                        echo "<p style='text-align: center; padding: 20px; color: #999;'>No activities yet</p>";
                    } else {
                        foreach ($activities as $activity) {
                            $activityType = $activity['activity_type'] ?? 'unknown';
                            $icon = $activityType === 'order_placed' ? '🛒' : '👤';
                            echo "<div class='activity-item'>";
                            echo "<strong>{$icon} " . htmlspecialchars($activityType) . "</strong><br>";
                            echo htmlspecialchars($activity['description'] ?? 'No description') . "<br>";
                            $dateValue = $activity[$dateColumn] ?? null;
                            if ($dateValue && $dateColumn !== 'id') {
                                echo "<span class='activity-time'>" . date('M d, Y H:i:s', strtotime($dateValue)) . "</span>";
                            } else {
                                echo "<span class='activity-time'>N/A</span>";
                            }
                            echo "</div>";
                        }
                    }
                }
            } catch (Exception $e) {
                echo "<p style='text-align: center; padding: 20px; color: red;'>Error loading activities: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>

