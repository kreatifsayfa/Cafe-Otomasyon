# Kurulum Notları - Yeni Özellikler

## ÖNEMLİ: Veritabanı Güncellemesi

1. **phpMyAdmin'e girin** (http://localhost/phpmyadmin)
2. `cafe_otomasyon` veritabanını seçin
3. **SQL sekmesine** gidin
4. `database_updates_v2.sql` dosyasının içeriğini kopyalayıp yapıştırın
5. **Çalıştır** butonuna tıklayın

## Yeni Özellikler

### 1. Ayarlar Sayfası
- **Erişim:** Admin panelinden "Ayarlar" menüsü
- **Özellikler:**
  - Genel ayarlar (Site adı, KDV oranı, Para birimi)
  - Yazıcı yönetimi (Mutfak, Bar, Kasa yazıcıları)
  - Kullanıcı yetkileri yönetimi

### 2. Yazıcı Entegrasyonu
- **Lokasyon Bazlı Yazdırma:**
  - Mutfak yazıcısı: Yemek siparişleri
  - Bar yazıcısı: İçecek siparişleri
  - Kasa yazıcısı: Fiş yazdırma
- **Yazıcı Tipleri:**
  - Network yazıcı (IP adresi ile)
  - Local yazıcı (Windows/Linux)
- **Otomatik Yazdırma:** Ayarlardan açılıp kapatılabilir

### 3. Kullanıcı Yetkileri
- **Garson Yetkileri:**
  - Sipariş al
  - Masa birleştir
  - Masa değiştir
- **Şef/Barmen Yetkileri:**
  - Sipariş hazır işaretle
- **Admin Yetkileri:**
  - Tüm yetkiler
  - Sipariş iptal

### 4. Sipariş İptal Sistemi
- **Kısmi İptal:** Sadece bir ürünü iptal et
- **Tam İptal:** Tüm siparişi iptal et
- **Yetki Kontrolü:** Sadece yetkili kullanıcılar iptal edebilir

### 5. Siparişler Sayfası
- **Önce Masa Seçimi:** Masaları görüntüle ve seç
- **Sipariş Yönetimi:** Siparişleri görüntüle, hazır işaretle, iptal et
- **Sipariş Hazır Butonu:** Şef/Barmen için

### 6. QR Menü Garson Girişi
- **Garson Giriş Sayfası:** `garson_giris.php`
- **Sadece Garson:** QR menü sadece garson hesapları ile erişilebilir

## Kullanım Kılavuzu

### Yazıcı Kurulumu

1. **Ayarlar** sayfasına gidin
2. **Yazıcı Ayarları** bölümünden **Yeni Yazıcı** butonuna tıklayın
3. Yazıcı bilgilerini girin:
   - **Yazıcı Adı:** Windows'ta görünen yazıcı adı
   - **Lokasyon:** Mutfak, Bar veya Kasa
   - **Yazıcı Tipi:** Fiş, Etiket veya Mutfak
   - **IP Adresi:** Network yazıcı için (opsiyonel)
   - **Port:** Varsayılan 9100

### Kullanıcı Yetkileri Ayarlama

1. **Ayarlar** sayfasına gidin
2. **Kullanıcı Yetkileri** bölümünden personel seçin
3. Yetkileri açıp kapatın:
   - ✅ Sipariş Al
   - ✅ Sipariş İptal
   - ✅ Ödeme Al
   - ✅ Masa Birleştir
   - ✅ Masa Değiştir
   - ✅ Sipariş Hazır
   - ✅ Rapor Görüntüle

### Sipariş İşlemleri

1. **Siparişler** sayfasına gidin
2. Masa seçin
3. Siparişleri görüntüleyin
4. **Sipariş Hazır** butonuna tıklayın (Şef/Barmen)
5. **İptal** butonuna tıklayarak siparişi iptal edin (Yetkili kullanıcılar)

## Sorun Giderme

### Yazıcı Çalışmıyor
- Yazıcının IP adresini kontrol edin
- Windows'ta yazıcı adının doğru olduğundan emin olun
- Firewall ayarlarını kontrol edin

### Yetki Sorunları
- Kullanıcının yetkilerini Ayarlar sayfasından kontrol edin
- Admin her zaman tüm yetkilere sahiptir

### Veritabanı Hataları
- `database_updates_v2.sql` dosyasını tekrar çalıştırın
- Tabloların oluşturulduğundan emin olun

## Notlar

- Mutfak görünümü kaldırıldı, yerine siparişler sayfasında "Sipariş Hazır" butonu kullanılıyor
- Yazıcılar lokasyona göre otomatik olarak siparişleri alır
- Garsonlar sadece sipariş alabilir, iptal edemez (yetki verilmediyse)
- Şef ve barmen siparişleri hazır işaretleyebilir

