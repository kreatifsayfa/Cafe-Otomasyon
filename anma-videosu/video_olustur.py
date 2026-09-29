#!/usr/bin/env python3
"""
6 Şubat Depremleri Anma Videosu — Bölükyayla Belediyesi / Başkan Mehmet Turan

Kurulum:
    pip install pillow numpy imageio-ffmpeg edge-tts
Kullanım:
    python3 video_olustur.py            # yatay (1920x1080) + dikey (1080x1920)
    python3 video_olustur.py --yatay    # sadece yatay
    python3 video_olustur.py --dikey    # sadece dikey
    python3 video_olustur.py --onizleme # sahnelerden kareler (png)

Seslendirme edge-tts (tr-TR-AhmetNeural) ile üretilir. Gerçek bir seslendirme
kaydı kullanılacaksa, sahne başına dosyaları ses/ klasörüne aynı adlarla
(d1.wav, d2.wav ...) koymak yeterlidir; zamanlama kaydın süresine göre kurulur.
"""
import math
import os
import subprocess
import sys
import wave

import numpy as np
from PIL import Image, ImageDraw, ImageFont
import imageio_ffmpeg

# ---------------------------------------------------------------- AYARLAR
BASKAN_ADI = "Mehmet Turan"
UNVAN = "Bölükyayla Belediye Başkanı"
GECEN_YIL = "Dört"  # 6 Şubat 2027 için "Dört", 2026 için "Üç"

SES = "tr-TR-AhmetNeural"
SES_HIZ = "-12%"
SES_PERDE = "-4Hz"

ILLER = ["Adana", "Adıyaman", "Diyarbakır", "Elazığ", "Gaziantep", "Hatay",
         "Kahramanmaraş", "Kilis", "Malatya", "Osmaniye", "Şanlıurfa"]

# Seslendirme metni (anahtar -> okunacak metin)
METIN = {
    "d1": "Altı Şubat, iki bin yirmi üç.",
    "d2": "Saat dördü on yedi geçe, Pazarcık.",
    "d3": "Dokuz saat sonra, Elbistan.",
    "e0": "On bir ilimiz.",
    **{f"il{i}": ad for i, ad in enumerate(ILLER)},
    "f1": "Türkiye'de elli binden fazla insan hayatını kaybetti.",
    "g1": f"{GECEN_YIL} yıl geçti.",
    "g2": "Yıkılan yerler yeniden yapılıyor.",
    "g3": "Ama kaybettiklerimizin yeri, hep boş kalacak.",
    "h1": "Depremde hayatını kaybeden tüm canlarımızı, rahmetle anıyoruz.",
}

FPS = 30
SR = 48000
KLASOR = os.path.dirname(os.path.abspath(__file__))
SES_KLASOR = os.path.join(KLASOR, "ses")
FONT = lambda ad: os.path.join(KLASOR, "fonts", ad)

BEYAZ = (238, 236, 232)
GRI = (128, 128, 132)
KOYU = (70, 70, 74)
KIRMIZI = (200, 52, 44)


# ---------------------------------------------------------------- yardımcılar
def clamp(x, a=0.0, b=1.0):
    return max(a, min(b, x))


def expo_out(t):
    t = clamp(t)
    return 1.0 if t >= 1 else 1 - 2 ** (-10 * t)


def smooth(t):
    t = clamp(t)
    return t * t * (3 - 2 * t)


def ffmpeg():
    return imageio_ffmpeg.get_ffmpeg_exe()


def wav_oku(yol):
    ham = subprocess.run([ffmpeg(), "-loglevel", "error", "-i", yol, "-f", "s16le",
                          "-ac", "1", "-ar", str(SR), "-"], capture_output=True, check=True).stdout
    x = np.frombuffer(ham, np.int16).astype(np.float32) / 32768
    # baştaki / sondaki sessizliği kırp
    esik = 0.01
    idx = np.where(np.abs(x) > esik)[0]
    if len(idx):
        x = x[max(0, idx[0] - int(0.02 * SR)): idx[-1] + int(0.08 * SR)]
    return x


def seslendirme_hazirla():
    os.makedirs(SES_KLASOR, exist_ok=True)
    klipler = {}
    for anahtar, metin in METIN.items():
        hazir = [os.path.join(SES_KLASOR, anahtar + u) for u in (".wav", ".mp3")]
        yol = next((h for h in hazir if os.path.exists(h)), None)
        if yol is None:
            yol = hazir[1]
            subprocess.run(["edge-tts", "--voice", SES, f"--rate={SES_HIZ}", f"--pitch={SES_PERDE}",
                            "--text", metin, "--write-media", yol], check=True,
                           stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        klipler[anahtar] = wav_oku(yol)
    return klipler


# ---------------------------------------------------------------- zaman çizelgesi
def zaman_cizelgesi(klip):
    """Seslendirme sürelerine göre tüm olayların zamanlarını hesaplar."""
    s = lambda k: len(klip[k]) / SR
    T = {}
    T["saat_bas"] = 1.0          # 04:16:54
    T["sarsinti"] = 7.0          # 04:17:00
    T["kesme"] = 9.6             # sert kesme -> siyah
    t = 11.2
    T["D"] = t
    T["d1"] = t + 0.3
    T["d2"] = T["d1"] + s("d1") + 0.7
    T["d3"] = T["d2"] + s("d2") + 0.9
    T["D_son"] = T["d3"] + s("d3") + 1.6
    t = T["D_son"] + 0.5
    T["E"] = t
    T["e0"] = t + 0.3
    c = T["e0"] + s("e0") + 0.5
    for i in range(len(ILLER)):
        T[f"il{i}"] = c
        c += max(0.95, s(f"il{i}") + 0.3)
    T["E_son"] = c + 1.6
    t = T["E_son"] + 0.6
    T["F"] = t
    T["f1"] = t + 0.4
    T["F_son"] = T["f1"] + s("f1") + 2.2
    t = T["F_son"] + 0.6
    T["G"] = t
    T["g1"] = t + 0.4
    T["g2"] = T["g1"] + s("g1") + 0.9
    T["g3"] = T["g2"] + s("g2") + 0.7
    T["G_son"] = T["g3"] + s("g3") + 2.0
    t = T["G_son"] + 0.8
    T["H"] = t
    T["h1"] = t + 0.4
    T["imza"] = T["h1"] + s("h1") + 0.9
    T["son"] = T["imza"] + 5.5
    return T


# ---------------------------------------------------------------- ses tasarımı
def ses_olustur(klip, T, yol):
    n = int((T["son"] + 0.5) * SR)
    t = np.arange(n) / SR
    rng = np.random.default_rng(6)
    muzik = np.zeros(n, np.float32)
    efekt = np.zeros(n, np.float32)
    vo = np.zeros(n, np.float32)

    def ekle(hedef, x, bas, kazanc=1.0):
        i = int(bas * SR)
        j = min(n, i + len(x))
        hedef[i:j] += x[: j - i] * kazanc

    # saat tıkırtısı
    tik_t = np.arange(int(0.05 * SR)) / SR
    tik = (np.sin(2 * np.pi * 2900 * tik_t) * np.exp(-tik_t * 180)
           + np.diff(rng.standard_normal(len(tik_t) + 1)) * np.exp(-tik_t * 400) * 0.3)
    for k in range(9):
        ekle(efekt, tik, T["saat_bas"] + k, 0.22 if k < 6 else 0.1)

    # deprem gürültüsü: filtrelenmiş kahverengi gürültü + alt bas
    bas_i, son_i = int(T["sarsinti"] * SR), int(T["kesme"] * SR)
    m = son_i - bas_i
    br = np.cumsum(rng.standard_normal(m)).astype(np.float32)
    br -= np.convolve(br, np.ones(4000) / 4000, "same")
    br = np.convolve(br, np.ones(60) / 60, "same")
    br /= np.max(np.abs(br)) + 1e-9
    tt = np.arange(m) / SR
    zarf = np.clip(tt / 0.12, 0, 1) * (0.75 + 0.25 * np.sin(2 * np.pi * 3.3 * tt + np.sin(tt * 11)))
    zarf *= np.clip((m / SR - tt) / 0.015, 0, 1)
    sub = np.sin(2 * np.pi * 36 * tt + 2 * np.sin(2 * np.pi * 0.9 * tt))
    kirilma = np.zeros(m, np.float32)
    for _ in range(40):
        p = rng.integers(0, m - 3000)
        kirilma[p:p + 3000] += rng.standard_normal(3000) * np.exp(-np.arange(3000) / 300) * rng.uniform(0.1, 0.5)
    efekt[bas_i:son_i] += (br * 0.9 + sub * 0.55 + kirilma * 0.35) * zarf

    # alçak, sade bir drone (Re minör) — D bölümünden sonra
    dr_bas = T["D"]
    for f, g in ((73.42, 1.0), (110.0, 0.55), (146.83, 0.35), (174.61, 0.22), (220.0, 0.12)):
        for det in (-0.35, 0.35):
            muzik += (g * np.sin(2 * np.pi * (f + det) * t + rng.uniform(0, 6))).astype(np.float32)
    ust = np.sin(2 * np.pi * 349.23 * t) * 0.10 + np.sin(2 * np.pi * 440.0 * t) * 0.07
    muzik += (ust * np.clip((t - T["G"]) / 6, 0, 1)).astype(np.float32)
    muzik *= (0.8 + 0.2 * np.sin(2 * np.pi * 0.07 * t)).astype(np.float32)
    muzik *= np.clip((t - dr_bas) / 5, 0, 1) * np.clip((T["son"] - t) / 3.5, 0, 1)
    muzik = muzik / np.max(np.abs(muzik)) * 0.16

    # seslendirme
    for k, x in klip.items():
        ekle(vo, x, T[k], 1.0)
    vo = vo / (np.max(np.abs(vo)) + 1e-9) * 0.85

    # seslendirme sırasında müziği bastır (ducking)
    aktif = np.convolve((np.abs(vo) > 0.02).astype(np.float32), np.ones(int(0.4 * SR)) / (0.4 * SR), "same")
    muzik *= 1 - 0.45 * np.clip(aktif * 3, 0, 1)

    # basit oda yankısı
    def yanki(x, gecikmeler):
        y = x.copy()
        for d, g in gecikmeler:
            k = int(d * SR)
            y[k:] += x[:-k] * g
        return y

    vo = yanki(vo, ((0.031, 0.12), (0.057, 0.08), (0.089, 0.05)))
    efekt = yanki(efekt, ((0.11, 0.2), (0.23, 0.1)))
    toplam = vo + muzik + efekt
    toplam = np.tanh(toplam * 1.1) / np.tanh(1.1)
    sol = toplam
    sag = np.roll(toplam, 8)
    with wave.open(yol, "wb") as w:
        w.setnchannels(2)
        w.setsampwidth(2)
        w.setframerate(SR)
        w.writeframes((np.stack([sol, sag], 1) * 32767).astype(np.int16).tobytes())


# ---------------------------------------------------------------- görüntü
class Kurgu:
    def __init__(self, G, Y, T):
        self.G, self.Y, self.T = G, Y, T
        self.dikey = Y > G
        s = self.s = (G if self.dikey else Y) / 1080
        self.kenar = int((0.09 if self.dikey else 0.11) * G)
        self.f = {
            "saat": ImageFont.truetype(FONT("IBMPlexMono_400Regular.ttf"), int(168 * s)),
            "etiket": ImageFont.truetype(FONT("IBMPlexMono_500Medium.ttf"), int(30 * s)),
            "yer": ImageFont.truetype(FONT("Inter_500Medium.ttf"), int(128 * s)),
            "il": ImageFont.truetype(FONT("Inter_400Regular.ttf"), int((74 if self.dikey else 62) * s)),
            "buyuk": ImageFont.truetype(FONT("Inter_300Light.ttf"), int((78 if self.dikey else 84) * s)),
            "metin": ImageFont.truetype(FONT("Inter_300Light.ttf"), int((64 if self.dikey else 68) * s)),
            "isim": ImageFont.truetype(FONT("Inter_600SemiBold.ttf"), int(52 * s)),
            "unvan": ImageFont.truetype(FONT("Inter_400Regular.ttf"), int(32 * s)),
        }
        rng = np.random.default_rng(2023)
        self.gren = [rng.normal(0, 4.5, (Y, G, 1)).astype(np.float32) for _ in range(6)]
        self.rng = rng

    # --- metin araçları
    def sar(self, metin, font, genislik):
        satirlar, sat = [], ""
        for k in metin.split():
            aday = (sat + " " + k).strip()
            if font.getlength(aday) > genislik and sat:
                satirlar.append(sat)
                sat = k
            else:
                sat = aday
        satirlar.append(sat)
        return satirlar

    def satir(self, img, metin, font, x, taban, t, renk, sure=0.9, hizala="sol"):
        """Maskeli, aşağıdan yukarı kayarak açılan satır."""
        if t <= 0:
            return
        e = expo_out(t / sure)
        a = clamp(t / 0.45)
        asc, desc = font.getmetrics()
        h = asc + desc
        if hizala == "orta":
            x = (self.G - font.getlength(metin)) / 2
        katman = Image.new("RGBA", (self.G, h + 2), (0, 0, 0, 0))
        ImageDraw.Draw(katman).text((x, asc + (1 - e) * h * 0.85), metin, font=font,
                                    fill=tuple(renk) + (int(255 * a),), anchor="ls")
        img.alpha_composite(katman, (0, int(taban - asc)))

    def paragraf(self, img, metin, font, x, ust, t, renk, genislik=None, aralik=1.22, kademe=0.12):
        genislik = genislik or (self.G - 2 * self.kenar)
        asc, desc = font.getmetrics()
        lh = (asc + desc) * aralik
        satirlar = self.sar(metin, font, genislik)
        for i, sat in enumerate(satirlar):
            self.satir(img, sat, font, x, ust + asc + i * lh, t - i * kademe, renk)
        return ust + len(satirlar) * lh

    # --- kare
    def kare(self, zt):
        G, Y, T, s = self.G, self.Y, self.T, self.s
        img = Image.new("RGBA", (G, Y), (0, 0, 0, 255))
        parlaklik = 1.0
        sars = 0.0
        x0 = self.kenar

        if T["saat_bas"] - 0.6 <= zt < T["kesme"]:
            # --- SAAT
            gecen = max(0, int(zt - T["saat_bas"]))
            sn = 54 + gecen
            dk, sn = 16 + sn // 60, sn % 60
            yazi = f"04:{dk:02d}:{sn:02d}"
            giris = smooth((zt - T["saat_bas"] + 0.6) / 0.8)
            renk = [int(c * giris) for c in BEYAZ]
            self.satir(img, yazi, self.f["saat"], 0, Y / 2 + 55 * s, 5, renk, hizala="orta")
            et = "6 ŞUBAT 2023  ·  PAZARTESİ"
            self.satir(img, et, self.f["etiket"], 0, Y / 2 - 120 * s, 5, [int(c * giris) for c in GRI],
                       hizala="orta")
            if zt >= T["sarsinti"]:
                u = zt - T["sarsinti"]
                sars = min(1.0, u / 0.15) * (0.8 + 0.2 * math.sin(u * 23))

        elif T["D"] <= zt < T["D_son"]:
            # --- PAZARCIK / ELBİSTAN
            parlaklik = smooth((T["D_son"] - zt) / 0.6)
            ust = Y * (0.30 if self.dikey else 0.24)
            self.satir(img, "KAHRAMANMARAŞ", self.f["etiket"], x0, ust, zt - T["d1"], GRI)
            self.satir(img, "6 ŞUBAT 2023", self.f["etiket"], x0, ust + 40 * s, zt - T["d1"] - 0.1, GRI)
            y1 = ust + (220 if self.dikey else 190) * s
            y2 = y1 + (330 if self.dikey else 300) * s
            for yy, k, saat, yer, mw in ((y1, "d2", "04:17", "Pazarcık", "7,7"),
                                         (y2, "d3", "13:24", "Elbistan", "7,6")):
                tt = zt - T[k]
                self.satir(img, f"{saat}   {mw} Mw", self.f["etiket"], x0, yy, tt, KIRMIZI)
                self.satir(img, yer, self.f["yer"], x0 - 6 * s, yy + 140 * s, tt - 0.12, BEYAZ)

        elif T["E"] <= zt < T["E_son"]:
            # --- 11 İL
            parlaklik = smooth((T["E_son"] - zt) / 0.6)
            f = self.f["il"]
            asc, desc = f.getmetrics()
            lh = (asc + desc) * 1.12
            if self.dikey:
                self.satir(img, "DEPREMDEN ETKİLENEN", self.f["etiket"], x0, Y * 0.17, zt - T["e0"], GRI)
                self.satir(img, "11 İL", self.f["etiket"], x0, Y * 0.17 + 40 * s, zt - T["e0"] - 0.1, KIRMIZI)
                lx, ly = x0, Y * 0.17 + 130 * s
            else:
                self.satir(img, "DEPREMDEN ETKİLENEN", self.f["etiket"], x0, Y / 2 - 10 * s, zt - T["e0"], GRI)
                self.satir(img, "11 İL", self.f["etiket"], x0, Y / 2 + 30 * s, zt - T["e0"] - 0.1, KIRMIZI)
                lx, ly = int(G * 0.46), (Y - lh * len(ILLER)) / 2
            son = max((i for i in range(len(ILLER)) if zt >= T[f"il{i}"]), default=-1)
            for i, ad in enumerate(ILLER):
                renk = BEYAZ if i == son else (190, 190, 192)
                self.satir(img, ad, f, lx, ly + asc + i * lh, zt - T[f"il{i}"], renk, sure=0.7)

        elif T["F"] <= zt < T["F_son"]:
            # --- KAYIP
            parlaklik = smooth((T["F_son"] - zt) / 0.7)
            f = self.f["buyuk"]
            gen = G - 2 * x0 if self.dikey else G * 0.62
            satirlar = self.sar(METIN["f1"], f, gen)
            asc, desc = f.getmetrics()
            ust = (Y - len(satirlar) * (asc + desc) * 1.2) / 2
            self.paragraf(img, METIN["f1"], f, x0, ust, zt - T["f1"], BEYAZ, gen, aralik=1.2, kademe=0.18)

        elif T["G"] <= zt < T["G_son"]:
            # --- DÖRT YIL
            parlaklik = smooth((T["G_son"] - zt) / 0.8)
            f = self.f["metin"]
            gen = G - 2 * x0 if self.dikey else G * 0.66
            parcalar = ["g1", "g2", "g3"]
            asc, desc = f.getmetrics()
            toplam_sat = sum(len(self.sar(METIN[k], f, gen)) for k in parcalar)
            y = (Y - (toplam_sat * (asc + desc) * 1.22 + 2 * 50 * s)) / 2
            for i, k in enumerate(parcalar):
                sonraki = T[parcalar[i + 1]] if i + 1 < len(parcalar) else 1e9
                soluk = smooth((zt - sonraki) / 0.8)
                renk = [int(BEYAZ[c] + (KOYU[c] - BEYAZ[c]) * soluk) for c in range(3)]
                y = self.paragraf(img, METIN[k], f, x0, y, zt - T[k], renk, gen) + 50 * s

        elif T["H"] <= zt:
            # --- ANMA + İMZA
            parlaklik = smooth((T["son"] - zt) / 1.6)
            f = self.f["metin"]
            gen = G - 2 * x0 if self.dikey else G * 0.62
            ust = Y * (0.30 if self.dikey else 0.26)
            self.paragraf(img, METIN["h1"], f, x0, ust, zt - T["h1"], BEYAZ, gen, kademe=0.16)
            iy = Y - (360 if self.dikey else 250) * s
            ti = zt - T["imza"]
            if ti > 0:
                d = ImageDraw.Draw(img)
                w = 64 * s * expo_out(ti / 1.2)
                d.rectangle([x0, iy - 70 * s, x0 + w, iy - 70 * s + max(2, int(3 * s))],
                            fill=KIRMIZI + (int(255 * clamp(ti / 0.4)),))
            self.satir(img, BASKAN_ADI, self.f["isim"], x0, iy, ti - 0.2, BEYAZ)
            self.satir(img, UNVAN, self.f["unvan"], x0, iy + 56 * s, ti - 0.35, GRI)

        a = np.asarray(img.convert("RGB"), np.float32) * parlaklik

        if sars > 0:
            amp = 38 * s * sars
            dx, dy = self.rng.normal(0, amp, 2).astype(int)
            a = np.roll(a, (dy, dx), (0, 1))
            k = int(10 * s * sars) + 1
            a[..., 0] = np.roll(a[..., 0], k, 1)
            a[..., 2] = np.roll(a[..., 2], -k, 1)
            a = 0.55 * a + 0.45 * np.roll(a, int(amp * 0.5) + 1, 0)

        a += self.gren[int(zt * FPS) % len(self.gren)]
        return np.clip(a, 0, 255).astype(np.uint8)


def video_olustur(G, Y, cikti, klip, T, ses_yolu):
    o = Kurgu(G, Y, T)
    komut = [ffmpeg(), "-y", "-loglevel", "error",
             "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{G}x{Y}", "-r", str(FPS), "-i", "-",
             "-i", ses_yolu,
             "-c:v", "libx264", "-preset", "slow", "-crf", "25", "-maxrate", "10M", "-bufsize", "20M", "-pix_fmt", "yuv420p",
             "-c:a", "aac", "-b:a", "192k", "-shortest", "-movflags", "+faststart", cikti]
    p = subprocess.Popen(komut, stdin=subprocess.PIPE)
    kare_sayisi = int(T["son"] * FPS)
    for i in range(kare_sayisi):
        p.stdin.write(o.kare(i / FPS).tobytes())
        if i % (FPS * 10) == 0:
            print(f"  {os.path.basename(cikti)}: {i / FPS:5.1f} / {T['son']:.1f} sn", flush=True)
    p.stdin.close()
    p.wait()
    print(f"Hazır: {cikti}")


if __name__ == "__main__":
    arg = sys.argv[1:]
    klip = seslendirme_hazirla()
    T = zaman_cizelgesi(klip)
    print(f"Süre: {T['son']:.1f} sn")
    if "--onizleme" in arg:
        for G, Y, ad in ((1920, 1080, "yatay"), (1080, 1920, "dikey")):
            o = Kurgu(G, Y, T)
            for k in ("saat_bas", "sarsinti", "d3", "E_son", "F_son", "G_son", "son"):
                zt = T[k] + (2.5 if k in ("saat_bas", "d3") else 0.12 if k == "sarsinti" else -1.0)
                Image.fromarray(o.kare(zt)).save(os.path.join(KLASOR, f".onizleme_{ad}_{k}.png"))
        sys.exit()
    ses_yolu = os.path.join(KLASOR, ".ses_karisim.wav")
    ses_olustur(klip, T, ses_yolu)
    if "--dikey" not in arg:
        video_olustur(1920, 1080, os.path.join(KLASOR, "6_subat_anma_yatay.mp4"), klip, T, ses_yolu)
    if "--yatay" not in arg:
        video_olustur(1080, 1920, os.path.join(KLASOR, "6_subat_anma_dikey.mp4"), klip, T, ses_yolu)
    os.remove(ses_yolu)
