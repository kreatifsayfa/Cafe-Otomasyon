-- Cafe Otomasyonu - Gelişmiş Özellikler için Veritabanı Güncellemeleri

USE cafe_otomasyon;

-- Kampanyalar Tablosu
CREATE TABLE IF NOT EXISTS kampanyalar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kampanya_adi VARCHAR(100) NOT NULL,
    baslangic_tarihi DATE NOT NULL,
    bitis_tarihi DATE NOT NULL,
    indirim_tipi ENUM('yuzde', 'tutar', 'urun', 'musteri') DEFAULT 'yuzde',
    indirim_degeri DECIMAL(10,2) NOT NULL,
    min_tutar DECIMAL(10,2) DEFAULT 0,
    urun_id INT,
    kategori_id INT,
    aciklama TEXT,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (urun_id) REFERENCES menu_urun(id) ON DELETE SET NULL,
    FOREIGN KEY (kategori_id) REFERENCES menu_kategori(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Masa Birleştirme Tablosu
CREATE TABLE IF NOT EXISTS masa_birlesim (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ana_masa_id INT NOT NULL,
    birlesen_masa_id INT NOT NULL,
    personel_id INT NOT NULL,
    aciklama TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ana_masa_id) REFERENCES masa(id) ON DELETE CASCADE,
    FOREIGN KEY (birlesen_masa_id) REFERENCES masa(id) ON DELETE CASCADE,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Masa Transfer Tablosu
CREATE TABLE IF NOT EXISTS masa_transfer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eski_masa_id INT NOT NULL,
    yeni_masa_id INT NOT NULL,
    siparis_id INT NOT NULL,
    personel_id INT NOT NULL,
    aciklama TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (eski_masa_id) REFERENCES masa(id) ON DELETE RESTRICT,
    FOREIGN KEY (yeni_masa_id) REFERENCES masa(id) ON DELETE RESTRICT,
    FOREIGN KEY (siparis_id) REFERENCES siparis(id) ON DELETE CASCADE,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Giderler Tablosu
CREATE TABLE IF NOT EXISTS giderler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gider_tipi VARCHAR(50) NOT NULL,
    aciklama TEXT,
    tutar DECIMAL(10,2) NOT NULL,
    tarih DATE NOT NULL,
    personel_id INT,
    kategori VARCHAR(50),
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Gelirler Tablosu (Sipariş dışı)
CREATE TABLE IF NOT EXISTS gelirler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gelir_tipi VARCHAR(50) NOT NULL,
    aciklama TEXT,
    tutar DECIMAL(10,2) NOT NULL,
    tarih DATE NOT NULL,
    personel_id INT,
    kategori VARCHAR(50),
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Personel Performans Tablosu
CREATE TABLE IF NOT EXISTS personel_performans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personel_id INT NOT NULL,
    tarih DATE NOT NULL,
    toplam_siparis INT DEFAULT 0,
    toplam_tutar DECIMAL(10,2) DEFAULT 0,
    ortalama_siparis_tutari DECIMAL(10,2) DEFAULT 0,
    calisma_suresi INT DEFAULT 0, -- dakika
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE CASCADE,
    UNIQUE KEY unique_personel_tarih (personel_id, tarih)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Müşteri Puan Geçmişi Tablosu
CREATE TABLE IF NOT EXISTS musteri_puan_gecmis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    musteri_id INT NOT NULL,
    puan INT NOT NULL,
    aciklama TEXT,
    siparis_id INT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (musteri_id) REFERENCES musteri(id) ON DELETE CASCADE,
    FOREIGN KEY (siparis_id) REFERENCES siparis(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Demo Kampanya Verileri
INSERT INTO kampanyalar (kampanya_adi, baslangic_tarihi, bitis_tarihi, indirim_tipi, indirim_degeri, min_tutar, aciklama, durum) VALUES
('Hoş Geldin İndirimi', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'yuzde', 10, 50, 'Yeni müşterilere özel %10 indirim', 'aktif'),
('Hafta Sonu Özel', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'yuzde', 15, 100, 'Hafta sonu siparişlerinde %15 indirim', 'aktif'),
('Kahve İndirimi', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'urun', 20, 0, 'Tüm kahve ürünlerinde %20 indirim', 'aktif');


