#!/usr/bin/env python3
"""
6 Şubat Depremleri Anma Videosu
Bölükyayla Belediye Başkanı Mehmet Turan adına hazırlanmıştır.

Kullanım:
    pip install pillow numpy imageio-ffmpeg
    python3 video_olustur.py              # yatay (1920x1080) + dikey (1080x1920)
    python3 video_olustur.py --yatay      # sadece yatay
    python3 video_olustur.py --dikey      # sadece dikey (Reels / Shorts / Story)

Metinleri ve yıl dönümü sayısını aşağıdaki AYARLAR bölümünden değiştirebilirsiniz.
"""
import math
import os
import subprocess
import sys
import wave

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont
import imageio_ffmpeg

# --------------------------------------------------------------------------
# AYARLAR
# --------------------------------------------------------------------------
BASKAN_ADI = "Mehmet Turan"
UNVAN = "Bölükyayla Belediye Başkanı"
YIL_DONUMU = "4. Yıl Dönümü"  # 6 Şubat 2027 için. 2026 için "3. Yıl Dönümü" yazın.

FPS = 30
KLASOR = os.path.dirname(os.path.abspath(__file__))

FONT_SERIF = "/usr/share/fonts/truetype/liberation/LiberationSerif-Regular.ttf"
FONT_SERIF_BOLD = "/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf"
FONT_SERIF_ITALIC = "/usr/share/fonts/truetype/liberation/LiberationSerif-Italic.ttf"
FONT_SANS = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"

# Sahneler: (süre sn, tür, içerik)
SAHNELER = [
    (6.0, "baslik", {"ust": "6 ŞUBAT 2023", "ana": "04:17", "alt": "Kahramanmaraş merkezli depremler"}),
    (7.0, "metin", {"satirlar": ["Pazarcık ve Elbistan merkezli", "iki büyük depremle", "11 ilimizde derin bir acı yaşadık."]}),
    (7.0, "metin", {"satirlar": ["Yitirdiğimiz her can,", "yüreğimizde silinmez", "bir iz bıraktı."]}),
    (9.0, "mum", {"satirlar": ["Hayatını kaybeden tüm canlarımızı", "rahmetle, saygıyla anıyoruz."]}),
    (8.0, "metin", {"satirlar": ["Yaraları sarmak için", "omuz omuza veren herkese", "şükranlarımızı sunuyoruz."]}),
    (7.0, "vurgu", {"satirlar": ["Unutmadık.", "Unutmayacağız."]}),
    (10.0, "imza", {}),
]

# Renkler
ALTIN = (222, 190, 132)
BEYAZ = (240, 236, 228)
GRI = (160, 160, 168)


def ease(t):
    t = max(0.0, min(1.0, t))
    return t * t * (3 - 2 * t)


def gorunurluk(t, sure, giris=1.2, cikis=1.0):
    """Sahne içindeki opaklık (0..1)."""
    return ease(t / giris) * ease((sure - t) / cikis)


class Olusturucu:
    def __init__(self, g, y):
        self.G, self.Y = g, y
        self.olcek = min(g, y) / 1080.0
        s = self.olcek
        self.f_ust = ImageFont.truetype(FONT_SANS, int(34 * s))
        self.f_saat = ImageFont.truetype(FONT_SERIF_BOLD, int(150 * s))
        self.f_alt = ImageFont.truetype(FONT_SERIF_ITALIC, int(40 * s))
        self.f_metin = ImageFont.truetype(FONT_SERIF, int(62 * s))
        self.f_vurgu = ImageFont.truetype(FONT_SERIF_BOLD, int(96 * s))
        self.f_isim = ImageFont.truetype(FONT_SERIF_BOLD, int(88 * s))
        self.f_unvan = ImageFont.truetype(FONT_SANS, int(36 * s))
        self.f_kucuk = ImageFont.truetype(FONT_SANS, int(28 * s))

        # Arka plan: koyu lacivertten siyaha dikey degrade + vinyet
        yy, xx = np.mgrid[0:y, 0:g].astype(np.float32)
        dy = yy / y
        base = np.zeros((y, g, 3), np.float32)
        ust = np.array([18, 22, 34], np.float32)
        alt = np.array([4, 4, 8], np.float32)
        base[:] = ust * (1 - dy[..., None]) + alt * dy[..., None]
        cx, cy = g / 2, y / 2
        r = np.sqrt(((xx - cx) / (g / 2)) ** 2 + ((yy - cy) / (y / 2)) ** 2)
        vinyet = np.clip(1.15 - 0.55 * r ** 1.6, 0.25, 1.0)
        self.arka = base * vinyet[..., None]

        # Mum ışığı için radyal parıltı (önceden hesaplanır)
        self.mum_x, self.mum_y = g / 2, y * (0.60 if g > y else 0.58)
        d = np.sqrt((xx - self.mum_x) ** 2 + (yy - self.mum_y) ** 2) / (420 * s)
        self.parilti = np.exp(-d ** 2 * 1.4).astype(np.float32)

        # Yükselen toz / kor parçacıkları
        rng = np.random.default_rng(6022023)
        n = int(90 * (g * y) / (1920 * 1080))
        self.par = {
            "x": rng.uniform(0, g, n),
            "y": rng.uniform(0, y, n),
            "hiz": rng.uniform(8, 28, n) * s,
            "boy": rng.uniform(1.0, 3.2, n) * s,
            "faz": rng.uniform(0, 2 * math.pi, n),
            "parlak": rng.uniform(0.25, 0.9, n),
        }

    # ------------------------------------------------------------------
    def arkaplan(self, zaman, mum_gucu=0.0):
        img = self.arka.copy()
        if mum_gucu > 0:
            titreme = 0.85 + 0.1 * math.sin(zaman * 7.3) + 0.05 * math.sin(zaman * 13.1)
            renk = np.array([255, 150, 60], np.float32) * 0.33 * mum_gucu * titreme
            img += self.parilti[..., None] * renk
        return Image.fromarray(np.clip(img, 0, 255).astype(np.uint8))

    def parcaciklar(self, katman, zaman, alfa=1.0):
        d = ImageDraw.Draw(katman)
        p = self.par
        ys = (p["y"] - p["hiz"] * zaman) % (self.Y + 20) - 10
        xs = p["x"] + np.sin(zaman * 0.6 + p["faz"]) * 18 * self.olcek
        for x, y, b, pa, f in zip(xs, ys, p["boy"], p["parlak"], p["faz"]):
            a = int(255 * alfa * pa * (0.6 + 0.4 * math.sin(zaman * 1.7 + f)))
            d.ellipse([x - b, y - b, x + b, y + b], fill=(255, 200, 140, max(a, 0)))

    def ortali_yazi(self, d, y, metin, font, renk, alfa, aralik=0):
        if aralik:
            metin = (" " * aralik).join(metin)
        w = d.textlength(metin, font=font)
        d.text(((self.G - w) / 2, y), metin, font=font, fill=renk + (int(255 * alfa),))

    def satir_blogu(self, d, satirlar, font, renk, alfa, merkez_y, t=0.0, kademeli=0.35):
        asc, desc = font.getmetrics()
        h = (asc + desc) * 1.25
        bas = merkez_y - h * len(satirlar) / 2
        for i, s in enumerate(satirlar):
            a = alfa * ease((t - i * kademeli) / 1.0) if kademeli else alfa
            kay = (1 - ease((t - i * kademeli) / 1.2)) * 14 * self.olcek
            self.ortali_yazi(d, bas + i * h + kay, s, font, renk, a)

    def mum(self, d, zaman, alfa):
        s = self.olcek
        cx, cy = self.mum_x, self.mum_y
        gw, gh = 46 * s, 190 * s
        a = int(255 * alfa)
        # gövde
        d.rounded_rectangle([cx - gw / 2, cy, cx + gw / 2, cy + gh], radius=6 * s,
                            fill=(236, 226, 206, a))
        d.rectangle([cx - gw / 2, cy + gh * 0.08, cx - gw / 2 + 8 * s, cy + gh],
                    fill=(210, 196, 170, a))
        # fitil
        d.line([cx, cy, cx, cy - 12 * s], fill=(40, 30, 25, a), width=max(2, int(3 * s)))
        # alev
        sal = math.sin(zaman * 5.1) * 3 * s + math.sin(zaman * 11.7) * 1.5 * s
        boy = (58 + 5 * math.sin(zaman * 8.3)) * s
        en = 16 * s
        tepe = (cx + sal, cy - 12 * s - boy)
        for olc, renk in ((1.0, (255, 170, 60)), (0.7, (255, 215, 120)), (0.4, (255, 250, 220))):
            pts = []
            for k in range(24):
                u = k / 23 * math.pi
                px = cx + math.sin(u) * en * olc * (1 - 0.15 * olc) + sal * (1 - math.cos(u)) / 2
                py = cy - 10 * s - (1 - math.cos(u)) / 2 * boy * olc
                pts.append((px, py))
            pts += [(tepe[0] + (cx - tepe[0]) * (1 - olc), cy - 12 * s - boy * olc)]
            for k in range(23, -1, -1):
                u = k / 23 * math.pi
                px = cx - math.sin(u) * en * olc * (1 - 0.15 * olc) + sal * (1 - math.cos(u)) / 2
                py = cy - 10 * s - (1 - math.cos(u)) / 2 * boy * olc
                pts.append((px, py))
            d.polygon(pts, fill=renk + (int(a * (0.9 if olc < 1 else 0.8)),))

    def cizgi(self, d, y, genislik, alfa):
        w = genislik * self.olcek
        d.line([(self.G - w) / 2, y, (self.G + w) / 2, y], fill=ALTIN + (int(200 * alfa),),
               width=max(1, int(2 * self.olcek)))

    # ------------------------------------------------------------------
    def kare(self, zaman, sahne_t, sure, tur, veri):
        G, Y, s = self.G, self.Y, self.olcek
        alfa = gorunurluk(sahne_t, sure)
        mum_gucu = alfa if tur == "mum" else 0.0
        img = self.arkaplan(zaman, mum_gucu).convert("RGBA")

        par = Image.new("RGBA", (G, Y), (0, 0, 0, 0))
        self.parcaciklar(par, zaman, 0.8)
        par = par.filter(ImageFilter.GaussianBlur(1.2 * s))
        img.alpha_composite(par)

        yazi = Image.new("RGBA", (G, Y), (0, 0, 0, 0))
        d = ImageDraw.Draw(yazi)
        orta = Y / 2

        if tur == "baslik":
            a1 = ease((sahne_t - 0.3) / 1.2) * ease((sure - sahne_t) / 1.0)
            a2 = ease((sahne_t - 1.2) / 1.5) * ease((sure - sahne_t) / 1.0)
            a3 = ease((sahne_t - 2.4) / 1.2) * ease((sure - sahne_t) / 1.0)
            self.ortali_yazi(d, orta - 190 * s, veri["ust"], self.f_ust, ALTIN, a1, aralik=1)
            asc, _ = self.f_saat.getmetrics()
            self.ortali_yazi(d, orta - asc / 2 - 20 * s, veri["ana"], self.f_saat, BEYAZ, a2)
            self.cizgi(d, orta + 110 * s, 160 * ease((sahne_t - 1.8) / 1.5), a2)
            self.ortali_yazi(d, orta + 140 * s, veri["alt"], self.f_alt, GRI, a3)

        elif tur == "metin":
            self.satir_blogu(d, veri["satirlar"], self.f_metin, BEYAZ, alfa, orta, sahne_t)

        elif tur == "mum":
            self.mum(d, zaman, ease(sahne_t / 1.5) * ease((sure - sahne_t) / 1.0))
            ust = Y * (0.28 if G > Y else 0.30)
            self.satir_blogu(d, veri["satirlar"], self.f_metin, BEYAZ, alfa, ust, sahne_t - 1.0)

        elif tur == "vurgu":
            self.satir_blogu(d, veri["satirlar"], self.f_vurgu, BEYAZ, alfa, orta, sahne_t, kademeli=1.4)
            self.cizgi(d, orta + 150 * s, 120 * ease((sahne_t - 2.5) / 1.5), alfa)

        elif tur == "imza":
            a0 = ease(sahne_t / 1.2) * ease((sure - sahne_t) / 1.5)
            a1 = ease((sahne_t - 0.8) / 1.4) * ease((sure - sahne_t) / 1.5)
            a2 = ease((sahne_t - 1.8) / 1.4) * ease((sure - sahne_t) / 1.5)
            self.ortali_yazi(d, orta - 250 * s, "6 ŞUBAT DEPREMLERİNİN", self.f_kucuk, GRI, a0, aralik=1)
            self.ortali_yazi(d, orta - 205 * s, YIL_DONUMU.upper(), self.f_kucuk, GRI, a0, aralik=1)
            self.cizgi(d, orta - 130 * s, 80, a0)
            asc, _ = self.f_isim.getmetrics()
            self.ortali_yazi(d, orta - asc / 2 - 10 * s, BASKAN_ADI, self.f_isim, BEYAZ, a1)
            self.ortali_yazi(d, orta + 80 * s, UNVAN, self.f_unvan, ALTIN, a2, aralik=0)
            self.ortali_yazi(d, Y - 140 * s, "Rahmetle anıyoruz.", self.f_alt, GRI, a2)

        img.alpha_composite(yazi)
        # genel giriş / çıkış karartması
        return img.convert("RGB")


def muzik_olustur(yol, sure, sr=44100):
    """Sade, hüzünlü bir ambient ses dokusu (La minör pad) üretir."""
    t = np.arange(int(sure * sr)) / sr
    ses = np.zeros_like(t)
    # Am - F - C - G - Am - F - Dm - E  (her akor ~7.5 sn)
    notlar = {"A2": 110.0, "C3": 130.81, "D3": 146.83, "E3": 164.81, "F3": 174.61, "G3": 196.0,
              "A3": 220.0, "B3": 246.94, "C4": 261.63, "D4": 293.66, "E4": 329.63, "G#3": 207.65}
    akorlar = [["A2", "E3", "A3", "C4"], ["F3", "A3", "C4"], ["C3", "G3", "C4", "E4"],
               ["G3", "B3", "D4"], ["A2", "E3", "A3", "C4"], ["F3", "A3", "C4"],
               ["D3", "A3", "D4"], ["E3", "G#3", "B3", "E4"]]
    uzun = sure / len(akorlar)
    for i, ak in enumerate(akorlar):
        bas = i * uzun
        zarf = np.clip((t - bas) / 2.5, 0, 1) * np.clip((bas + uzun + 2.5 - t) / 2.5, 0, 1)
        zarf = zarf ** 1.5
        for n in ak:
            f = notlar[n]
            for h, g in ((1, 1.0), (2, 0.25), (3, 0.08)):
                for det in (-0.6, 0.6):
                    ses += zarf * g * np.sin(2 * np.pi * (f * h + det) * t + i) * 0.5
    # hafif titreşim (tremolo) ve yumuşak giriş/çıkış
    ses *= 0.85 + 0.15 * np.sin(2 * np.pi * 0.2 * t)
    ses *= np.clip(t / 3, 0, 1) * np.clip((sure - t) / 4, 0, 1)
    # basit yankı
    for gecikme, g in ((0.23, 0.35), (0.41, 0.22), (0.67, 0.12)):
        k = int(gecikme * sr)
        ses[k:] += ses[:-k] * g
    ses = ses / np.max(np.abs(ses)) * 0.5
    stereo = np.stack([ses, np.roll(ses, int(0.012 * sr))], axis=1)
    with wave.open(yol, "wb") as w:
        w.setnchannels(2)
        w.setsampwidth(2)
        w.setframerate(sr)
        w.writeframes((stereo * 32767).astype(np.int16).tobytes())


def video_olustur(g, y, cikti):
    toplam = sum(s[0] for s in SAHNELER)
    ses_yolu = os.path.join(KLASOR, ".muzik.wav")
    muzik_olustur(ses_yolu, toplam)

    ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
    komut = [ffmpeg, "-y", "-loglevel", "error",
             "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{g}x{y}", "-r", str(FPS), "-i", "-",
             "-i", ses_yolu,
             "-c:v", "libx264", "-preset", "medium", "-crf", "20", "-pix_fmt", "yuv420p",
             "-c:a", "aac", "-b:a", "160k", "-shortest", "-movflags", "+faststart", cikti]
    p = subprocess.Popen(komut, stdin=subprocess.PIPE)
    o = Olusturucu(g, y)
    zaman = 0.0
    kare_no = 0
    for sure, tur, veri in SAHNELER:
        for k in range(int(sure * FPS)):
            sahne_t = k / FPS
            p.stdin.write(o.kare(zaman, sahne_t, sure, tur, veri).tobytes())
            zaman += 1 / FPS
            kare_no += 1
        print(f"  {os.path.basename(cikti)}: {zaman:5.1f} / {toplam:.0f} sn", flush=True)
    p.stdin.close()
    p.wait()
    os.remove(ses_yolu)
    print(f"Hazır: {cikti}")


if __name__ == "__main__":
    arg = sys.argv[1:]
    if "--dikey" not in arg:
        video_olustur(1920, 1080, os.path.join(KLASOR, "6_subat_anma_yatay.mp4"))
    if "--yatay" not in arg:
        video_olustur(1080, 1920, os.path.join(KLASOR, "6_subat_anma_dikey.mp4"))
