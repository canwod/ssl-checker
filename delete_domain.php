<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz domain ID']);
        exit;
    }

    try {
        $stmt = $db->prepare("DELETE FROM domains WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Domain başarıyla silindi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Domain bulunamadı']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
}
?> 