-- Cafe Otomasyonu Veritabanı Yapısı
-- MySQL/MariaDB için hazırlanmıştır

CREATE DATABASE IF NOT EXISTS cafe_otomasyon CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE cafe_otomasyon;

-- Personel Tablosu
CREATE TABLE IF NOT EXISTS personel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefon VARCHAR(20),
    sifre VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'garson', 'kasiyer', 'mutfak') DEFAULT 'garson',
    maas DECIMAL(10,2),
    baslangic_tarihi DATE,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Müşteri Tablosu
CREATE TABLE IF NOT EXISTS musteri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(100) NOT NULL,
    telefon VARCHAR(20),
    email VARCHAR(100),
    adres TEXT,
    dogum_tarihi DATE,
    puan INT DEFAULT 0,
    toplam_harcama DECIMAL(10,2) DEFAULT 0.00,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Masa Tablosu
CREATE TABLE IF NOT EXISTS masa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    masa_no VARCHAR(10) UNIQUE NOT NULL,
    kapasite INT NOT NULL,
    konum VARCHAR(50),
    durum ENUM('bos', 'dolu', 'rezerve', 'temizlik') DEFAULT 'bos',
    aciklama TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Menü Kategorileri
CREATE TABLE IF NOT EXISTS menu_kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_adi VARCHAR(100) NOT NULL,
    aciklama TEXT,
    sira INT DEFAULT 0,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Menü Ürünleri
CREATE TABLE IF NOT EXISTS menu_urun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    urun_adi VARCHAR(100) NOT NULL,
    aciklama TEXT,
    fiyat DECIMAL(10,2) NOT NULL,
    resim VARCHAR(255),
    stok_var_mi BOOLEAN DEFAULT TRUE,
    stok_miktari INT DEFAULT 0,
    birim VARCHAR(20) DEFAULT 'adet',
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    sira INT DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES menu_kategori(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Stok Tablosu
CREATE TABLE IF NOT EXISTS stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_id INT,
    malzeme_adi VARCHAR(100) NOT NULL,
    miktar DECIMAL(10,2) NOT NULL,
    birim VARCHAR(20) DEFAULT 'kg',
    minimum_stok DECIMAL(10,2) DEFAULT 0,
    tedarikci VARCHAR(100),
    son_alim_tarihi DATE,
    son_alim_fiyati DECIMAL(10,2),
    aciklama TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (urun_id) REFERENCES menu_urun(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Rezervasyon Tablosu
CREATE TABLE IF NOT EXISTS rezervasyon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    musteri_id INT,
    masa_id INT NOT NULL,
    rezervasyon_tarihi DATE NOT NULL,
    rezervasyon_saati TIME NOT NULL,
    kisi_sayisi INT NOT NULL,
    durum ENUM('beklemede', 'onaylandi', 'iptal', 'tamamlandi') DEFAULT 'beklemede',
    notlar TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (musteri_id) REFERENCES musteri(id) ON DELETE SET NULL,
    FOREIGN KEY (masa_id) REFERENCES masa(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Sipariş Tablosu
CREATE TABLE IF NOT EXISTS siparis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    masa_id INT NOT NULL,
    musteri_id INT,
    personel_id INT NOT NULL,
    siparis_no VARCHAR(20) UNIQUE NOT NULL,
    durum ENUM('beklemede', 'hazirlaniyor', 'hazir', 'teslim_edildi', 'iptal') DEFAULT 'beklemede',
    toplam_tutar DECIMAL(10,2) DEFAULT 0.00,
    indirim_tutari DECIMAL(10,2) DEFAULT 0.00,
    kdv_tutari DECIMAL(10,2) DEFAULT 0.00,
    odeme_durumu ENUM('beklemede', 'odendi', 'kismi_odendi') DEFAULT 'beklemede',
    odeme_tipi ENUM('nakit', 'kredi_karti', 'havale', 'karma') DEFAULT 'nakit',
    notlar TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (masa_id) REFERENCES masa(id) ON DELETE RESTRICT,
    FOREIGN KEY (musteri_id) REFERENCES musteri(id) ON DELETE SET NULL,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Sipariş Detayları
CREATE TABLE IF NOT EXISTS siparis_detay (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siparis_id INT NOT NULL,
    urun_id INT NOT NULL,
    adet INT NOT NULL DEFAULT 1,
    birim_fiyat DECIMAL(10,2) NOT NULL,
    toplam_fiyat DECIMAL(10,2) NOT NULL,
    notlar TEXT,
    durum ENUM('beklemede', 'hazirlaniyor', 'hazir', 'teslim_edildi', 'iptal') DEFAULT 'beklemede',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siparis_id) REFERENCES siparis(id) ON DELETE CASCADE,
    FOREIGN KEY (urun_id) REFERENCES menu_urun(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Ödeme Tablosu
CREATE TABLE IF NOT EXISTS odeme (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siparis_id INT NOT NULL,
    personel_id INT NOT NULL,
    odeme_tipi ENUM('nakit', 'kredi_karti', 'havale', 'diger') NOT NULL,
    tutar DECIMAL(10,2) NOT NULL,
    kdv_tutari DECIMAL(10,2) DEFAULT 0.00,
    indirim_tutari DECIMAL(10,2) DEFAULT 0.00,
    odeme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notlar TEXT,
    FOREIGN KEY (siparis_id) REFERENCES siparis(id) ON DELETE RESTRICT,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Kasa İşlemleri
CREATE TABLE IF NOT EXISTS kasa_islem (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personel_id INT NOT NULL,
    islem_tipi ENUM('giris', 'cikis', 'transfer', 'diger') NOT NULL,
    tutar DECIMAL(10,2) NOT NULL,
    aciklama TEXT,
    islem_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Günlük Kasa Özeti
CREATE TABLE IF NOT EXISTS kasa_ozet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarih DATE UNIQUE NOT NULL,
    baslangic_bakiye DECIMAL(10,2) DEFAULT 0.00,
    nakit_giris DECIMAL(10,2) DEFAULT 0.00,
    kredi_karti_giris DECIMAL(10,2) DEFAULT 0.00,
    toplam_giris DECIMAL(10,2) DEFAULT 0.00,
    nakit_cikis DECIMAL(10,2) DEFAULT 0.00,
    kredi_karti_cikis DECIMAL(10,2) DEFAULT 0.00,
    toplam_cikis DECIMAL(10,2) DEFAULT 0.00,
    kasa_bakiye DECIMAL(10,2) DEFAULT 0.00,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Varsayılan Admin Kullanıcı (şifre: admin123)
-- NOT: Eğer şifre çalışmazsa, setup_admin.php dosyasını çalıştırın
INSERT INTO personel (ad_soyad, email, telefon, sifre, rol, durum) VALUES
('Admin Kullanıcı', 'admin@cafe.com', '05551234567', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'admin', 'aktif');

-- Örnek Kategoriler
INSERT INTO menu_kategori (kategori_adi, sira) VALUES
('İçecekler', 1),
('Yemekler', 2),
('Tatlılar', 3),
('Kahvaltı', 4),
('Atıştırmalıklar', 5);

-- Bildirimler Tablosu
CREATE TABLE IF NOT EXISTS bildirim (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personel_id INT,
    baslik VARCHAR(200) NOT NULL,
    mesaj TEXT NOT NULL,
    tip ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    okundu BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Aktivite Logları
CREATE TABLE IF NOT EXISTS aktivite_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personel_id INT,
    islem_tipi VARCHAR(50) NOT NULL,
    tablo_adi VARCHAR(50),
    kayit_id INT,
    aciklama TEXT,
    ip_adresi VARCHAR(45),
    user_agent TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tedarikçiler
CREATE TABLE IF NOT EXISTS tedarikci (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_adi VARCHAR(100) NOT NULL,
    yetkili_kisi VARCHAR(100),
    telefon VARCHAR(20),
    email VARCHAR(100),
    adres TEXT,
    vergi_no VARCHAR(20),
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Müşteri Puan Geçmişi
CREATE TABLE IF NOT EXISTS musteri_puan_gecmis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    musteri_id INT NOT NULL,
    puan INT NOT NULL,
    aciklama VARCHAR(255),
    siparis_id INT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (musteri_id) REFERENCES musteri(id) ON DELETE CASCADE,
    FOREIGN KEY (siparis_id) REFERENCES siparis(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Personel İzinleri
CREATE TABLE IF NOT EXISTS personel_izin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personel_id INT NOT NULL,
    izin_adi VARCHAR(50) NOT NULL,
    durum BOOLEAN DEFAULT TRUE,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE CASCADE,
    UNIQUE KEY unique_izin (personel_id, izin_adi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Stok Hareketleri
CREATE TABLE IF NOT EXISTS stok_hareket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stok_id INT NOT NULL,
    hareket_tipi ENUM('giris', 'cikis', 'transfer', 'sayim') NOT NULL,
    miktar DECIMAL(10,2) NOT NULL,
    birim_fiyat DECIMAL(10,2),
    tedarikci_id INT,
    personel_id INT,
    aciklama TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stok_id) REFERENCES stok(id) ON DELETE CASCADE,
    FOREIGN KEY (tedarikci_id) REFERENCES tedarikci(id) ON DELETE SET NULL,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Örnek Masalar
INSERT INTO masa (masa_no, kapasite, konum, durum) VALUES
('M1', 2, 'Pencere', 'bos'),
('M2', 4, 'Orta', 'bos'),
('M3', 4, 'Orta', 'dolu'),
('M4', 6, 'Bahçe', 'bos'),
('M5', 2, 'Pencere', 'bos'),
('M6', 4, 'Orta', 'bos'),
('M7', 8, 'Bahçe', 'rezerve'),
('M8', 2, 'Pencere', 'bos'),
('M9', 4, 'Salon', 'bos'),
('M10', 6, 'Bahçe', 'bos'),
('M11', 2, 'Pencere', 'bos'),
('M12', 4, 'Salon', 'bos');

-- Demo Personel
INSERT INTO personel (ad_soyad, email, telefon, sifre, rol, maas, durum) VALUES
('Ahmet Yılmaz', 'ahmet@cafe.com', '05551234568', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'garson', 8500.00, 'aktif'),
('Ayşe Demir', 'ayse@cafe.com', '05551234569', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'kasiyer', 9000.00, 'aktif'),
('Mehmet Kaya', 'mehmet@cafe.com', '05551234570', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'mutfak', 8000.00, 'aktif'),
('Fatma Şahin', 'fatma@cafe.com', '05551234571', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'garson', 8500.00, 'aktif');

-- Demo Müşteriler
INSERT INTO musteri (ad_soyad, telefon, email, adres, puan, toplam_harcama) VALUES
('Ali Veli', '05321234567', 'ali@email.com', 'İstanbul, Kadıköy', 150, 2500.00),
('Zeynep Kaya', '05321234568', 'zeynep@email.com', 'İstanbul, Beşiktaş', 320, 4800.00),
('Can Öz', '05321234569', 'can@email.com', 'İstanbul, Şişli', 85, 1200.00),
('Elif Yıldız', '05321234570', 'elif@email.com', 'İstanbul, Üsküdar', 200, 3000.00),
('Burak Çelik', '05321234571', 'burak@email.com', 'İstanbul, Bakırköy', 450, 6800.00),
('Selin Arslan', '05321234572', 'selin@email.com', 'İstanbul, Ataşehir', 180, 2700.00),
('Emre Doğan', '05321234573', 'emre@email.com', 'İstanbul, Beylikdüzü', 95, 1400.00),
('Deniz Yılmaz', '05321234574', 'deniz@email.com', 'İstanbul, Kartal', 280, 4200.00);

-- Demo Ürünler (İçecekler)
INSERT INTO menu_urun (kategori_id, urun_adi, aciklama, fiyat, stok_var_mi, stok_miktari, sira) VALUES
(1, 'Türk Kahvesi', 'Geleneksel Türk kahvesi, lokum ile', 45.00, FALSE, 0, 1),
(1, 'Espresso', 'İtalyan espresso, sıcak ve yoğun', 35.00, FALSE, 0, 2),
(1, 'Cappuccino', 'Espresso, buharda ısıtılmış süt ve süt köpüğü', 50.00, FALSE, 0, 3),
(1, 'Latte', 'Espresso ve buharda ısıtılmış süt', 55.00, FALSE, 0, 4),
(1, 'Americano', 'Espresso ve sıcak su', 40.00, FALSE, 0, 5),
(1, 'Mocha', 'Espresso, çikolata ve süt', 60.00, FALSE, 0, 6),
(1, 'Filtre Kahve', 'Taze demlenmiş filtre kahve', 30.00, FALSE, 0, 7),
(1, 'Soğuk Kahve', 'Buzlu soğuk kahve', 45.00, FALSE, 0, 8),
(1, 'Çay', 'Türk çayı, şekerli/şekersiz', 15.00, FALSE, 0, 9),
(1, 'Bitki Çayı', 'Adaçayı, Ihlamur, Nane-Limon', 25.00, FALSE, 0, 10),
(1, 'Taze Sıkılmış Portakal Suyu', 'Günlük taze portakal suyu', 35.00, TRUE, 50, 11),
(1, 'Limonata', 'Taze limonata', 30.00, TRUE, 30, 12),
(1, 'Ayran', 'Ev yapımı ayran', 20.00, TRUE, 40, 13),
(1, 'Kola', '330ml kutu', 25.00, TRUE, 100, 14),
(1, 'Su', '500ml pet şişe', 10.00, TRUE, 200, 15);

-- Demo Ürünler (Yemekler)
INSERT INTO menu_urun (kategori_id, urun_adi, aciklama, fiyat, stok_var_mi, stok_miktari, sira) VALUES
(2, 'Hamburger', 'Et, marul, domates, soğan, özel sos', 120.00, TRUE, 25, 1),
(2, 'Cheeseburger', 'Et, peynir, marul, domates, özel sos', 135.00, TRUE, 20, 2),
(2, 'Tavuk Burger', 'Izgara tavuk, marul, domates, mayonez', 110.00, TRUE, 30, 3),
(2, 'Pizza Margherita', 'Domates, mozzarella, fesleğen', 150.00, TRUE, 15, 4),
(2, 'Pizza Pepperoni', 'Domates, mozzarella, pepperoni', 170.00, TRUE, 12, 5),
(2, 'Pizza Karışık', 'Domates, mozzarella, sucuk, mantar, mısır', 180.00, TRUE, 10, 6),
(2, 'Lazanya', 'Etli lazanya, beşamel sos', 140.00, TRUE, 8, 7),
(2, 'Spagetti Carbonara', 'Makarna, krema, pastırma, parmesan', 130.00, TRUE, 15, 8),
(2, 'Tavuk Şiş', 'Izgara tavuk şiş, pilav, salata', 125.00, TRUE, 20, 9),
(2, 'Köfte', 'Izgara köfte, pilav, salata', 115.00, TRUE, 18, 10),
(2, 'Döner', 'Tavuk/Et döner, pilav, salata', 100.00, TRUE, 25, 11),
(2, 'Lahmacun', 'İnce hamur, kıyma, soğan, maydanoz', 35.00, TRUE, 40, 12),
(2, 'Pide', 'Kaşarlı, kıymalı, kuşbaşılı', 80.00, TRUE, 20, 13),
(2, 'Mantı', 'Ev yapımı mantı, yoğurt, sarımsaklı sos', 90.00, TRUE, 12, 14),
(2, 'Çorba', 'Mercimek, tavuk, domates çorbası', 45.00, TRUE, 30, 15);

-- Demo Ürünler (Tatlılar)
INSERT INTO menu_urun (kategori_id, urun_adi, aciklama, fiyat, stok_var_mi, stok_miktari, sira) VALUES
(3, 'Baklava', 'Cevizli baklava, 6 dilim', 85.00, TRUE, 20, 1),
(3, 'Sütlaç', 'Ev yapımı sütlaç', 35.00, TRUE, 25, 2),
(3, 'Kazandibi', 'Geleneksel kazandibi', 40.00, TRUE, 15, 3),
(3, 'Tiramisu', 'İtalyan tiramisu', 65.00, TRUE, 12, 4),
(3, 'Cheesecake', 'New York style cheesecake', 70.00, TRUE, 10, 5),
(3, 'Brownie', 'Çikolatalı brownie, dondurma ile', 55.00, TRUE, 18, 6),
(3, 'Waffle', 'Belçika waffle, çikolata/çilek sos', 75.00, TRUE, 15, 7),
(3, 'Pancake', '3 adet pancake, bal/çikolata', 60.00, TRUE, 20, 8),
(3, 'Profiterol', '6 adet profiterol, çikolata sos', 70.00, TRUE, 12, 9),
(3, 'Magnolia', 'Çilekli magnolia', 65.00, TRUE, 14, 10);

-- Demo Ürünler (Kahvaltı)
INSERT INTO menu_urun (kategori_id, urun_adi, aciklama, fiyat, stok_var_mi, stok_miktari, sira) VALUES
(4, 'Serpme Kahvaltı', 'Peynir çeşitleri, zeytin, bal, reçel, yumurta, domates, salatalık', 180.00, TRUE, 15, 1),
(4, 'Menemen', 'Domates, biber, yumurta, soğan', 75.00, TRUE, 25, 2),
(4, 'Omlet', 'Kaşarlı, mantarlı, sucuklu', 70.00, TRUE, 30, 3),
(4, 'Sucuklu Yumurta', 'Taze yumurta, sucuk', 80.00, TRUE, 25, 4),
(4, 'Pastırma Yumurta', 'Taze yumurta, pastırma', 90.00, TRUE, 20, 5),
(4, 'Tost', 'Kaşarlı, sucuklu, karışık', 45.00, TRUE, 40, 6),
(4, 'Sandviç', 'Tavuk, ton balığı, karışık', 55.00, TRUE, 30, 7),
(4, 'Croissant', 'Tereyağlı croissant, reçel/bal', 35.00, TRUE, 35, 8),
(4, 'Gözleme', 'Peynirli, patatesli, ıspanaklı', 50.00, TRUE, 25, 9),
(4, 'Börek', 'Peynirli, patatesli, kıymalı', 40.00, TRUE, 30, 10);

-- Demo Ürünler (Atıştırmalıklar)
INSERT INTO menu_urun (kategori_id, urun_adi, aciklama, fiyat, stok_var_mi, stok_miktari, sira) VALUES
(5, 'Patates Kızartması', 'Taze patates, özel baharat', 45.00, TRUE, 30, 1),
(5, 'Soğan Halkası', 'Kızarmış soğan halkası, sos', 50.00, TRUE, 25, 2),
(5, 'Mozzarella Çubukları', 'Kızarmış mozzarella, marinara sos', 65.00, TRUE, 20, 3),
(5, 'Nachos', 'Tortilla cipsi, peynir, jalapeno', 70.00, TRUE, 18, 4),
(5, 'Çıtır Tavuk', 'Kızarmış tavuk parçaları, sos', 85.00, TRUE, 22, 5),
(5, 'Köfte Tabağı', 'Mini köfteler, sos, ekmek', 95.00, TRUE, 15, 6),
(5, 'Çips', 'Patates cipsi, çeşitli aromalar', 25.00, TRUE, 50, 7),
(5, 'Kuruyemiş Tabağı', 'Karışık kuruyemiş', 60.00, TRUE, 20, 8),
(5, 'Zeytin Tabağı', 'Yeşil, siyah zeytin', 35.00, TRUE, 30, 9),
(5, 'Peynir Tabağı', 'Kaşar, beyaz peynir, tulum', 55.00, TRUE, 25, 10);

-- Demo Stok Kayıtları
INSERT INTO stok (malzeme_adi, miktar, birim, minimum_stok, tedarikci, son_alim_fiyati) VALUES
('Kahve Çekirdeği', 50.00, 'kg', 10.00, 'Kahve Dünyası', 450.00),
('Süt', 100.00, 'lt', 20.00, 'Süt Üreticileri', 25.00),
('Şeker', 75.00, 'kg', 15.00, 'Şeker Fabrikası', 35.00),
('Un', 200.00, 'kg', 50.00, 'Un Fabrikası', 18.00),
('Yumurta', 500.00, 'adet', 100.00, 'Çiftlik', 2.50),
('Peynir', 30.00, 'kg', 10.00, 'Süt Üreticileri', 85.00),
('Et', 40.00, 'kg', 15.00, 'Et Üreticileri', 250.00),
('Tavuk', 35.00, 'kg', 12.00, 'Tavuk Çiftliği', 120.00),
('Domates', 60.00, 'kg', 20.00, 'Sebze Üreticileri', 15.00),
('Soğan', 50.00, 'kg', 15.00, 'Sebze Üreticileri', 12.00),
('Patates', 80.00, 'kg', 25.00, 'Sebze Üreticileri', 10.00),
('Zeytin', 25.00, 'kg', 8.00, 'Zeytin Üreticileri', 45.00),
('Bal', 20.00, 'kg', 5.00, 'Arıcılık', 180.00),
('Reçel', 30.00, 'kg', 10.00, 'Reçel Üreticileri', 55.00),
('Çay', 15.00, 'kg', 5.00, 'Çay Üreticileri', 120.00);

-- Demo Tedarikçiler
INSERT INTO tedarikci (firma_adi, yetkili_kisi, telefon, email, adres, vergi_no) VALUES
('Kahve Dünyası', 'Mehmet Yılmaz', '02121234567', 'info@kahvedunyasi.com', 'İstanbul, Kadıköy', '1234567890'),
('Süt Üreticileri A.Ş.', 'Ayşe Demir', '02121234568', 'info@sutureticileri.com', 'İstanbul, Ümraniye', '2345678901'),
('Sebze Üreticileri', 'Ali Kaya', '02121234569', 'info@sebzeureticileri.com', 'İstanbul, Pendik', '3456789012'),
('Et Üreticileri Ltd.', 'Fatma Şahin', '02121234570', 'info@etureticileri.com', 'İstanbul, Beylikdüzü', '4567890123'),
('Çiftlik Ürünleri', 'Can Öz', '02121234571', 'info@ciftlikurunleri.com', 'İstanbul, Çatalca', '5678901234');

-- Demo Rezervasyonlar
INSERT INTO rezervasyon (musteri_id, masa_id, rezervasyon_tarihi, rezervasyon_saati, kisi_sayisi, durum, notlar) VALUES
(1, 7, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:00:00', 6, 'onaylandi', 'Doğum günü kutlaması'),
(2, 4, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '20:00:00', 4, 'beklemede', 'Akşam yemeği'),
(3, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '12:00:00', 2, 'onaylandi', 'Öğle yemeği'),
(4, 2, CURDATE(), '18:30:00', 3, 'onaylandi', 'Aile yemeği'),
(5, 10, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', 5, 'beklemede', 'İş yemeği');

-- Demo Siparişler (Bugün)
INSERT INTO siparis (masa_id, musteri_id, personel_id, siparis_no, durum, toplam_tutar, kdv_tutari, indirim_tutari, odeme_durumu, odeme_tipi, olusturma_tarihi) VALUES
(3, 1, 2, CONCAT('SP-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-0001'), 'teslim_edildi', 285.00, 57.00, 0.00, 'odendi', 'nakit', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 2, 2, CONCAT('SP-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-0002'), 'hazir', 450.00, 90.00, 0.00, 'beklemede', 'nakit', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(5, 3, 2, CONCAT('SP-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-0003'), 'hazirlaniyor', 320.00, 64.00, 20.00, 'beklemede', 'kredi_karti', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 4, 2, CONCAT('SP-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-0004'), 'beklemede', 195.00, 39.00, 0.00, 'beklemede', 'nakit', DATE_SUB(NOW(), INTERVAL 15 MINUTE));

-- Demo Sipariş Detayları
-- Sipariş 1 (Masa 3)
INSERT INTO siparis_detay (siparis_id, urun_id, adet, birim_fiyat, toplam_fiyat, durum) VALUES
(1, 1, 2, 45.00, 90.00, 'teslim_edildi'),
(1, 16, 1, 120.00, 120.00, 'teslim_edildi'),
(1, 31, 1, 35.00, 35.00, 'teslim_edildi'),
(1, 40, 1, 40.00, 40.00, 'teslim_edildi');

-- Sipariş 2 (Masa 1)
INSERT INTO siparis_detay (siparis_id, urun_id, adet, birim_fiyat, toplam_fiyat, durum) VALUES
(2, 3, 2, 50.00, 100.00, 'hazir'),
(2, 19, 1, 150.00, 150.00, 'hazir'),
(2, 20, 1, 170.00, 170.00, 'hazirlaniyor'),
(2, 26, 1, 30.00, 30.00, 'hazir');

-- Sipariş 3 (Masa 5)
INSERT INTO siparis_detay (siparis_id, urun_id, adet, birim_fiyat, toplam_fiyat, durum) VALUES
(3, 2, 1, 35.00, 35.00, 'hazirlaniyor'),
(3, 17, 2, 135.00, 270.00, 'hazirlaniyor'),
(3, 27, 1, 25.00, 25.00, 'hazirlaniyor');

-- Sipariş 4 (Masa 2)
INSERT INTO siparis_detay (siparis_id, urun_id, adet, birim_fiyat, toplam_fiyat, durum) VALUES
(4, 9, 2, 15.00, 30.00, 'beklemede'),
(4, 23, 1, 110.00, 110.00, 'beklemede'),
(4, 28, 1, 35.00, 35.00, 'beklemede'),
(4, 32, 1, 20.00, 20.00, 'beklemede');

-- Demo Ödemeler
INSERT INTO odeme (siparis_id, personel_id, odeme_tipi, tutar, kdv_tutari, indirim_tutari, odeme_tarihi) VALUES
(1, 3, 'nakit', 285.00, 57.00, 0.00, DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Demo Siparişler (Dün)
INSERT INTO siparis (masa_id, musteri_id, personel_id, siparis_no, durum, toplam_tutar, kdv_tutari, indirim_tutari, odeme_durumu, odeme_tipi, olusturma_tarihi) VALUES
(4, 5, 2, CONCAT('SP-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), '-0001'), 'teslim_edildi', 520.00, 104.00, 0.00, 'odendi', 'kredi_karti', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 6, 2, CONCAT('SP-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), '-0002'), 'teslim_edildi', 380.00, 76.00, 30.00, 'odendi', 'nakit', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 7, 2, CONCAT('SP-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), '-0003'), 'teslim_edildi', 245.00, 49.00, 0.00, 'odendi', 'nakit', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(9, 8, 2, CONCAT('SP-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), '-0004'), 'teslim_edildi', 650.00, 130.00, 50.00, 'odendi', 'karma', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Dünkü Sipariş Detayları
INSERT INTO siparis_detay (siparis_id, urun_id, adet, birim_fiyat, toplam_fiyat, durum) VALUES
(5, 4, 2, 55.00, 110.00, 'teslim_edildi'),
(5, 21, 2, 125.00, 250.00, 'teslim_edildi'),
(5, 22, 1, 115.00, 115.00, 'teslim_edildi'),
(5, 33, 1, 45.00, 45.00, 'teslim_edildi'),
(6, 5, 1, 40.00, 40.00, 'teslim_edildi'),
(6, 18, 1, 140.00, 140.00, 'teslim_edildi'),
(6, 19, 1, 150.00, 150.00, 'teslim_edildi'),
(6, 34, 1, 50.00, 50.00, 'teslim_edildi'),
(7, 1, 1, 45.00, 45.00, 'teslim_edildi'),
(7, 24, 1, 80.00, 80.00, 'teslim_edildi'),
(7, 25, 1, 35.00, 35.00, 'teslim_edildi'),
(7, 29, 1, 50.00, 50.00, 'teslim_edildi'),
(7, 35, 1, 35.00, 35.00, 'teslim_edildi'),
(8, 3, 3, 50.00, 150.00, 'teslim_edildi'),
(8, 6, 2, 60.00, 120.00, 'teslim_edildi'),
(8, 20, 1, 170.00, 170.00, 'teslim_edildi'),
(8, 21, 1, 125.00, 125.00, 'teslim_edildi'),
(8, 36, 1, 60.00, 60.00, 'teslim_edildi'),
(8, 37, 1, 25.00, 25.00, 'teslim_edildi');

-- Dünkü Ödemeler
INSERT INTO odeme (siparis_id, personel_id, odeme_tipi, tutar, kdv_tutari, indirim_tutari, odeme_tarihi) VALUES
(5, 3, 'kredi_karti', 520.00, 104.00, 0.00, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 3, 'nakit', 380.00, 76.00, 30.00, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(7, 3, 'nakit', 245.00, 49.00, 0.00, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 3, 'karma', 650.00, 130.00, 50.00, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Demo Siparişler (2 Gün Önce)
INSERT INTO siparis (masa_id, musteri_id, personel_id, siparis_no, durum, toplam_tutar, kdv_tutari, indirim_tutari, odeme_durumu, odeme_tipi, olusturma_tarihi) VALUES
(1, 1, 2, CONCAT('SP-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '%Y%m%d'), '-0001'), 'teslim_edildi', 295.00, 59.00, 0.00, 'odendi', 'nakit', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 2, CONCAT('SP-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '%Y%m%d'), '-0002'), 'teslim_edildi', 410.00, 82.00, 0.00, 'odendi', 'kredi_karti', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 3, 2, CONCAT('SP-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '%Y%m%d'), '-0003'), 'teslim_edildi', 180.00, 36.00, 0.00, 'odendi', 'nakit', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(7, 4, 2, CONCAT('SP-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '%Y%m%d'), '-0004'), 'teslim_edildi', 720.00, 144.00, 0.00, 'odendi', 'kredi_karti', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 5, 2, CONCAT('SP-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '%Y%m%d'), '-0005'), 'teslim_edildi', 340.00, 68.00, 0.00, 'odendi', 'nakit', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- 2 Gün Önceki Sipariş Detayları
INSERT INTO siparis_detay (siparis_id, urun_id, adet, birim_fiyat, toplam_fiyat, durum) VALUES
(9, 1, 2, 45.00, 90.00, 'teslim_edildi'),
(9, 16, 1, 120.00, 120.00, 'teslim_edildi'),
(9, 31, 1, 35.00, 35.00, 'teslim_edildi'),
(9, 40, 1, 50.00, 50.00, 'teslim_edildi'),
(10, 4, 2, 55.00, 110.00, 'teslim_edildi'),
(10, 19, 1, 150.00, 150.00, 'teslim_edildi'),
(10, 20, 1, 170.00, 170.00, 'teslim_edildi'),
(10, 26, 1, 30.00, 30.00, 'teslim_edildi'),
(11, 9, 2, 15.00, 30.00, 'teslim_edildi'),
(11, 23, 1, 110.00, 110.00, 'teslim_edildi'),
(11, 28, 1, 35.00, 35.00, 'teslim_edildi'),
(11, 32, 1, 20.00, 20.00, 'teslim_edildi'),
(12, 3, 4, 50.00, 200.00, 'teslim_edildi'),
(12, 6, 2, 60.00, 120.00, 'teslim_edildi'),
(12, 19, 2, 150.00, 300.00, 'teslim_edildi'),
(12, 26, 2, 30.00, 60.00, 'teslim_edildi'),
(12, 40, 2, 40.00, 80.00, 'teslim_edildi'),
(13, 2, 1, 35.00, 35.00, 'teslim_edildi'),
(13, 17, 2, 135.00, 270.00, 'teslim_edildi'),
(13, 27, 1, 25.00, 25.00, 'teslim_edildi');

-- 2 Gün Önceki Ödemeler
INSERT INTO odeme (siparis_id, personel_id, odeme_tipi, tutar, kdv_tutari, indirim_tutari, odeme_tarihi) VALUES
(9, 3, 'nakit', 295.00, 59.00, 0.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 3, 'kredi_karti', 410.00, 82.00, 0.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(11, 3, 'nakit', 180.00, 36.00, 0.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(12, 3, 'kredi_karti', 720.00, 144.00, 0.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(13, 3, 'nakit', 340.00, 68.00, 0.00, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Demo Müşteri Puan Geçmişi
INSERT INTO musteri_puan_gecmis (musteri_id, puan, aciklama, siparis_id) VALUES
(1, 50, 'Sipariş tamamlandı', 1),
(1, 100, 'Sipariş tamamlandı', 9),
(2, 50, 'Sipariş tamamlandı', 10),
(3, 50, 'Sipariş tamamlandı', 11),
(4, 50, 'Sipariş tamamlandı', 12),
(5, 50, 'Sipariş tamamlandı', 5),
(5, 50, 'Sipariş tamamlandı', 13),
(6, 50, 'Sipariş tamamlandı', 6),
(7, 50, 'Sipariş tamamlandı', 7),
(8, 50, 'Sipariş tamamlandı', 8);

-- Demo Stok Hareketleri
INSERT INTO stok_hareket (stok_id, hareket_tipi, miktar, birim_fiyat, tedarikci_id, personel_id, aciklama, olusturma_tarihi) VALUES
(1, 'giris', 50.00, 450.00, 1, 1, 'Aylık kahve alımı', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'giris', 100.00, 25.00, 2, 1, 'Haftalık süt alımı', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'giris', 75.00, 35.00, 1, 1, 'Şeker alımı', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(4, 'giris', 200.00, 18.00, 1, 1, 'Aylık un alımı', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(5, 'giris', 500.00, 2.50, 5, 1, 'Yumurta alımı', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 'giris', 30.00, 85.00, 2, 1, 'Peynir alımı', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 'cikis', 5.00, NULL, NULL, 1, 'Günlük kullanım', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'cikis', 10.00, NULL, NULL, 1, 'Günlük kullanım', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'cikis', 3.00, NULL, NULL, 1, 'Günlük kullanım', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Demo Kasa Özeti (Bugün)
INSERT INTO kasa_ozet (tarih, baslangic_bakiye, nakit_giris, kredi_karti_giris, toplam_giris, kasa_bakiye) VALUES
(CURDATE(), 5000.00, 342.00, 624.00, 966.00, 5966.00);

-- Demo Kasa Özeti (Dün)
INSERT INTO kasa_ozet (tarih, baslangic_bakiye, nakit_giris, kredi_karti_giris, toplam_giris, kasa_bakiye) VALUES
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 4500.00, 995.00, 1170.00, 2165.00, 6665.00);

-- Demo Kasa Özeti (2 Gün Önce)
INSERT INTO kasa_ozet (tarih, baslangic_bakiye, nakit_giris, kredi_karti_giris, toplam_giris, kasa_bakiye) VALUES
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 4000.00, 815.00, 1230.00, 2045.00, 6045.00);

-- Demo Bildirimler
INSERT INTO bildirim (personel_id, baslik, mesaj, tip, link) VALUES
(NULL, 'Yeni Sipariş', 'Masa 1 için yeni sipariş alındı', 'info', 'siparis.php'),
(NULL, 'Stok Uyarısı', 'Kahve çekirdeği stoğu minimum seviyenin altında', 'warning', 'stok.php'),
(2, 'Sipariş Hazır', 'Masa 3 siparişi hazır', 'success', 'siparis.php'),
(4, 'Rezervasyon Hatırlatma', 'Yarın saat 19:00 için rezervasyon var', 'info', 'rezervasyon.php'),
(NULL, 'Günlük Rapor', 'Bugünkü satış raporu hazır', 'info', 'raporlar.php');

-- Demo Aktivite Logları
INSERT INTO aktivite_log (personel_id, islem_tipi, tablo_adi, kayit_id, aciklama, olusturma_tarihi) VALUES
(1, 'giris', 'sistem', NULL, 'Sisteme giriş yapıldı', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(2, 'siparis_olustur', 'siparis', 1, 'Yeni sipariş oluşturuldu', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'siparis_olustur', 'siparis', 2, 'Yeni sipariş oluşturuldu', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(3, 'odeme_al', 'odeme', 1, 'Ödeme alındı', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 'siparis_olustur', 'siparis', 3, 'Yeni sipariş oluşturuldu', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 'siparis_olustur', 'siparis', 4, 'Yeni sipariş oluşturuldu', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(1, 'stok_guncelle', 'stok', 1, 'Stok güncellendi', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'musteri_ekle', 'musteri', 1, 'Yeni müşteri eklendi', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 'rezervasyon_ekle', 'rezervasyon', 1, 'Rezervasyon eklendi', DATE_SUB(NOW(), INTERVAL 2 DAY));

