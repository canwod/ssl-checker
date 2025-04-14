<?php
require_once 'config.php';

function checkSSL($domain) {
    $context = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    
    try {
        $stream = @stream_socket_client(
            'ssl://' . $domain . ':443',
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if ($stream) {
            $params = stream_context_get_params($stream);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            
            $validFrom = $cert['validFrom_time_t'];
            $validTo = $cert['validTo_time_t'];
            $now = time();
            
            // Daha doğru gün hesaplaması
            $validToDate = new DateTime('@' . $validTo);
            $nowDate = new DateTime('@' . $now);
            $interval = $nowDate->diff($validToDate);
            $daysLeft = $interval->days;
            
            // Başlangıç ve bitiş tarihlerini formatla
            $startDate = date('d.m.Y H:i:s', $validFrom);
            $endDate = date('d.m.Y H:i:s', $validTo);
            
            // Sertifika detayları
            $issuer = isset($cert['issuer']['CN']) ? $cert['issuer']['CN'] : 'Bilinmiyor';
            $subject = isset($cert['subject']['CN']) ? $cert['subject']['CN'] : 'Bilinmiyor';
            $serialNumber = isset($cert['serialNumber']) ? $cert['serialNumber'] : 'Bilinmiyor';
            $signatureAlgorithm = isset($cert['signatureTypeSN']) ? $cert['signatureTypeSN'] : 'Bilinmiyor';
            $version = isset($cert['version']) ? $cert['version'] : 'Bilinmiyor';
            
            if ($daysLeft < 0) {
                return [
                    'status' => 'expired',
                    'days' => abs($daysLeft),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'issuer' => $issuer,
                    'subject' => $subject,
                    'serial_number' => $serialNumber,
                    'signature_algorithm' => $signatureAlgorithm,
                    'version' => $version
                ];
            } elseif ($daysLeft < 30) {
                return [
                    'status' => 'warning',
                    'days' => $daysLeft,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'issuer' => $issuer,
                    'subject' => $subject,
                    'serial_number' => $serialNumber,
                    'signature_algorithm' => $signatureAlgorithm,
                    'version' => $version
                ];
            } else {
                return [
                    'status' => 'valid',
                    'days' => $daysLeft,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'issuer' => $issuer,
                    'subject' => $subject,
                    'serial_number' => $serialNumber,
                    'signature_algorithm' => $signatureAlgorithm,
                    'version' => $version
                ];
            }
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
    
    return ['status' => 'error', 'message' => 'SSL sertifikası bulunamadı'];
}

try {
    $stmt = $db->query("SELECT * FROM domains ORDER BY created_at DESC");
    $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // SSL bilgilerini topla ve sırala
    $domainData = [];
    foreach ($domains as $domain) {
        $sslInfo = checkSSL($domain['domain']);
        $domainData[] = [
            'id' => $domain['id'],
            'domain' => $domain['domain'],
            'ssl_info' => $sslInfo
        ];
    }
    
    // Kalan gün sayısına göre sırala
    usort($domainData, function($a, $b) {
        $daysA = isset($a['ssl_info']['days']) ? $a['ssl_info']['days'] : PHP_INT_MAX;
        $daysB = isset($b['ssl_info']['days']) ? $b['ssl_info']['days'] : PHP_INT_MAX;
        return $daysA - $daysB;
    });
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover">';
    echo '<thead><tr>
            <th>Domain</th>
            <th>SSL Durumu</th>
            <th>Kalan Gün</th>
            <th>Başlangıç Tarihi</th>
            <th>Bitiş Tarihi</th>
            <th>Sertifika Detayları</th>
            <th>İşlemler</th>
          </tr></thead>';
    echo '<tbody>';
    
    foreach ($domainData as $data) {
        $domain = $data['domain'];
        $sslInfo = $data['ssl_info'];
        
        // Kalan gün sayısına göre satır rengini belirle
        $rowClass = '';
        if (isset($sslInfo['days'])) {
            if ($sslInfo['days'] < 0) {
                $rowClass = 'table-danger';
            } elseif ($sslInfo['days'] < 30) {
                $rowClass = 'table-warning';
            } else {
                $rowClass = 'table-success';
            }
        }
        
        echo '<tr class="domain-card ' . $rowClass . '">';
        echo '<td>' . htmlspecialchars($domain) . '</td>';
        
        switch ($sslInfo['status']) {
            case 'valid':
                echo '<td><span class="ssl-status ssl-valid"><i class="fas fa-check-circle"></i> Geçerli</span></td>';
                break;
            case 'warning':
                echo '<td><span class="ssl-status ssl-warning"><i class="fas fa-exclamation-triangle"></i> Uyarı</span></td>';
                break;
            case 'expired':
                echo '<td><span class="ssl-status ssl-expired"><i class="fas fa-times-circle"></i> Süresi Dolmuş</span></td>';
                break;
            default:
                echo '<td><span class="ssl-status ssl-expired"><i class="fas fa-question-circle"></i> Hata</span></td>';
        }
        
        echo '<td>' . ($sslInfo['days'] ?? 'N/A') . ' gün</td>';
        echo '<td>' . ($sslInfo['start_date'] ?? 'N/A') . '</td>';
        echo '<td>' . ($sslInfo['end_date'] ?? 'N/A') . '</td>';
        
        // Sertifika detayları için dropdown
        echo '<td>
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#certDetails' . $data['id'] . '">
                    <i class="fas fa-info-circle"></i> Detaylar
                </button>
                
                <!-- Modal -->
                <div class="modal fade" id="certDetails' . $data['id'] . '" tabindex="-1" aria-labelledby="certDetailsLabel' . $data['id'] . '" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="certDetailsLabel' . $data['id'] . '">SSL Sertifika Detayları</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th>Yayıncı:</th>
                                        <td>' . ($sslInfo['issuer'] ?? 'N/A') . '</td>
                                    </tr>
                                    <tr>
                                        <th>Konu:</th>
                                        <td>' . ($sslInfo['subject'] ?? 'N/A') . '</td>
                                    </tr>
                                    <tr>
                                        <th>Seri Numarası:</th>
                                        <td>' . ($sslInfo['serial_number'] ?? 'N/A') . '</td>
                                    </tr>
                                    <tr>
                                        <th>İmza Algoritması:</th>
                                        <td>' . ($sslInfo['signature_algorithm'] ?? 'N/A') . '</td>
                                    </tr>
                                    <tr>
                                        <th>Sertifika Versiyonu:</th>
                                        <td>' . ($sslInfo['version'] ?? 'N/A') . '</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
              </td>';
        
        echo '<td>
                <button class="btn btn-sm btn-danger delete-domain" data-id="' . $data['id'] . '">
                    <i class="fas fa-trash"></i> Sil
                </button>
              </td>';
        echo '</tr>';
    }
    
    echo '</tbody></table></div>';
    
} catch(PDOException $e) {
    echo '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
}
?> 