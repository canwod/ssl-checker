<?php
ob_start(); // Çıktı tamponlamasını başlat
require_once 'config.php';
require_once 'vendor/autoload.php'; // Composer autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Dompdf\Dompdf;
use Dompdf\Options;

// Export tipini al
$type = isset($_GET['type']) ? $_GET['type'] : '';

if (!in_array($type, ['pdf', 'excel'])) {
    die('Geçersiz export tipi');
}

try {
    // Domainleri veritabanından al
    $stmt = $db->query("SELECT * FROM domains ORDER BY created_at DESC");
    $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // SSL bilgilerini topla
    $domainData = [];
    foreach ($domains as $domain) {
        $sslInfo = checkSSL($domain['domain']);
        $domainData[] = [
            'domain' => $domain['domain'],
            'status' => $sslInfo['status'] ?? 'error',
            'days' => $sslInfo['days'] ?? 'N/A',
            'start_date' => $sslInfo['start_date'] ?? 'N/A',
            'end_date' => $sslInfo['end_date'] ?? 'N/A',
            'issuer' => $sslInfo['issuer'] ?? 'N/A',
            'subject' => $sslInfo['subject'] ?? 'N/A'
        ];
    }

    if ($type === 'pdf') {
        // PDF oluştur
        $html = '<h1>SSL Sertifika Durumları</h1>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%">';
        $html .= '<tr>
                    <th>Domain</th>
                    <th>SSL Durumu</th>
                    <th>Kalan Gün</th>
                    <th>Başlangıç Tarihi</th>
                    <th>Bitiş Tarihi</th>
                    <th>Yayıncı</th>
                    <th>Konu</th>
                </tr>';
        
        foreach ($domainData as $data) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($data['domain']) . '</td>';
            $html .= '<td>' . getStatusText($data['status']) . '</td>';
            $html .= '<td>' . $data['days'] . ' gün</td>';
            $html .= '<td>' . $data['start_date'] . '</td>';
            $html .= '<td>' . $data['end_date'] . '</td>';
            $html .= '<td>' . htmlspecialchars($data['issuer']) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['subject']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // PDF ayarları
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        // PDF'i indir
        $dompdf->stream('ssl_durumlari.pdf', ['Attachment' => true]);
        
    } elseif ($type === 'excel') {
        // Önceki çıktıları temizle
        ob_end_clean();
        
        // Excel oluştur
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Başlık stilini ayarla
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4ECDC4'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Başlıkları ayarla
        $headers = [
            'A1' => 'Domain',
            'B1' => 'SSL Durumu',
            'C1' => 'Kalan Gün',
            'D1' => 'Başlangıç Tarihi',
            'E1' => 'Bitiş Tarihi',
            'F1' => 'Yayıncı',
            'G1' => 'Konu'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        
        // Verileri ekle
        $row = 2;
        foreach ($domainData as $data) {
            $sheet->setCellValue('A' . $row, $data['domain']);
            $sheet->setCellValue('B' . $row, getStatusText($data['status']));
            $sheet->setCellValue('C' . $row, $data['days'] . ' gün');
            $sheet->setCellValue('D' . $row, $data['start_date']);
            $sheet->setCellValue('E' . $row, $data['end_date']);
            $sheet->setCellValue('F' . $row, $data['issuer']);
            $sheet->setCellValue('G' . $row, $data['subject']);
            
            // Durum rengini ayarla
            $statusColor = '';
            if ($data['status'] === 'valid') {
                $statusColor = '28a745'; // Yeşil
            } elseif ($data['status'] === 'warning') {
                $statusColor = 'ffc107'; // Sarı
            } else {
                $statusColor = 'dc3545'; // Kırmızı
            }
            
            $sheet->getStyle('B' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($statusColor);
            
            $row++;
        }
        
        // Sütun genişliklerini otomatik ayarla
        foreach(range('A','G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Tüm hücrelere border ekle
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:G'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Excel dosyasını indir
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="ssl_durumlari_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        $writer = new Xls($spreadsheet);
        $writer->save('php://output');
        exit();
    }
    
} catch(PDOException $e) {
    die('Veritabanı hatası: ' . $e->getMessage());
} catch(Exception $e) {
    die('Hata: ' . $e->getMessage());
}

function getStatusText($status) {
    switch ($status) {
        case 'valid':
            return 'Geçerli';
        case 'warning':
            return 'Uyarı';
        case 'expired':
            return 'Süresi Dolmuş';
        default:
            return 'Hata';
    }
}

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
            
            $validToDate = new DateTime('@' . $validTo);
            $nowDate = new DateTime('@' . $now);
            $interval = $nowDate->diff($validToDate);
            $daysLeft = $interval->days;
            
            $startDate = date('d.m.Y H:i:s', $validFrom);
            $endDate = date('d.m.Y H:i:s', $validTo);
            
            $issuer = isset($cert['issuer']['CN']) ? $cert['issuer']['CN'] : 'Bilinmiyor';
            $subject = isset($cert['subject']['CN']) ? $cert['subject']['CN'] : 'Bilinmiyor';
            
            if ($daysLeft < 0) {
                return [
                    'status' => 'expired',
                    'days' => abs($daysLeft),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'issuer' => $issuer,
                    'subject' => $subject
                ];
            } elseif ($daysLeft < 30) {
                return [
                    'status' => 'warning',
                    'days' => $daysLeft,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'issuer' => $issuer,
                    'subject' => $subject
                ];
            } else {
                return [
                    'status' => 'valid',
                    'days' => $daysLeft,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'issuer' => $issuer,
                    'subject' => $subject
                ];
            }
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
    
    return ['status' => 'error', 'message' => 'SSL sertifikası bulunamadı'];
}
?> 