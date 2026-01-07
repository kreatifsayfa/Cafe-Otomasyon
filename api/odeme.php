<?php
// Hata raporlamayı kapat - JSON API için
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Output buffering başlat
ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../config/config.php';
} catch(Exception $e) {
    @ob_clean();
    echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()]);
    exit();
}

checkLoginAPI();
checkRoleAPI(['admin', 'kasiyer']); // Sadece kasiyer ve admin ödeme alabilir

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($action) {
    case 'hesap_kes':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $siparis_id = intval($data['siparis_id']);
            $indirim_tutari = floatval($data['indirim_tutari'] ?? 0);
            $odeme_tipi = cleanInput($data['odeme_tipi'] ?? 'nakit');
            $nakit_tutar = isset($data['nakit_tutar']) ? floatval($data['nakit_tutar']) : null;
            $kart_tutar = isset($data['kart_tutar']) ? floatval($data['kart_tutar']) : null;
            
            try {
                $db->beginTransaction();
                
                // Sipariş bilgilerini al
                $stmt = $db->prepare("SELECT * FROM siparis WHERE id = ?");
                $stmt->execute([$siparis_id]);
                $siparis = $stmt->fetch();
                
                if (!$siparis) {
                    throw new Exception('Sipariş bulunamadı');
                }
                
                // Sipariş hazır mı kontrol et (mutfak onayı gerekli)
                if ($siparis['durum'] !== 'hazir' && $siparis['durum'] !== 'teslim_edildi') {
                    throw new Exception('Bu sipariş henüz hazır değil! Mutfak görünümünden siparişin hazır olduğunu onaylamanız gerekiyor.');
                }
                
                $toplam_tutar = $siparis['toplam_tutar'] - $indirim_tutari;
                $kdv_tutari = $toplam_tutar * 0.20;
                $genel_toplam = $toplam_tutar + $kdv_tutari;
                
                // Karma ödeme kontrolü
                if ($odeme_tipi === 'karma') {
                    if ($nakit_tutar === null || $kart_tutar === null) {
                        throw new Exception('Karma ödeme için nakit ve kart tutarları gereklidir');
                    }
                    
                    // Toplam kontrolü
                    $toplam_odeme = $nakit_tutar + $kart_tutar;
                    if (abs($toplam_odeme - $genel_toplam) > 0.01) {
                        throw new Exception('Nakit ve kart tutarlarının toplamı, genel toplam ile eşleşmelidir');
                    }
                    
                    // Nakit ödeme kaydı
                    $nakit_kdv = ($nakit_tutar / $genel_toplam) * $kdv_tutari;
                    $nakit_indirim = ($nakit_tutar / $genel_toplam) * $indirim_tutari;
                    $stmt = $db->prepare("INSERT INTO odeme (siparis_id, personel_id, odeme_tipi, tutar, kdv_tutari, indirim_tutari) 
                                         VALUES (?, ?, 'nakit', ?, ?, ?)");
                    $stmt->execute([$siparis_id, $_SESSION['personel_id'], $nakit_tutar, $nakit_kdv, $nakit_indirim]);
                    
                    // Kart ödeme kaydı
                    $kart_kdv = ($kart_tutar / $genel_toplam) * $kdv_tutari;
                    $kart_indirim = ($kart_tutar / $genel_toplam) * $indirim_tutari;
                    $stmt = $db->prepare("INSERT INTO odeme (siparis_id, personel_id, odeme_tipi, tutar, kdv_tutari, indirim_tutari) 
                                         VALUES (?, ?, 'kredi_karti', ?, ?, ?)");
                    $stmt->execute([$siparis_id, $_SESSION['personel_id'], $kart_tutar, $kart_kdv, $kart_indirim]);
                } else {
                    // Tek ödeme tipi için ödeme kaydı oluştur
                    $stmt = $db->prepare("INSERT INTO odeme (siparis_id, personel_id, odeme_tipi, tutar, kdv_tutari, indirim_tutari) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$siparis_id, $_SESSION['personel_id'], $odeme_tipi, $toplam_tutar, $kdv_tutari, $indirim_tutari]);
                }
                
                // Sipariş durumunu güncelle
                $stmt = $db->prepare("UPDATE siparis SET odeme_durumu = 'odendi', odeme_tipi = ?, 
                                     indirim_tutari = ?, toplam_tutar = ?, kdv_tutari = ? WHERE id = ?");
                $stmt->execute([$odeme_tipi, $indirim_tutari, $toplam_tutar, $kdv_tutari, $siparis_id]);
                
                // Masa durumunu güncelle
                $stmt = $db->prepare("UPDATE masa SET durum = 'bos' WHERE id = ?");
                $stmt->execute([$siparis['masa_id']]);
                
                // Kasa özetini güncelle
                $tarih = date('Y-m-d');
                $stmt = $db->prepare("SELECT id FROM kasa_ozet WHERE tarih = ?");
                $stmt->execute([$tarih]);
                $kasa_ozet = $stmt->fetch();
                
                if ($odeme_tipi === 'karma') {
                    // Karma ödeme için hem nakit hem kart girişi yap
                    if ($kasa_ozet) {
                        $stmt = $db->prepare("UPDATE kasa_ozet SET 
                                             nakit_giris = nakit_giris + ?, 
                                             kredi_karti_giris = kredi_karti_giris + ?,
                                             toplam_giris = toplam_giris + ? 
                                             WHERE id = ?");
                        $stmt->execute([$nakit_tutar, $kart_tutar, $genel_toplam, $kasa_ozet['id']]);
                    } else {
                        $stmt = $db->prepare("INSERT INTO kasa_ozet (tarih, nakit_giris, kredi_karti_giris, toplam_giris) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$tarih, $nakit_tutar, $kart_tutar, $genel_toplam]);
                    }
                } else {
                    // Tek ödeme tipi
                    if ($kasa_ozet) {
                        if ($odeme_tipi == 'nakit') {
                            $stmt = $db->prepare("UPDATE kasa_ozet SET nakit_giris = nakit_giris + ?, 
                                                 toplam_giris = toplam_giris + ? WHERE id = ?");
                        } else {
                            $stmt = $db->prepare("UPDATE kasa_ozet SET kredi_karti_giris = kredi_karti_giris + ?, 
                                                 toplam_giris = toplam_giris + ? WHERE id = ?");
                        }
                        $stmt->execute([$genel_toplam, $genel_toplam, $kasa_ozet['id']]);
                    } else {
                        if ($odeme_tipi == 'nakit') {
                            $stmt = $db->prepare("INSERT INTO kasa_ozet (tarih, nakit_giris, toplam_giris) VALUES (?, ?, ?)");
                        } else {
                            $stmt = $db->prepare("INSERT INTO kasa_ozet (tarih, kredi_karti_giris, toplam_giris) VALUES (?, ?, ?)");
                        }
                        $stmt->execute([$tarih, $genel_toplam, $genel_toplam]);
                    }
                }
                
                // Aktivite log
                require_once '../includes/log_activity.php';
                logActivity($db, 'odeme_al', 'odeme', $db->lastInsertId(), "Sipariş #{$siparis_id} ödendi: " . formatMoney($genel_toplam));
                
                // Otomatik fiş yazdır (ayarlara göre)
                $stmt = $db->query("SELECT ayar_degeri FROM ayarlar WHERE ayar_adi = 'otomatik_yazdir'");
                $ayar = $stmt->fetch();
                if ($ayar && $ayar['ayar_degeri'] == '1') {
                    require_once __DIR__ . '/yazici.php';
                    // Sipariş detaylarını al
                    $stmt = $db->prepare("SELECT sd.*, u.urun_adi FROM siparis_detay sd 
                                        JOIN menu_urun u ON sd.urun_id = u.id 
                                        WHERE sd.siparis_id = ?");
                    $stmt->execute([$siparis_id]);
                    $detaylar = $stmt->fetchAll();
                    
                    // Masa bilgisini al
                    $stmt = $db->prepare("SELECT masa_no FROM masa WHERE id = ?");
                    $stmt->execute([$siparis['masa_id']]);
                    $masa = $stmt->fetch();
                    $siparis['masa_no'] = $masa['masa_no'] ?? '';
                    
                    // Fiş formatı oluştur ve yazdır
                    $icerik = fisFormatiOlustur($siparis, $detaylar);
                    yaziciyaYazdir($db, 'kasa', $icerik, 'fis');
                }
                
                // Müşteri varsa puan ekle
                if ($siparis['musteri_id']) {
                    $puan = intval($genel_toplam / 10); // Her 10 TL için 1 puan
                    if ($puan > 0) {
                        $stmt = $db->prepare("INSERT INTO musteri_puan_gecmis (musteri_id, puan, aciklama, siparis_id) 
                                             VALUES (?, ?, ?, ?)");
                        $stmt->execute([$siparis['musteri_id'], $puan, "Sipariş #{$siparis['siparis_no']} tamamlandı", $siparis_id]);
                        
                        $stmt = $db->prepare("UPDATE musteri SET puan = puan + ?, toplam_harcama = toplam_harcama + ? WHERE id = ?");
                        $stmt->execute([$puan, $genel_toplam, $siparis['musteri_id']]);
                    }
                }
                
                $db->commit();
                echo json_encode(['success' => true, 'toplam' => $genel_toplam, 'message' => 'Ödeme alındı']);
            } catch(Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'fis':
        $siparis_id = intval($_GET['siparis_id']);
        $stmt = $db->prepare("SELECT s.*, m.masa_no, mu.ad_soyad as musteri_adi, p.ad_soyad as personel_adi 
                             FROM siparis s 
                             JOIN masa m ON s.masa_id = m.id 
                             LEFT JOIN musteri mu ON s.musteri_id = mu.id 
                             JOIN personel p ON s.personel_id = p.id 
                             WHERE s.id = ?");
        $stmt->execute([$siparis_id]);
        $siparis = $stmt->fetch();
        
        $stmt = $db->prepare("SELECT sd.*, u.urun_adi FROM siparis_detay sd 
                             JOIN menu_urun u ON sd.urun_id = u.id 
                             WHERE sd.siparis_id = ?");
        $stmt->execute([$siparis_id]);
        $detaylar = $stmt->fetchAll();
        
        echo json_encode(['siparis' => $siparis, 'detaylar' => $detaylar]);
        break;
        
    case 'masa_hesap_kes':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $masa_id = intval($data['masa_id']);
            $siparis_ids = $data['siparis_ids'] ?? [];
            $indirim_tutari = floatval($data['indirim_tutari'] ?? 0);
            $odeme_tipi = cleanInput($data['odeme_tipi'] ?? 'nakit');
            $nakit_tutar = isset($data['nakit_tutar']) ? floatval($data['nakit_tutar']) : null;
            $kart_tutar = isset($data['kart_tutar']) ? floatval($data['kart_tutar']) : null;
            
            if (empty($siparis_ids) || !is_array($siparis_ids)) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz sipariş listesi']);
                break;
            }
            
            try {
                $db->beginTransaction();
                
                // Tüm siparişlerin hazır olup olmadığını kontrol et
                $placeholders = str_repeat('?,', count($siparis_ids) - 1) . '?';
                $stmt = $db->prepare("SELECT id, siparis_no, durum, toplam_tutar FROM siparis WHERE id IN ($placeholders)");
                $stmt->execute($siparis_ids);
                $siparisler = $stmt->fetchAll();
                
                if (count($siparisler) !== count($siparis_ids)) {
                    throw new Exception('Bazı siparişler bulunamadı');
                }
                
                foreach ($siparisler as $siparis) {
                    if ($siparis['durum'] !== 'hazir' && $siparis['durum'] !== 'teslim_edildi') {
                        throw new Exception("Sipariş {$siparis['siparis_no']} henüz hazır değil!");
                    }
                }
                
                // Toplam tutarı hesapla
                $toplam_tutar = array_sum(array_column($siparisler, 'toplam_tutar')) - $indirim_tutari;
                $kdv_tutari = $toplam_tutar * 0.20;
                $genel_toplam = $toplam_tutar + $kdv_tutari;
                
                // Karma ödeme kontrolü
                if ($odeme_tipi === 'karma') {
                    if ($nakit_tutar === null || $kart_tutar === null) {
                        throw new Exception('Karma ödeme için nakit ve kart tutarları gereklidir');
                    }
                    
                    $toplam_odeme = $nakit_tutar + $kart_tutar;
                    if (abs($toplam_odeme - $genel_toplam) > 0.01) {
                        throw new Exception('Nakit ve kart tutarlarının toplamı, genel toplam ile eşleşmelidir');
                    }
                    
                    // Her sipariş için nakit ve kart ödemelerini oransal dağıt
                    foreach ($siparisler as $siparis) {
                        $siparis_orani = $siparis['toplam_tutar'] / array_sum(array_column($siparisler, 'toplam_tutar'));
                        $siparis_nakit = $nakit_tutar * $siparis_orani;
                        $siparis_kart = $kart_tutar * $siparis_orani;
                        $siparis_toplam = $siparis_nakit + $siparis_kart;
                        $siparis_kdv = $siparis_toplam * 0.20;
                        $siparis_indirim = $indirim_tutari * $siparis_orani;
                        
                        // Nakit ödeme kaydı
                        $stmt = $db->prepare("INSERT INTO odeme (siparis_id, personel_id, odeme_tipi, tutar, kdv_tutari, indirim_tutari) 
                                             VALUES (?, ?, 'nakit', ?, ?, ?)");
                        $stmt->execute([$siparis['id'], $_SESSION['personel_id'], $siparis_nakit, $siparis_kdv * ($siparis_nakit / $siparis_toplam), $siparis_indirim * ($siparis_nakit / $siparis_toplam)]);
                        
                        // Kart ödeme kaydı
                        $stmt = $db->prepare("INSERT INTO odeme (siparis_id, personel_id, odeme_tipi, tutar, kdv_tutari, indirim_tutari) 
                                             VALUES (?, ?, 'kredi_karti', ?, ?, ?)");
                        $stmt->execute([$siparis['id'], $_SESSION['personel_id'], $siparis_kart, $siparis_kdv * ($siparis_kart / $siparis_toplam), $siparis_indirim * ($siparis_kart / $siparis_toplam)]);
                        
                        // Sipariş durumunu güncelle
                        $stmt = $db->prepare("UPDATE siparis SET odeme_durumu = 'odendi', odeme_tipi = 'karma', 
                                             indirim_tutari = ?, toplam_tutar = ?, kdv_tutari = ? WHERE id = ?");
                        $stmt->execute([$siparis_indirim, $siparis['toplam_tutar'] - $siparis_indirim, $siparis_kdv, $siparis['id']]);
                    }
                } else {
                    // Tek ödeme tipi - her sipariş için oransal dağıt
                    foreach ($siparisler as $siparis) {
                        $siparis_orani = $siparis['toplam_tutar'] / array_sum(array_column($siparisler, 'toplam_tutar'));
                        $siparis_indirim = $indirim_tutari * $siparis_orani;
                        $siparis_toplam = $siparis['toplam_tutar'] - $siparis_indirim;
                        $siparis_kdv = $siparis_toplam * 0.20;
                        $siparis_genel = $siparis_toplam + $siparis_kdv;
                        
                        // Ödeme kaydı oluştur
                        $stmt = $db->prepare("INSERT INTO odeme (siparis_id, personel_id, odeme_tipi, tutar, kdv_tutari, indirim_tutari) 
                                             VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$siparis['id'], $_SESSION['personel_id'], $odeme_tipi, $siparis_toplam, $siparis_kdv, $siparis_indirim]);
                        
                        // Sipariş durumunu güncelle
                        $stmt = $db->prepare("UPDATE siparis SET odeme_durumu = 'odendi', odeme_tipi = ?, 
                                             indirim_tutari = ?, toplam_tutar = ?, kdv_tutari = ? WHERE id = ?");
                        $stmt->execute([$odeme_tipi, $siparis_indirim, $siparis_toplam, $siparis_kdv, $siparis['id']]);
                    }
                }
                
                // Masa durumunu güncelle
                $stmt = $db->prepare("UPDATE masa SET durum = 'bos' WHERE id = ?");
                $stmt->execute([$masa_id]);
                
                // Kasa özetini güncelle
                $tarih = date('Y-m-d');
                $stmt = $db->prepare("SELECT id FROM kasa_ozet WHERE tarih = ?");
                $stmt->execute([$tarih]);
                $kasa_ozet = $stmt->fetch();
                
                if ($odeme_tipi === 'karma') {
                    if ($kasa_ozet) {
                        $stmt = $db->prepare("UPDATE kasa_ozet SET 
                                             nakit_giris = nakit_giris + ?, 
                                             kredi_karti_giris = kredi_karti_giris + ?,
                                             toplam_giris = toplam_giris + ? 
                                             WHERE id = ?");
                        $stmt->execute([$nakit_tutar, $kart_tutar, $genel_toplam, $kasa_ozet['id']]);
                    } else {
                        $stmt = $db->prepare("INSERT INTO kasa_ozet (tarih, nakit_giris, kredi_karti_giris, toplam_giris) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$tarih, $nakit_tutar, $kart_tutar, $genel_toplam]);
                    }
                } else {
                    if ($kasa_ozet) {
                        if ($odeme_tipi == 'nakit') {
                            $stmt = $db->prepare("UPDATE kasa_ozet SET nakit_giris = nakit_giris + ?, 
                                                 toplam_giris = toplam_giris + ? WHERE id = ?");
                        } else {
                            $stmt = $db->prepare("UPDATE kasa_ozet SET kredi_karti_giris = kredi_karti_giris + ?, 
                                                 toplam_giris = toplam_giris + ? WHERE id = ?");
                        }
                        $stmt->execute([$genel_toplam, $genel_toplam, $kasa_ozet['id']]);
                    } else {
                        if ($odeme_tipi == 'nakit') {
                            $stmt = $db->prepare("INSERT INTO kasa_ozet (tarih, nakit_giris, toplam_giris) VALUES (?, ?, ?)");
                        } else {
                            $stmt = $db->prepare("INSERT INTO kasa_ozet (tarih, kredi_karti_giris, toplam_giris) VALUES (?, ?, ?)");
                        }
                        $stmt->execute([$tarih, $genel_toplam, $genel_toplam]);
                    }
                }
                
                // Aktivite log
                require_once '../includes/log_activity.php';
                $siparis_nolar = implode(', ', array_column($siparisler, 'siparis_no'));
                logActivity($db, 'odeme_al', 'odeme', 0, "Masa $masa_id için ${count($siparis_ids)} sipariş ödendi: $siparis_nolar - " . formatMoney($genel_toplam));
                
                // Otomatik fiş yazdır (ayarlara göre)
                $stmt = $db->query("SELECT ayar_degeri FROM ayarlar WHERE ayar_adi = 'otomatik_yazdir'");
                $ayar = $stmt->fetch();
                if ($ayar && $ayar['ayar_degeri'] == '1') {
                    require_once __DIR__ . '/yazici.php';
                    foreach ($siparisler as $siparis) {
                        $stmt = $db->prepare("SELECT sd.*, u.urun_adi FROM siparis_detay sd 
                                            JOIN menu_urun u ON sd.urun_id = u.id 
                                            WHERE sd.siparis_id = ?");
                        $stmt->execute([$siparis['id']]);
                        $detaylar = $stmt->fetchAll();
                        
                        $masa_stmt = $db->prepare("SELECT masa_no FROM masa WHERE id = ?");
                        $masa_stmt->execute([$masa_id]);
                        $masa = $masa_stmt->fetch();
                        $siparis['masa_no'] = $masa['masa_no'] ?? '';
                        
                        $icerik = fisFormatiOlustur($siparis, $detaylar);
                        yaziciyaYazdir($db, 'kasa', $icerik, 'fis');
                    }
                }
                
                // Müşteri varsa puan ekle
                $musteri_ids = array_filter(array_column($siparisler, 'musteri_id'));
                if (!empty($musteri_ids)) {
                    $musteri_id = $musteri_ids[0]; // İlk siparişin müşterisini kullan
                    $puan = intval($genel_toplam / 10);
                    if ($puan > 0) {
                        $stmt = $db->prepare("INSERT INTO musteri_puan_gecmis (musteri_id, puan, aciklama, siparis_id) 
                                             VALUES (?, ?, ?, ?)");
                        $stmt->execute([$musteri_id, $puan, "Masa $masa_id için birleşik ödeme", $siparis_ids[0]]);
                        
                        $stmt = $db->prepare("UPDATE musteri SET puan = puan + ?, toplam_harcama = toplam_harcama + ? WHERE id = ?");
                        $stmt->execute([$puan, $genel_toplam, $musteri_id]);
                    }
                }
                
                $db->commit();
                echo json_encode(['success' => true, 'toplam' => $genel_toplam, 'message' => count($siparis_ids) . ' sipariş ödendi']);
            } catch(Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
}
?>

