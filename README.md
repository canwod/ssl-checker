# SSL Sertifika Kontrol Paneli

Bu proje, birden fazla domain için SSL sertifikalarının durumunu takip etmenizi sağlayan bir web uygulamasıdır. Domainlerinizin SSL sertifikalarının geçerlilik sürelerini kontrol edebilir, yaklaşan son kullanma tarihlerini takip edebilir ve detaylı raporlar alabilirsiniz.

## Özellikler

- 🔒 SSL sertifika durumu kontrolü
- ⏰ Kalan gün sayısı takibi
- 🎨 Renk kodlu durum göstergeleri (Yeşil/Sarı/Kırmızı)
- 📊 Detaylı sertifika bilgileri
- 📥 Excel ve PDF rapor çıktısı
- 🔄 Otomatik yenileme (5 dakikada bir)
- 📱 Mobil uyumlu tasarım

## Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Composer
- XAMPP/WAMP/LAMP benzeri bir web sunucusu
- PHP GD Eklentisi (önerilen)
- PHP OpenSSL Eklentisi

## Kurulum

1. Projeyi bilgisayarınıza indirin:
```bash
git clone [proje-url]
cd ssl-checker
```

2. Composer bağımlılıklarını yükleyin:
```bash
composer install
```

3. Veritabanını oluşturun:
- phpMyAdmin'e gidin
- `database.sql` dosyasını içe aktarın veya SQL kodunu çalıştırın

4. Veritabanı bağlantısını yapılandırın:
- `config.php` dosyasını açın
- Veritabanı bilgilerinizi güncelleyin:
```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ssl_checker';
```

## Kullanım

1. Web tarayıcınızda projeyi açın:
```
http://localhost/ssl-checker
```

2. Domain eklemek için:
- "Yeni Domain Ekle" formunu kullanın
- Domain adresini girin (örn: example.com)
- "Ekle" butonuna tıklayın

3. SSL durumlarını kontrol etmek için:
- Domainler otomatik olarak listelenecektir
- Her domain için SSL durumu renkli göstergelerle belirtilir:
  - 🟢 Yeşil: SSL geçerli
  - 🟡 Sarı: SSL süresi yakında dolacak (30 günden az)
  - 🔴 Kırmızı: SSL süresi dolmuş

4. Raporlama:
- Excel raporu için: "Excel İndir" butonunu kullanın
- PDF raporu için: "PDF İndir" butonunu kullanın

## Renk Kodları

- Geçerli SSL: `#28a745` (Yeşil)
- Uyarı (30 günden az): `#ffc107` (Sarı)
- Süresi Dolmuş: `#dc3545` (Kırmızı)

## Güvenlik

- SQL Injection koruması
- XSS koruması
- Input validasyonu
- Güvenli SSL kontrolü

## Sorun Giderme

1. Excel dosyası açılmıyorsa:
- PHP GD eklentisinin yüklü olduğundan emin olun
- `php.ini` dosyasında `extension=gd` satırını aktifleştirin
- Web sunucusunu yeniden başlatın

2. SSL kontrolleri çalışmıyorsa:
- PHP OpenSSL eklentisinin yüklü olduğundan emin olun
- Domain adresinin doğru formatta olduğunu kontrol edin

3. Veritabanı bağlantı hatası:
- Veritabanı bilgilerinin doğru olduğunu kontrol edin
- MySQL servisinin çalıştığından emin olun

## Katkıda Bulunma

1. Bu depoyu fork edin
2. Yeni bir branch oluşturun (`git checkout -b feature/yeniOzellik`)
3. Değişikliklerinizi commit edin (`git commit -am 'Yeni özellik eklendi'`)
4. Branch'inizi push edin (`git push origin feature/yeniOzellik`)
5. Pull Request oluşturun

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Daha fazla bilgi için `LICENSE` dosyasına bakın. 