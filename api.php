<?php
require 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

if ($action == 'place_order' && isset($_SESSION['user_id'])) {
    try {
        $pdo->beginTransaction();

        $remark = $data['remark'] ?? ''; // Capture remark

        // Calculate total
        $total = 0;
        foreach ($data['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }

        // Create Order with Remark
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, remark) VALUES (?, ?, 'pending', ?)");
        $stmt->execute([$_SESSION['user_id'], $total, $remark]);
        $order_id = $pdo->lastInsertId();

        // Create Order Items
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, item_name, price, quantity) VALUES (?, ?, ?, ?)");
        foreach ($data['items'] as $item) {
            $stmt->execute([$order_id, $item['name'], $item['price'], $item['qty']]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'order_id' => $order_id]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 

elseif ($action == 'update_status' && $_SESSION['role'] == 'admin') {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $result = $stmt->execute([$data['status'], $data['order_id']]);
    echo json_encode(['success' => $result]);
}

elseif ($action == 'get_orders' && isset($_SESSION['user_id'])) {
    $status = $data['status'] ?? 'all';
    $role = $_SESSION['role'];
    $userId = $_SESSION['user_id'];

    $sql = "SELECT orders.*, users.name as user_name FROM orders 
            JOIN users ON orders.user_id = users.id 
            WHERE 1=1";
    $params = [];

    if ($role == 'student') {
        $sql .= " AND orders.user_id = ?";
        $params[] = $userId;
    }

    if ($status !== 'all' && !empty($status)) {
        $sql .= " AND orders.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY orders.created_at DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$order) {
            $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$order['id']]);
            $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

elseif ($action == 'update_profile' && isset($_SESSION['user_id'])) {
    $name = $data['name'];
    $password = $data['password'] ?? null;
    $userId = $_SESSION['user_id'];

    try {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $hashed, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $userId]);
        }
        $_SESSION['name'] = $name;
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>