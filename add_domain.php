<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = filter_var($_POST['domain'], FILTER_SANITIZE_STRING);
    
    if (empty($domain)) {
        echo json_encode(['success' => false, 'message' => 'Domain adresi boş olamaz']);
        exit;
    }

    try {
        // Domain'in zaten var olup olmadığını kontrol et
        $stmt = $db->prepare("SELECT id FROM domains WHERE domain = ?");
        $stmt->execute([$domain]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Bu domain zaten eklenmiş']);
            exit;
        }

        // Yeni domain'i ekle
        $stmt = $db->prepare("INSERT INTO domains (domain, created_at) VALUES (?, NOW())");
        $stmt->execute([$domain]);
        
        echo json_encode(['success' => true, 'message' => 'Domain başarıyla eklendi']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
}
?> 