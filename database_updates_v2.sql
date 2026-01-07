-- Cafe Otomasyonu - Veritabanı Güncellemeleri v2
-- Yazıcı entegrasyonu, kullanıcı yetkileri, ürün lokasyonları, sipariş iptal sistemi

USE cafe_otomasyon;

-- Yazıcı Tablosu
CREATE TABLE IF NOT EXISTS yazici (
    id INT AUTO_INCREMENT PRIMARY KEY,
    yazici_adi VARCHAR(100) NOT NULL,
    lokasyon ENUM('mutfak', 'bar', 'kasa') NOT NULL,
    yazici_tipi ENUM('fis', 'etiket', 'mutfak') DEFAULT 'fis',
    ip_adresi VARCHAR(50),
    port INT DEFAULT 9100,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    aciklama TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Kullanıcı Yetkileri Tablosu
CREATE TABLE IF NOT EXISTS kullanici_yetki (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personel_id INT NOT NULL,
    yetki_adi VARCHAR(50) NOT NULL,
    durum BOOLEAN DEFAULT TRUE,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE CASCADE,
    UNIQUE KEY unique_yetki (personel_id, yetki_adi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Ürün Lokasyon Tablosu (hangi ürün hangi lokasyona gidecek)
-- Önce kolonun var olup olmadığını kontrol et
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'menu_urun' 
AND COLUMN_NAME = 'lokasyon';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE menu_urun ADD COLUMN lokasyon ENUM(\'mutfak\', \'bar\', \'kasa\') DEFAULT \'mutfak\' AFTER kategori_id',
    'SELECT "Kolon zaten mevcut" AS mesaj');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Sipariş İptal Tablosu
CREATE TABLE IF NOT EXISTS siparis_iptal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siparis_id INT,
    siparis_detay_id INT,
    iptal_tipi ENUM('tam', 'kismi') NOT NULL,
    personel_id INT NOT NULL,
    iptal_nedeni TEXT,
    iptal_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siparis_id) REFERENCES siparis(id) ON DELETE SET NULL,
    FOREIGN KEY (siparis_detay_id) REFERENCES siparis_detay(id) ON DELETE SET NULL,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Personel rol güncellemeleri (şef, barmen ekle)
ALTER TABLE personel 
MODIFY COLUMN rol ENUM('admin', 'garson', 'kasiyer', 'mutfak', 'sef', 'barmen') DEFAULT 'garson';

-- Ayarlar Tablosu
CREATE TABLE IF NOT EXISTS ayarlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ayar_adi VARCHAR(100) UNIQUE NOT NULL,
    ayar_degeri TEXT,
    ayar_tipi ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    aciklama TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Sipariş Detaylarına hazır durumu ekle (şef/barmen için)
SET @col_exists2 = 0;
SELECT COUNT(*) INTO @col_exists2 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'siparis_detay' 
AND COLUMN_NAME = 'hazirlayan_personel_id';

SET @sql2 = IF(@col_exists2 = 0,
    'ALTER TABLE siparis_detay ADD COLUMN hazirlayan_personel_id INT AFTER durum',
    'SELECT "Kolon zaten mevcut" AS mesaj');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Foreign key ekle (eğer yoksa)
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'siparis_detay' 
AND CONSTRAINT_NAME = 'fk_hazirlayan';

SET @sql3 = IF(@fk_exists = 0,
    'ALTER TABLE siparis_detay ADD CONSTRAINT fk_hazirlayan FOREIGN KEY (hazirlayan_personel_id) REFERENCES personel(id) ON DELETE SET NULL',
    'SELECT "Foreign key zaten mevcut" AS mesaj');
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- Varsayılan yazıcılar
INSERT INTO yazici (yazici_adi, lokasyon, yazici_tipi, durum) VALUES
('Mutfak Yazıcısı', 'mutfak', 'mutfak', 'aktif'),
('Bar Yazıcısı', 'bar', 'fis', 'aktif'),
('Kasa Yazıcısı', 'kasa', 'fis', 'aktif')
ON DUPLICATE KEY UPDATE yazici_adi = yazici_adi;

-- Varsayılan ayarlar
INSERT INTO ayarlar (ayar_adi, ayar_degeri, ayar_tipi, aciklama) VALUES
('site_adi', 'Cafe Otomasyonu', 'text', 'Site adı'),
('kdv_orani', '20', 'number', 'KDV oranı (%)'),
('para_birimi', 'TL', 'text', 'Para birimi'),
('otomatik_yazdir', '1', 'boolean', 'Sipariş alındığında otomatik yazdır'),
('mutfak_görünümü_aktif', '0', 'boolean', 'Mutfak görünümü aktif mi?')
ON DUPLICATE KEY UPDATE ayar_adi = ayar_adi;

-- Ürün lokasyonlarını güncelle (kategorilere göre)
UPDATE menu_urun mu
JOIN menu_kategori mk ON mu.kategori_id = mk.id
SET mu.lokasyon = CASE 
    WHEN mk.kategori_adi LIKE '%İçecek%' OR mk.kategori_adi LIKE '%Çay%' OR mk.kategori_adi LIKE '%Kahve%' THEN 'bar'
    WHEN mk.kategori_adi LIKE '%Yemek%' OR mk.kategori_adi LIKE '%Kahvaltı%' OR mk.kategori_adi LIKE '%Tatlı%' THEN 'mutfak'
    ELSE 'kasa'
END
WHERE mu.lokasyon IS NULL OR mu.lokasyon = '';

-- Varsayılan yetkiler (garson için)
INSERT INTO kullanici_yetki (personel_id, yetki_adi, durum)
SELECT id, 'siparis_al', TRUE FROM personel WHERE rol = 'garson'
ON DUPLICATE KEY UPDATE durum = TRUE;

INSERT INTO kullanici_yetki (personel_id, yetki_adi, durum)
SELECT id, 'masa_birlestir', TRUE FROM personel WHERE rol = 'garson'
ON DUPLICATE KEY UPDATE durum = TRUE;

INSERT INTO kullanici_yetki (personel_id, yetki_adi, durum)
SELECT id, 'masa_degistir', TRUE FROM personel WHERE rol = 'garson'
ON DUPLICATE KEY UPDATE durum = TRUE;

-- Şef ve barmen için sipariş hazır yetkisi
INSERT INTO kullanici_yetki (personel_id, yetki_adi, durum)
SELECT id, 'siparis_hazir', TRUE FROM personel WHERE rol IN ('sef', 'barmen', 'mutfak')
ON DUPLICATE KEY UPDATE durum = TRUE;

