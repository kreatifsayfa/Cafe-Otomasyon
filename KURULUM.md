# Cafe Otomasyonu - Kurulum ve Kullanım Kılavuzu

## 🚀 Hızlı Kurulum

### 1. Veritabanı Kurulumu

1. XAMPP/WAMP/LAMP başlatın
2. phpMyAdmin'e gidin (http://localhost/phpmyadmin)
3. `database.sql` dosyasını içe aktarın
4. Veritabanı otomatik oluşturulacak ve demo veriler yüklenecek

### 2. Veritabanı Ayarları

`config/database.php` dosyasını düzenleyin (gerekirse):
```php
private $host = "localhost";
private $db_name = "cafe_otomasyon";
private $username = "root";
private $password = "";
```

### 3. Giriş

Tarayıcıda açın: `http://localhost/ads/login.php`

**Varsayılan Giriş Bilgileri:**
- E-posta: `admin@cafe.com`
- Şifre: `admin123`

⚠️ **Not:** Eğer şifre çalışmazsa, `setup_admin.php` dosyasını çalıştırın.

## 📋 Sistem Özellikleri

### ✅ Tam İşlevsel Modüller

1. **Masa Yönetimi**
   - Masa ekleme, düzenleme, silme
   - Masa durumu takibi (Boş, Dolu, Rezerve, Temizlik)
   - Masa bazlı sipariş alma

2. **Sipariş Yönetimi**
   - Masa seçerek sipariş alma
   - Müşteri seçimi (opsiyonel)
   - Sepet yönetimi
   - Sipariş durumu takibi
   - Gerçek zamanlı güncellemeler

3. **Mutfak Görünümü**
   - Bekleyen siparişler
   - Hazırlanan siparişler
   - Hazır siparişler
   - Sipariş durumu güncelleme
   - Otomatik bildirimler

4. **Hesap Kesme**
   - Sipariş seçimi
   - İndirim uygulama
   - Ödeme tipi seçimi (Nakit, Kredi Kartı, Havale, Karma)
   - Fiş yazdırma
   - Otomatik masa boşaltma
   - Müşteri puan sistemi

5. **Menü Yönetimi**
   - Kategori yönetimi
   - Ürün ekleme, düzenleme
   - Fiyat yönetimi
   - Stok takibi

6. **Stok Yönetimi**
   - Stok ekleme, düzenleme, silme
   - Minimum stok uyarıları
   - Stok hareketleri
   - Tedarikçi bilgileri

7. **Personel Yönetimi**
   - Personel ekleme, düzenleme, silme
   - Rol yönetimi (Admin, Garson, Kasiyer, Mutfak)
   - Maaş takibi
   - Durum yönetimi

8. **Müşteri Yönetimi**
   - Müşteri ekleme, düzenleme
   - Puan sistemi
   - Toplam harcama takibi
   - Arama ve filtreleme

9. **Rezervasyon Sistemi**
   - Rezervasyon ekleme, düzenleme, silme
   - Tarih bazlı görüntüleme
   - Masa rezervasyonu
   - Durum yönetimi

10. **Raporlama**
    - Günlük satış raporları
    - Aylık satış raporları
    - En çok satan ürünler
    - Ödeme tipi dağılımı
    - Grafikler (Chart.js)

11. **Bildirim Sistemi**
    - Yeni sipariş bildirimleri
    - Stok uyarıları
    - Sistem bildirimleri
    - Okundu/Okunmadı takibi

12. **Aktivite Logları**
    - Tüm işlemlerin kaydı
    - Personel bazlı takip
    - IP adresi ve tarayıcı bilgisi

## 🎯 Kullanım Senaryoları

### Senaryo 1: Garson Sipariş Alıyor

1. Ana sayfadan veya Masalar sayfasından masa seçin
2. Sipariş sayfasında müşteri seçin (opsiyonel)
3. Kategorilerden ürün seçin
4. Sepete ekleyin
5. Siparişi gönderin
6. Mutfak otomatik bildirim alır

### Senaryo 2: Mutfak Sipariş Hazırlıyor

1. Mutfak Görünümü sayfasına gidin
2. Bekleyen siparişleri görün
3. "Hazırlanmaya Başla" butonuna tıklayın
4. Hazır olduğunda "Hazır" butonuna tıklayın
5. Garson teslim edildiğinde "Teslim Edildi" butonuna tıklayın

### Senaryo 3: Kasiyer Hesap Kesiyor

1. Hesap Kesme sayfasına gidin
2. Masa seçin
3. Siparişi seçin
4. İndirim uygulayın (gerekirse)
5. Ödeme tipini seçin
6. "Hesabı Kes ve Ödeme Al" butonuna tıklayın
7. Fiş yazdırmak için "Fiş Yazdır" butonuna tıklayın

### Senaryo 4: Admin Rapor İnceleme

1. Raporlar sayfasına gidin
2. Günlük veya aylık rapor seçin
3. Grafikleri inceleyin
4. En çok satan ürünleri görün

## 🔐 Rol Bazlı Erişim

- **Admin:** Tüm özelliklere erişim
- **Garson:** Sipariş alma, masa yönetimi
- **Kasiyer:** Hesap kesme, raporlar
- **Mutfak:** Mutfak görünümü, sipariş durumu güncelleme

## 📊 Demo Veriler

Sistem kurulumunda otomatik olarak yüklenen demo veriler:

- ✅ 8 Müşteri
- ✅ 4 Personel (Admin, Garson, Kasiyer, Mutfak)
- ✅ 12 Masa
- ✅ 60+ Ürün (5 kategoride)
- ✅ 15 Stok Kaydı
- ✅ 5 Tedarikçi
- ✅ 5 Rezervasyon
- ✅ 13 Sipariş (detaylarıyla)
- ✅ Ödemeler ve kasa özetleri
- ✅ Bildirimler ve aktivite logları

## 🛠️ Teknik Detaylar

- **Backend:** PHP 7.4+
- **Veritabanı:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Kütüphaneler:** Chart.js (grafikler), Font Awesome (ikonlar)
- **Güvenlik:** Password hashing, SQL injection koruması, XSS koruması
- **Responsive:** Mobil uyumlu tasarım

## ⚠️ Önemli Notlar

1. Production ortamında `error_reporting` kapatılmalı
2. `setup_admin.php` dosyası güvenlik için silinmeli
3. Veritabanı şifreleri güçlü olmalı
4. Düzenli yedekleme yapılmalı

## 🐛 Sorun Giderme

**Sorun:** Admin şifresi çalışmıyor
**Çözüm:** `setup_admin.php` dosyasını çalıştırın

**Sorun:** Veritabanı bağlantı hatası
**Çözüm:** `config/database.php` dosyasındaki bilgileri kontrol edin

**Sorun:** Sayfa bulunamadı hatası
**Çözüm:** `.htaccess` dosyası oluşturun veya URL yapısını kontrol edin

## 📞 Destek

Sistem tamamen işlevsel ve production'a hazırdır. Tüm özellikler test edilmiştir.


