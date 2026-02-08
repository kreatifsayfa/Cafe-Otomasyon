<?php
// Hata raporlamayı kapat - JSON API için
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Output buffering başlat - hataları yakalamak için
ob_start();

// JSON header'ı hemen ayarla
header('Content-Type: application/json; charset=utf-8');

// Tüm çıktıları yakala
try {
require_once '../config/config.php';
} catch(Exception $e) {
    @ob_clean();
    echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()]);
    @ob_end_flush();
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$is_qr_request = $action === 'yeni' && (isset($_GET['qr']) && $_GET['qr'] === '1');

if (!$is_qr_request) {
    checkLoginAPI();
}

function generateSiparisNo($db) {
    $tarih = date('Ymd');
    $max_deneme = 10; // Maksimum deneme sayısı
    $deneme = 0;
    
    while ($deneme < $max_deneme) {
        // Tarih + saat + mikrosaniye + rastgele sayı ile benzersiz numara üret
        $mikrosaniye = substr(str_replace('.', '', microtime(true)), -6, 6); // Son 6 hanesi
        $rastgele = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $siparis_no = 'SP-' . $tarih . '-' . $mikrosaniye . $rastgele;
        
        // Bu numara daha önce kullanılmış mı kontrol et
        $stmt = $db->prepare("SELECT COUNT(*) as sayi FROM siparis WHERE siparis_no = ?");
        $stmt->execute([$siparis_no]);
        $result = $stmt->fetch();
        
        if ($result['sayi'] == 0) {
            return $siparis_no;
        }
        
        $deneme++;
        usleep(1000); // 1ms bekle
    }
    
    // Son çare: COUNT + timestamp kullan
    $stmt = $db->prepare("SELECT COUNT(*) as sayi FROM siparis WHERE DATE(olusturma_tarihi) = ?");
    $stmt->execute([$tarih]);
    $result = $stmt->fetch();
    $numara = $result['sayi'] + 1;
    $timestamp = substr(time(), -6); // Son 6 hanesi
    return 'SP-' . $tarih . '-' . str_pad($numara, 4, '0', STR_PAD_LEFT) . '-' . $timestamp;
}

function getQrPersonelId($db) {
    $stmt = $db->query("SELECT id FROM personel WHERE rol = 'garson' AND durum = 'aktif' ORDER BY id LIMIT 1");
    $personel = $stmt->fetch();
    if ($personel) {
        return $personel['id'];
    }

    $stmt = $db->query("SELECT id FROM personel WHERE rol = 'admin' AND durum = 'aktif' ORDER BY id LIMIT 1");
    $personel = $stmt->fetch();
    if ($personel) {
        return $personel['id'];
    }

    $stmt = $db->query("SELECT id FROM personel WHERE durum = 'aktif' ORDER BY id LIMIT 1");
    $personel = $stmt->fetch();
    return $personel ? $personel['id'] : null;
}

switch($action) {
    case 'yeni':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $masa_id = intval($data['masa_id']);
            $musteri_id = isset($data['musteri_id']) ? intval($data['musteri_id']) : null;
            $urunler = $data['urunler'];
            $personel_id = $_SESSION['personel_id'] ?? null;

            if (!$personel_id && $is_qr_request) {
                $personel_id = getQrPersonelId($db);
            }

            if (!$personel_id) {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Sipariş için aktif personel bulunamadı. Lütfen yöneticiyle iletişime geçin.']);
                @ob_end_flush();
                exit();
            }
            
            try {
                $db->beginTransaction();
                
                // Sipariş oluştur
                $siparis_no = generateSiparisNo($db);
                $stmt = $db->prepare("INSERT INTO siparis (masa_id, musteri_id, personel_id, siparis_no) 
                                     VALUES (?, ?, ?, ?)");
                $stmt->execute([$masa_id, $musteri_id, $personel_id, $siparis_no]);
                $siparis_id = $db->lastInsertId();
                
                // Sipariş detaylarını ekle
                $toplam_tutar = 0;
                foreach ($urunler as $urun) {
                    // Hem 'id' hem 'urun_id' desteği
                    $urun_id = intval($urun['urun_id'] ?? $urun['id'] ?? 0);
                    $adet = intval($urun['adet']);
                    // Hem 'fiyat' hem 'birim_fiyat' desteği
                    $birim_fiyat = floatval($urun['birim_fiyat'] ?? $urun['fiyat'] ?? 0);
                    // Toplam fiyat hesaplanmışsa onu kullan, yoksa hesapla
                    $toplam_fiyat = floatval($urun['toplam_fiyat'] ?? ($adet * $birim_fiyat));
                    $toplam_tutar += $toplam_fiyat;
                    
                    $stmt = $db->prepare("INSERT INTO siparis_detay (siparis_id, urun_id, adet, birim_fiyat, toplam_fiyat) 
                                         VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$siparis_id, $urun_id, $adet, $birim_fiyat, $toplam_fiyat]);
                }
                
                // Toplam tutarı güncelle
                $kdv_tutari = $toplam_tutar * 0.20; // %20 KDV
                $stmt = $db->prepare("UPDATE siparis SET toplam_tutar = ?, kdv_tutari = ? WHERE id = ?");
                $stmt->execute([$toplam_tutar, $kdv_tutari, $siparis_id]);
                
                // Masa durumunu güncelle
                $stmt = $db->prepare("UPDATE masa SET durum = 'dolu' WHERE id = ?");
                $stmt->execute([$masa_id]);
                
                // Aktivite log
                require_once '../includes/log_activity.php';
                try {
                logActivity($db, 'siparis_olustur', 'siparis', $siparis_id, "Yeni sipariş: $siparis_no");
                } catch(Exception $e) {
                    // Log hatası sessizce geç
                }
                
                // Otomatik yazdırma (ayarlara göre)
                try {
                    $stmt = $db->query("SELECT ayar_degeri FROM ayarlar WHERE ayar_adi = 'otomatik_yazdir'");
                    $ayar = $stmt->fetch();
                    if ($ayar && $ayar['ayar_degeri'] == '1') {
                        require_once __DIR__ . '/yazici.php';
                        // Mutfak ve bar için ayrı ayrı yazdır
                        $stmt = $db->prepare("SELECT sd.*, u.urun_adi, u.lokasyon FROM siparis_detay sd 
                                            JOIN menu_urun u ON sd.urun_id = u.id 
                                            WHERE sd.siparis_id = ? AND u.lokasyon IN ('mutfak', 'bar')");
                        $stmt->execute([$siparis_id]);
                        $detaylar = $stmt->fetchAll();
                        
                        // Lokasyona göre grupla
                        $lokasyonlar = [];
                        foreach ($detaylar as $detay) {
                            $lokasyonlar[$detay['lokasyon']][] = $detay;
                        }
                        
                        foreach ($lokasyonlar as $lokasyon => $lokasyon_detaylar) {
                            $siparis_bilgi = ['siparis_no' => $siparis_no, 'masa_no' => '', 'olusturma_tarihi' => date('Y-m-d H:i:s')];
                            $masa_stmt = $db->prepare("SELECT masa_no FROM masa WHERE id = ?");
                            $masa_stmt->execute([$masa_id]);
                            $masa = $masa_stmt->fetch();
                            if ($masa) {
                                $siparis_bilgi['masa_no'] = $masa['masa_no'];
                            }
                            $icerik = mutfakSiparisFormati($siparis_bilgi, $lokasyon_detaylar);
                            yaziciyaYazdir($db, $lokasyon, $icerik, 'mutfak');
                        }
                    }
                } catch(Exception $e) {
                    // Yazdırma hatası sessizce geç
                }
                
                // Bildirim oluştur (şef ve barmen için)
                try {
                    $mutfak_personel = $db->query("SELECT id FROM personel WHERE rol IN ('mutfak', 'sef', 'barmen') AND durum = 'aktif'");
                while ($mutfak = $mutfak_personel->fetch()) {
                        createNotification($db, $mutfak['id'], 'Yeni Sipariş', "Masa {$masa_id} için yeni sipariş alındı: $siparis_no", 'info', 'index.php');
                    }
                } catch(Exception $e) {
                    // Bildirim hatası sessizce geç
                }
                
                $db->commit();
                
                // Output buffer'ı temizle ve JSON döndür
                @ob_clean();
                echo json_encode(['success' => true, 'siparis_id' => $siparis_id, 'siparis_no' => $siparis_no]);
                @ob_end_flush();
                exit();
            } catch(PDOException $e) {
                $db->rollBack();
                $error_message = $e->getMessage();
                
                // Daha anlaşılır hata mesajları
                if (strpos($error_message, 'Duplicate entry') !== false || strpos($error_message, 'UNIQUE constraint') !== false) {
                    if (strpos($error_message, 'siparis_no') !== false) {
                        $error_message = 'Sipariş numarası çakışması oluştu. Lütfen tekrar deneyin.';
                    } else {
                        $error_message = 'Bu kayıt zaten mevcut. Lütfen sayfayı yenileyin.';
                    }
                } elseif (strpos($error_message, 'FOREIGN KEY constraint') !== false) {
                    $error_message = 'Geçersiz masa veya ürün seçimi. Lütfen kontrol edin.';
                } elseif (strpos($error_message, 'SQLSTATE') !== false) {
                    $error_message = 'Veritabanı hatası oluştu. Lütfen tekrar deneyin.';
                }
                
                error_log("Sipariş oluşturma hatası: " . $e->getMessage());
                
                // Output buffer'ı temizle ve JSON döndür
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $error_message]);
                exit();
            } catch(Exception $e) {
                $db->rollBack();
                error_log("Sipariş oluşturma hatası: " . $e->getMessage());
                
                // Output buffer'ı temizle ve JSON döndür
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Sipariş oluşturulurken beklenmeyen bir hata oluştu: ' . $e->getMessage()]);
                exit();
            }
        }
        break;
        
    case 'listele':
        $masa_id = isset($_GET['masa_id']) ? intval($_GET['masa_id']) : 0;
        if ($masa_id > 0) {
            $stmt = $db->prepare("SELECT s.*, m.masa_no, mu.ad_soyad as musteri_adi 
                                  FROM siparis s 
                                  JOIN masa m ON s.masa_id = m.id 
                                  LEFT JOIN musteri mu ON s.musteri_id = mu.id 
                                  WHERE s.masa_id = ? AND s.odeme_durumu != 'odendi' 
                                  ORDER BY s.olusturma_tarihi DESC");
            $stmt->execute([$masa_id]);
        } else {
            $stmt = $db->query("SELECT s.*, m.masa_no, mu.ad_soyad as musteri_adi 
                               FROM siparis s 
                               JOIN masa m ON s.masa_id = m.id 
                               LEFT JOIN musteri mu ON s.musteri_id = mu.id 
                               ORDER BY s.olusturma_tarihi DESC LIMIT 50");
        }
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'detay':
        @ob_clean();
        $siparis_id = intval($_GET['siparis_id']);
        $stmt = $db->prepare("SELECT sd.*, u.urun_adi, u.aciklama 
                             FROM siparis_detay sd 
                             JOIN menu_urun u ON sd.urun_id = u.id 
                             WHERE sd.siparis_id = ?");
        $stmt->execute([$siparis_id]);
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'durum_guncelle':
        @ob_clean();
        if ($method == 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $siparis_id = intval($data['siparis_id']);
            $durum = cleanInput($data['durum']);
            
            try {
                $db->beginTransaction();
                
                // Sipariş durumunu güncelle
                $stmt = $db->prepare("UPDATE siparis SET durum = ? WHERE id = ?");
                $stmt->execute([$durum, $siparis_id]);
                
                // Sipariş detaylarının durumunu da güncelle
                $stmt = $db->prepare("UPDATE siparis_detay SET durum = ? WHERE siparis_id = ?");
                $stmt->execute([$durum, $siparis_id]);
                
                // Aktivite log
                require_once '../includes/log_activity.php';
                logActivity($db, 'siparis_durum_guncelle', 'siparis', $siparis_id, "Sipariş durumu: $durum");
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Durum güncellendi']);
            } catch(Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'detay_durum_guncelle':
        @ob_clean();
        if ($method == 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $detay_id = intval($data['detay_id']);
            $durum = cleanInput($data['durum']);
            
            $stmt = $db->prepare("UPDATE siparis_detay SET durum = ? WHERE id = ?");
            if ($stmt->execute([$durum, $detay_id])) {
                echo json_encode(['success' => true, 'message' => 'Detay durumu güncellendi']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'siparis_hazir':
        @ob_clean();
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $siparis_id = intval($data['siparis_id']);
            $detay_id = isset($data['detay_id']) ? intval($data['detay_id']) : null;
            
            try {
                $db->beginTransaction();
                
                if ($detay_id) {
                    // Sadece bir detayı hazır yap
                    $stmt = $db->prepare("UPDATE siparis_detay SET durum = 'hazir', hazirlayan_personel_id = ? WHERE id = ?");
                    $stmt->execute([$_SESSION['personel_id'], $detay_id]);
                    
                    // Eğer tüm detaylar hazırsa, siparişi de hazır yap
                    $stmt = $db->prepare("SELECT COUNT(*) as toplam, SUM(CASE WHEN durum = 'hazir' THEN 1 ELSE 0 END) as hazir_sayisi 
                                         FROM siparis_detay 
                                         WHERE siparis_id = ? AND durum != 'iptal'");
                    $stmt->execute([$siparis_id]);
                    $durum_kontrol = $stmt->fetch();
                    
                    if ($durum_kontrol['toplam'] == $durum_kontrol['hazir_sayisi']) {
                        $stmt = $db->prepare("UPDATE siparis SET durum = 'hazir' WHERE id = ?");
                        $stmt->execute([$siparis_id]);
                    }
                } else {
                    // Tüm siparişi hazır yap
                    $stmt = $db->prepare("UPDATE siparis SET durum = 'hazir' WHERE id = ?");
                    $stmt->execute([$siparis_id]);
                    
                    // Tüm detayları hazır yap
                    $stmt = $db->prepare("UPDATE siparis_detay SET durum = 'hazir', hazirlayan_personel_id = ? 
                                         WHERE siparis_id = ? AND durum != 'iptal'");
                    $stmt->execute([$_SESSION['personel_id'], $siparis_id]);
                }
                
                // Aktivite log
                require_once '../includes/log_activity.php';
                logActivity($db, 'siparis_hazir', 'siparis', $siparis_id, "Sipariş hazır işaretlendi");
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Sipariş hazır işaretlendi']);
            } catch(Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'mutfak_listele':
        @ob_clean();
        // Sadece ödenmemiş ve iptal edilmemiş siparişleri getir
        $stmt = $db->query("SELECT s.*, m.masa_no 
                           FROM siparis s 
                           JOIN masa m ON s.masa_id = m.id 
                           WHERE s.odeme_durumu != 'odendi' AND s.durum != 'iptal'
                           ORDER BY s.olusturma_tarihi DESC");
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'masa_hesap':
        @ob_clean();
        // Masa bazlı birleşik hesap - tüm ödenmemiş siparişleri ve detaylarını getir
        $masa_id = isset($_GET['masa_id']) ? intval($_GET['masa_id']) : 0;
        if ($masa_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz masa ID']);
            break;
        }
        
        try {
            // Masa bilgisini al
            $stmt = $db->prepare("SELECT * FROM masa WHERE id = ?");
            $stmt->execute([$masa_id]);
            $masa = $stmt->fetch();
            
            if (!$masa) {
                echo json_encode(['success' => false, 'message' => 'Masa bulunamadı']);
                break;
            }
            
            // Ödenmemiş siparişleri al
            $stmt = $db->prepare("SELECT s.*, m.masa_no, mu.ad_soyad as musteri_adi 
                                  FROM siparis s 
                                  JOIN masa m ON s.masa_id = m.id 
                                  LEFT JOIN musteri mu ON s.musteri_id = mu.id 
                                  WHERE s.masa_id = ? AND s.odeme_durumu != 'odendi' AND s.durum != 'iptal'
                                  ORDER BY s.olusturma_tarihi ASC");
            $stmt->execute([$masa_id]);
            $siparisler = $stmt->fetchAll();
            
            if (empty($siparisler)) {
                echo json_encode(['success' => false, 'message' => 'Bu masada ödenmemiş sipariş yok']);
                break;
            }
            
            // Tüm siparişlerin detaylarını al
            $siparis_ids = array_column($siparisler, 'id');
            $placeholders = str_repeat('?,', count($siparis_ids) - 1) . '?';
            $stmt = $db->prepare("SELECT sd.*, u.urun_adi, u.aciklama 
                                 FROM siparis_detay sd 
                                 JOIN menu_urun u ON sd.urun_id = u.id 
                                 WHERE sd.siparis_id IN ($placeholders) AND sd.durum != 'iptal'
                                 ORDER BY sd.siparis_id, sd.id");
            $stmt->execute($siparis_ids);
            $detaylar = $stmt->fetchAll();
            
            // Toplam tutarı hesapla
            $toplam_tutar = array_sum(array_column($siparisler, 'toplam_tutar'));
            
            echo json_encode([
                'success' => true,
                'masa' => $masa,
                'siparisler' => $siparisler,
                'detaylar' => $detaylar,
                'toplam_tutar' => $toplam_tutar
            ]);
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'iptal':
        @ob_clean();
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $siparis_id = intval($data['siparis_id']);
            $detay_id = isset($data['detay_id']) ? intval($data['detay_id']) : null;
            $iptal_nedeni = cleanInput($data['iptal_nedeni'] ?? '');
            
            try {
                $db->beginTransaction();
                
                if ($detay_id) {
                    // Kısmi iptal - sadece bir detayı iptal et
                    $stmt = $db->prepare("UPDATE siparis_detay SET durum = 'iptal' WHERE id = ?");
                    $stmt->execute([$detay_id]);
                    
                    // İptal kaydı oluştur
                    $stmt = $db->prepare("INSERT INTO siparis_iptal (siparis_id, siparis_detay_id, iptal_tipi, personel_id, iptal_nedeni) 
                                         VALUES (?, ?, 'kismi', ?, ?)");
                    $stmt->execute([$siparis_id, $detay_id, $_SESSION['personel_id'], $iptal_nedeni]);
                    
                    // Sipariş toplam tutarını güncelle
                    $stmt = $db->prepare("SELECT SUM(toplam_fiyat) as toplam FROM siparis_detay 
                                         WHERE siparis_id = ? AND durum != 'iptal'");
                    $stmt->execute([$siparis_id]);
                    $yeni_toplam = $stmt->fetch()['toplam'] ?? 0;
                    
                    $kdv_tutari = $yeni_toplam * 0.20;
                    $stmt = $db->prepare("UPDATE siparis SET toplam_tutar = ?, kdv_tutari = ? WHERE id = ?");
                    $stmt->execute([$yeni_toplam, $kdv_tutari, $siparis_id]);
                } else {
                    // Tam iptal - tüm siparişi iptal et
                    $stmt = $db->prepare("UPDATE siparis SET durum = 'iptal' WHERE id = ?");
                    $stmt->execute([$siparis_id]);
                    
                    $stmt = $db->prepare("UPDATE siparis_detay SET durum = 'iptal' WHERE siparis_id = ?");
                    $stmt->execute([$siparis_id]);
                    
                    // İptal kaydı oluştur
                    $stmt = $db->prepare("INSERT INTO siparis_iptal (siparis_id, iptal_tipi, personel_id, iptal_nedeni) 
                                         VALUES (?, 'tam', ?, ?)");
                    $stmt->execute([$siparis_id, $_SESSION['personel_id'], $iptal_nedeni]);
                    
                    // Masa durumunu güncelle
                    $stmt = $db->prepare("SELECT masa_id FROM siparis WHERE id = ?");
                    $stmt->execute([$siparis_id]);
                    $siparis = $stmt->fetch();
                    if ($siparis) {
                        $stmt = $db->prepare("UPDATE masa SET durum = 'bos' WHERE id = ?");
                        $stmt->execute([$siparis['masa_id']]);
                    }
                }
                
                // Aktivite log
                require_once '../includes/log_activity.php';
                logActivity($db, 'siparis_iptal', 'siparis', $siparis_id, "Sipariş iptal edildi: " . ($detay_id ? 'Kısmi' : 'Tam'));
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Sipariş iptal edildi']);
            } catch(Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'getir':
        @ob_clean();
        if ($method == 'GET' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $db->prepare("SELECT s.*, m.masa_no, mu.ad_soyad as musteri_adi, p.ad_soyad as personel_adi 
                                 FROM siparis s 
                                 JOIN masa m ON s.masa_id = m.id 
                                 LEFT JOIN musteri mu ON s.musteri_id = mu.id 
                                 JOIN personel p ON s.personel_id = p.id 
                                 WHERE s.id = ?");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch());
        } else {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
        }
        break;
        
    default:
        @ob_clean();
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}

// Output buffer'ı temizle ve kapat
@ob_end_clean();
?>
