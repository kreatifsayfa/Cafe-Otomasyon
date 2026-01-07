# Cafe Otomasyonu

Kapsamlı cafe yönetim sistemi. Masalar, siparişler, menü, hesap kesme, raporlama ve daha fazlası.

## Özellikler

- ✅ Masa Yönetimi (Durum takibi, kapasite, konum)
- ✅ Menü Yönetimi (Kategoriler, ürünler, fiyatlar)
- ✅ Sipariş Alma ve Takibi
- ✅ Hesap Kesme ve Ödeme İşlemleri
- ✅ Raporlama (Günlük/Aylık satış raporları, en çok satan ürünler)
- ✅ Stok Yönetimi
- ✅ Personel Yönetimi
- ✅ Müşteri Yönetimi
- ✅ Rezervasyon Sistemi
- ✅ Kasa İşlemleri

## Kurulum

1. **Veritabanı Kurulumu:**
   - XAMPP/WAMP/LAMP kurulu olmalı
   - phpMyAdmin'e girin
   - `database.sql` dosyasını içe aktarın

2. **Dosya Yerleşimi:**
   - Tüm dosyaları `htdocs/ads/` klasörüne kopyalayın

3. **Veritabanı Ayarları:**
   - `config/database.php` dosyasında veritabanı bilgilerini kontrol edin
   - Varsayılan: localhost, root, boş şifre

4. **Giriş Bilgileri:**
   - E-posta: `admin@cafe.com`
   - Şifre: `admin123`

5. **Şifre Sorunu:**
   - Eğer admin şifresi çalışmazsa, `setup_admin.php` dosyasını tarayıcıda çalıştırın
   - Bu dosya admin şifresini otomatik olarak düzeltecektir
   - İşlem tamamlandıktan sonra güvenlik için `setup_admin.php` dosyasını silin

## Kullanım

1. Tarayıcıda `http://localhost/ads/login.php` adresine gidin
2. Admin bilgileriyle giriş yapın
3. Ana sayfadan masaları görüntüleyin
4. Masa seçerek sipariş alın
5. Hesap kesme sayfasından ödemeleri alın
6. Raporlar sayfasından satış istatistiklerini görüntüleyin

## Teknolojiler

- PHP 7.4+
- MySQL/MariaDB
- HTML5, CSS3, JavaScript
- Font Awesome Icons

## Dosya Yapısı

```
ads/
├── api/              # Backend API dosyaları
├── assets/           # CSS ve JS dosyaları
├── config/           # Yapılandırma dosyaları
├── includes/         # Include edilen dosyalar
├── database.sql      # Veritabanı yapısı
├── index.php         # Ana sayfa
├── login.php         # Giriş sayfası
├── masalar.php       # Masa yönetimi
├── siparis.php       # Sipariş alma
├── menu.php          # Menü yönetimi
├── hesap.php         # Hesap kesme
├── raporlar.php      # Raporlar
├── stok.php          # Stok yönetimi
├── personel.php      # Personel yönetimi
├── musteri.php       # Müşteri yönetimi
└── rezervasyon.php   # Rezervasyonlar
```

## Notlar

- Şifreler `password_hash()` ile hash'lenmiştir
- Tüm para birimi işlemleri Türk Lirası (₺) üzerinden yapılmaktadır
- KDV oranı %20 olarak ayarlanmıştır
- Responsive tasarım ile mobil uyumludur

## Geliştirme

Sistem modüler yapıda tasarlanmıştır. Yeni özellikler eklemek için:

1. İlgili API dosyasını `api/` klasörüne ekleyin
2. Frontend sayfasını oluşturun
3. Gerekli CSS/JS stillerini ekleyin

## Lisans

Bu proje eğitim amaçlı geliştirilmiştir.

