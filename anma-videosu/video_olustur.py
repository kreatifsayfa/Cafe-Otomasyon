#!/usr/bin/env python3
"""
6 Şubat Depremleri Anma Videosu — Bölükyayla Belediyesi / Başkan Mehmet Turan

Kurulum:
    pip install pillow numpy imageio-ffmpeg edge-tts svg.path
Kullanım:
    python3 video_olustur.py            # yatay (1920x1080) + dikey (1080x1920)
    python3 video_olustur.py --yatay | --dikey
    python3 video_olustur.py --onizleme # her çekimden bir kare (png)

Fotoğraflar: Wikimedia Commons (kamu malı / CC0 / CC BY / CC BY-SA) — kaynak/foto/kaynaklar.json
Müzik: "Heartbreaking" — Kevin MacLeod (incompetech.com), CC BY 4.0
Harita: turkey-map-react (MIT)
Seslendirme: edge-tts (tr-TR-AhmetNeural). Gerçek kayıt kullanmak için ses/ klasörüne
aynı adlarla (b1.wav, d2.wav ...) dosya koymak yeterli; zamanlama kayda göre kurulur.
"""
import json
import math
import os
import re
import subprocess
import sys
import wave

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont, ImageOps
import imageio_ffmpeg
from svg.path import parse_path

# ---------------------------------------------------------------- AYARLAR
BASKAN_ADI = "Mehmet Turan"
UNVAN = "Bölükyayla Belediye Başkanı"
GECEN_YIL = "Dört"  # 6 Şubat 2027 için "Dört", 2026 için "Üç"

SES = "tr-TR-AhmetNeural"
SES_HIZ = "-10%"
SES_PERDE = "-3Hz"

# Seslendirme metinleri (altyazı olarak da kullanılır)
METIN = {
    "b1": "Pazarcık merkezli, yedi nokta yedi büyüklüğünde bir deprem.",
    "b2": "Dokuz saat sonra Elbistan'da, yedi nokta altı.",
    "b3": "Binlerce bina yıkıldı. Milyonlarca insan o sabahı soğukta, sokakta karşıladı.",
    "c0": "On bir ilimiz.",
    "d1": "Elli binden fazla canımızı kaybettik.",
    "d2": "Kimi evladını, kimi annesini, kimi bütün ailesini.",
    "d3": "Şehirlerimizin hafızası enkazın altında kaldı.",
    "e1": f"{GECEN_YIL} yıl geçti.",
    "e2": "Yıkılan yerler yeniden yapılıyor.",
    "e3": "Ama kaybettiklerimizin yeri, hep boş kalacak.",
    "f1": "Depremde hayatını kaybeden tüm canlarımızı, rahmetle anıyoruz.",
}
ALTYAZI = {  # ekranda farklı yazılacaksa
    "b1": "Pazarcık merkezli, 7,7 büyüklüğünde bir deprem.",
    "b2": "Dokuz saat sonra Elbistan'da, 7,6.",
}
# Harita sırası (seslendirme bu sırayla okur)
ILLER = ["Kahramanmaraş", "Hatay", "Adıyaman", "Malatya", "Gaziantep", "Osmaniye",
         "Diyarbakır", "Şanlıurfa", "Adana", "Kilis", "Elazığ"]
for _i, _ad in enumerate(ILLER):
    METIN[f"il{_i}"] = _ad

# Çekimler: (dosya, konum/tarih etiketi, yatay odak 0..1)
CEKIM_B = [("004", "DİYARBAKIR · 6 ŞUBAT 2023", 0.5), ("012", "GAZİANTEP · 6 ŞUBAT 2023", 0.55),
           ("015", "GAZİANTEP · 6 ŞUBAT 2023", 0.5), ("007", "GAZİANTEP · 7 ŞUBAT 2023", 0.45),
           ("080", "ADIYAMAN · 10 ŞUBAT 2023", 0.5), ("002", "GAZİANTEP · 8 ŞUBAT 2023", 0.5)]
CEKIM_D = [("019", "ANTAKYA · MAYIS 2023", 0.5), ("017", "ANTAKYA · 13 ŞUBAT 2023", 0.45),
           ("065", "ADIYAMAN · MART 2023", 0.5), ("072", "ADIYAMAN · ŞUBAT 2023", 0.5),
           ("089", "GAZİANTEP · 10 ŞUBAT 2023", 0.55), ("093", "İSKENDERUN · TEMMUZ 2023", 0.5)]
CEKIM_E = [("084", "ANTAKYA · OCAK 2024", 0.5), ("018", "GAZİANTEP KALESİ · OCAK 2024", 0.5),
           ("039", "ANTAKYA · NİSAN 2023", 0.5)]

FPS = 30
SR = 48000
KLASOR = os.path.dirname(os.path.abspath(__file__))
SES_KLASOR = os.path.join(KLASOR, "ses")
FOTO = lambda ad: os.path.join(KLASOR, "kaynak", "foto", ad + ".jpg")
MUZIK = os.path.join(KLASOR, "kaynak", "muzik", "Heartbreaking.mp3")
FONT = lambda ad: os.path.join(KLASOR, "fonts", ad)

BEYAZ = (238, 236, 232)
GRI = (150, 150, 150)
KIRMIZI = (196, 38, 36)


# ---------------------------------------------------------------- yardımcılar
def clamp(x, a=0.0, b=1.0):
    return max(a, min(b, x))


def smooth(t):
    t = clamp(t)
    return t * t * (3 - 2 * t)


def tr_buyuk(metin):
    return metin.replace("i", "İ").replace("ı", "I").upper()


def tr_kucuk(metin):
    return metin.replace("İ", "i").replace("I", "ı").lower()


def tarih_duz(metin):
    """'13 ŞUBAT 2023' -> '13 Şubat 2023'"""
    return " ".join(k if k.isdigit() else tr_kucuk(k)[:1].upper().replace("İ", "İ") + tr_kucuk(k)[1:]
                    for k in metin.split())


def smooth_arr(x):
    x = np.clip(x, 0, 1)
    return x * x * (3 - 2 * x)


def expo_out(t):
    t = clamp(t)
    return 1.0 if t >= 1 else 1 - 2 ** (-10 * t)


def ffmpeg():
    return imageio_ffmpeg.get_ffmpeg_exe()


def ses_oku(yol, kirp=True):
    ham = subprocess.run([ffmpeg(), "-loglevel", "error", "-i", yol, "-f", "s16le",
                          "-ac", "1", "-ar", str(SR), "-"], capture_output=True, check=True).stdout
    x = np.frombuffer(ham, np.int16).astype(np.float32) / 32768
    if kirp:
        idx = np.where(np.abs(x) > 0.01)[0]
        if len(idx):
            x = x[max(0, idx[0] - int(0.02 * SR)): idx[-1] + int(0.08 * SR)]
    return x


def seslendirme_hazirla():
    os.makedirs(SES_KLASOR, exist_ok=True)
    klipler = {}
    for anahtar, metin in METIN.items():
        adaylar = [os.path.join(SES_KLASOR, anahtar + u) for u in (".wav", ".mp3")]
        yol = next((a for a in adaylar if os.path.exists(a)), None)
        if yol is None:
            yol = adaylar[1]
            subprocess.run(["edge-tts", "--voice", SES, f"--rate={SES_HIZ}", f"--pitch={SES_PERDE}",
                            "--text", metin, "--write-media", yol], check=True,
                           stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        klipler[anahtar] = ses_oku(yol)
    return klipler


# ---------------------------------------------------------------- zaman çizelgesi
def zaman_cizelgesi(klip):
    s = lambda k: len(klip[k]) / SR
    T = {"saat_bas": 1.0, "sarsinti": 7.0, "kesme": 9.6}
    T["B"] = 10.6
    T["b1"] = T["B"] + 1.2
    T["b2"] = T["b1"] + s("b1") + 1.0
    T["b3"] = T["b2"] + s("b2") + 1.4
    T["B_son"] = T["b3"] + s("b3") + 2.0
    T["ses_kart"] = T["B_son"]            # "Sesimi duyan var mı?"
    T["C"] = T["ses_kart"] + 4.2
    T["c0"] = T["C"] + 0.8
    c = T["c0"] + s("c0") + 0.5
    for i in range(len(ILLER)):
        T[f"il{i}"] = c
        c += max(0.85, s(f"il{i}") + 0.25)
    T["C_son"] = c + 1.6
    T["D"] = T["C_son"]
    T["d1"] = T["D"] + 1.2
    T["d2"] = T["d1"] + s("d1") + 1.3
    T["d3"] = T["d2"] + s("d2") + 1.5
    T["D_son"] = T["d3"] + s("d3") + 2.2
    T["E"] = T["D_son"] + 0.6
    T["e1"] = T["E"] + 1.0
    T["e2"] = T["e1"] + s("e1") + 1.0
    T["e3"] = T["e2"] + s("e2") + 0.9
    T["E_son"] = T["e3"] + s("e3") + 2.4
    T["F"] = T["E_son"] + 0.8
    T["f1"] = T["F"] + 0.6
    T["imza"] = T["f1"] + s("f1") + 1.2
    T["kunye"] = T["imza"] + 6.0
    T["son"] = T["kunye"] + 5.0
    T["_sure"] = {k: s(k) for k in klip}
    return T


# ---------------------------------------------------------------- ses
def ses_olustur(klip, T, yol):
    n = int((T["son"] + 0.5) * SR)
    t = np.arange(n) / SR
    rng = np.random.default_rng(6)
    efekt = np.zeros(n, np.float32)
    vo = np.zeros(n, np.float32)

    def ekle(hedef, x, bas, k=1.0):
        i = int(bas * SR)
        j = min(n, i + len(x))
        hedef[i:j] += x[: j - i] * k

    # saat tıkırtısı
    tt = np.arange(int(0.05 * SR)) / SR
    tik = np.sin(2 * np.pi * 2900 * tt) * np.exp(-tt * 180) + \
        np.diff(rng.standard_normal(len(tt) + 1)) * np.exp(-tt * 400) * 0.3
    for k in range(9):
        ekle(efekt, tik, T["saat_bas"] + k, 0.2 if k < 6 else 0.08)

    # deprem uğultusu
    a, b = int(T["sarsinti"] * SR), int(T["kesme"] * SR)
    m = b - a
    br = np.cumsum(rng.standard_normal(m)).astype(np.float32)
    br -= np.convolve(br, np.ones(4000) / 4000, "same")
    br = np.convolve(br, np.ones(60) / 60, "same")
    br /= np.max(np.abs(br)) + 1e-9
    u = np.arange(m) / SR
    zarf = np.clip(u / 0.12, 0, 1) * (0.75 + 0.25 * np.sin(2 * np.pi * 3.3 * u + np.sin(u * 11)))
    zarf *= np.clip((m / SR - u) / 0.015, 0, 1)
    sub = np.sin(2 * np.pi * 36 * u + 2 * np.sin(2 * np.pi * 0.9 * u))
    kir = np.zeros(m, np.float32)
    for _ in range(40):
        p = rng.integers(0, m - 3000)
        kir[p:p + 3000] += rng.standard_normal(3000) * np.exp(-np.arange(3000) / 300) * rng.uniform(0.1, 0.5)
    efekt[a:b] += (br * 0.9 + sub * 0.55 + kir * 0.35) * zarf

    # müzik: B bölümünde başlar, "Sesimi duyan var mı?" kartında kısılır, sonda söner
    muz = ses_oku(MUZIK, kirp=False)
    muz = muz / (np.sqrt(np.mean(muz ** 2)) + 1e-9) * 0.075
    muzik = np.zeros(n, np.float32)
    ekle(muzik, muz, T["B"] + 0.3)
    kart = np.clip(1 - smooth_arr((t - T["ses_kart"]) / 0.8) + smooth_arr((t - T["C"] + 0.6) / 1.2), 0.12, 1)
    muzik *= kart
    muzik *= np.clip((T["son"] - t) / 4.0, 0, 1)

    for k, x in klip.items():
        ekle(vo, x, T[k])
    vo = vo / (np.max(np.abs(vo)) + 1e-9) * 0.8
    aktif = np.convolve((np.abs(vo) > 0.02).astype(np.float32), np.ones(int(0.5 * SR)) / (0.5 * SR), "same")
    muzik *= 1 - 0.4 * np.clip(aktif * 3, 0, 1)

    def yanki(x, gec):
        y = x.copy()
        for d, g in gec:
            k = int(d * SR)
            y[k:] += x[:-k] * g
        return y

    vo = yanki(vo, ((0.029, 0.10), (0.053, 0.06)))
    efekt = yanki(efekt, ((0.11, 0.2), (0.23, 0.1)))
    top = vo + muzik + efekt
    top = np.tanh(top * 1.1) / np.tanh(1.1)
    with wave.open(yol, "wb") as w:
        w.setnchannels(2)
        w.setsampwidth(2)
        w.setframerate(SR)
        w.writeframes((np.stack([top, np.roll(top, 8)], 1) * 32767).astype(np.int16).tobytes())


# ---------------------------------------------------------------- harita
def harita_yukle():
    veri = json.load(open(os.path.join(KLASOR, "kaynak", "iller.json"), encoding="utf-8"))
    iller = {}
    for il in veri:
        parcalar, cur = [], []
        for seg in parse_path(il["path"]):
            if type(seg).__name__ == "Move":
                if len(cur) > 2:
                    parcalar.append(cur)
                cur = []
                continue
            for k in range(8):
                p = seg.point(k / 8)
                cur.append((p.real, p.imag))
        if len(cur) > 2:
            parcalar.append(cur)
        iller[il["name"]] = parcalar
    return iller


# ---------------------------------------------------------------- görüntü
class Kurgu:
    def __init__(self, G, Y, T):
        self.G, self.Y, self.T = G, Y, T
        self.dikey = Y > G
        s = self.s = (G if self.dikey else Y) / 1080
        self.kenar = int((0.08 if self.dikey else 0.07) * G)
        F = lambda ad, px: ImageFont.truetype(FONT(ad), int(px * s))
        d = self.dikey
        self.f = {
            "saat": F("Cinzel_400Regular.ttf", 150 if d else 176),
            "ust": F("Cinzel_500Medium.ttf", 26),
            "sehir": F("Cinzel_500Medium.ttf", 30 if d else 28),
            "tarih": F("CormorantGaramond_400Regular_Italic.ttf", 32 if d else 30),
            "alt": F("SourceSans3_400Regular.ttf", 44 if d else 42),
            "kart": F("CormorantGaramond_300Light_Italic.ttf", 92 if d else 104),
            "il": F("Cinzel_400Regular.ttf", 84 if d else 80),
            "sayac": F("Cinzel_400Regular.ttf", 34),
            "anma": F("CormorantGaramond_300Light.ttf", 72 if d else 78),
            "isim": F("Cinzel_500Medium.ttf", 44),
            "unvan": F("CormorantGaramond_400Regular_Italic.ttf", 38),
            "kunye_bas": F("Cinzel_500Medium.ttf", 22),
            "kunye": F("SourceSans3_400Regular.ttf", 25 if d else 24),
        }
        rng = np.random.default_rng(2023)
        self.gren = [rng.normal(0, 5, (Y, G, 1)).astype(np.float32) for _ in range(6)]
        yy, xx = np.mgrid[0:Y, 0:G].astype(np.float32)
        r = np.sqrt(((xx - G / 2) / (G / 2)) ** 2 + ((yy - Y / 2) / (Y / 2)) ** 2)
        vinyet = np.clip(1.05 - 0.45 * r ** 2.2, 0.3, 1.0)
        # altyazı ve konum etiketi okunsun diye alt / üst karartma
        dy = yy / Y
        alt = 1 - 0.78 * smooth_arr((dy - (0.55 if self.dikey else 0.62)) / 0.33)
        ust = 1 - 0.45 * smooth_arr(((0.14 if self.dikey else 0.20) - dy) / 0.14)
        self.vinyet = (vinyet * alt * ust * 0.9)[..., None]
        self.rng = rng
        self.fotolar = {}
        self._harita_hazirla()
        self.plan = []
        for bas, son, liste in ((T["B"], T["B_son"], CEKIM_B), (T["D"], T["D_son"], CEKIM_D),
                                (T["E"], T["E_son"], CEKIM_E)):
            d = (son - bas) / len(liste)
            for i, (ad, et, fx) in enumerate(liste):
                self.plan.append((bas + i * d, bas + (i + 1) * d, ad, et, fx, i))

    # --- fotoğraf: siyah-beyaz, kontrast eğrisi, hafif sıcak ton
    def foto(self, ad):
        if ad not in self.fotolar:
            im = Image.open(FOTO(ad)).convert("L")
            im = ImageOps.autocontrast(im, cutoff=0.5)
            im = im.point([int(255 * smooth(v / 255) * 0.92 + v * 0.08) for v in range(256)])
            kap = max(self.G * 1.18 / im.width, self.Y * 1.18 / im.height)
            im = im.resize((int(im.width * kap), int(im.height * kap)), Image.LANCZOS)
            self.fotolar[ad] = Image.merge("RGB", (im.point(lambda v: min(255, int(v * 1.03))), im,
                                                   im.point(lambda v: int(v * 0.95))))
        return self.fotolar[ad]

    def ken_burns(self, ad, u, fx, yon):
        im = self.foto(ad)
        G, Y = self.G, self.Y
        z = 1.0 + 0.10 * (u if yon % 2 == 0 else 1 - u)
        # görünür kesit: çıktı oranında, fotoğrafa sığan en büyük kutunun 1/z'si
        oran = G / Y
        w = min(im.width, im.height * oran) / z
        h = w / oran
        kaydir = (u - 0.5) * 0.06 * (1 if yon % 3 else -1)
        cx = clamp(fx + kaydir, w / im.width / 2, 1 - w / im.width / 2) * im.width
        cy = im.height / 2
        return im.transform((G, Y), Image.EXTENT, (cx - w / 2, cy - h / 2, cx + w / 2, cy + h / 2),
                            Image.BILINEAR)

    def _harita_hazirla(self):
        iller = harita_yukle()
        xs = [x for p in iller.values() for par in p for x, _ in par]
        ys = [y for p in iller.values() for par in p for _, y in par]
        x0, x1, y0, y1 = min(xs), max(xs), min(ys), max(ys)
        G, Y = self.G, self.Y
        if self.dikey:  # dikeyde deprem bölgesine yakınlaş
            olc = G * 1.55 / (x1 - x0)
            ox = G / 2 - (x1 - x0) * 0.66 * olc
            oy = Y * 0.36 - (y1 - y0) * 0.62 * olc
        else:
            olc = G * 0.66 / (x1 - x0)
            ox = (G - (x1 - x0) * olc) / 2
            oy = Y * 0.36 - (y1 - y0) * olc / 2
        self.harita = {ad: [[((x - x0) * olc + ox, (y - y0) * olc + oy) for x, y in par] for par in p]
                       for ad, p in iller.items()}
        self.harita_taban = Image.new("RGBA", (G, Y), (0, 0, 0, 0))
        d = ImageDraw.Draw(self.harita_taban)
        for p in self.harita.values():
            for par in p:
                d.polygon(par, fill=(34, 34, 36, 255), outline=(66, 66, 70, 255))
        self.il_katman = {}
        for ad in ILLER:
            k = Image.new("RGBA", (G, Y), (0, 0, 0, 0))
            dd = ImageDraw.Draw(k)
            for par in self.harita[ad]:
                dd.polygon(par, fill=KIRMIZI + (255,), outline=(110, 18, 18, 255))
            self.il_katman[ad] = k

    # --- metin
    def sar(self, metin, font, genislik):
        sat, cur = [], ""
        for k in metin.split():
            aday = (cur + " " + k).strip()
            if font.getlength(aday) > genislik and cur:
                sat.append(cur)
                cur = k
            else:
                cur = aday
        sat.append(cur)
        return sat

    def genislik(self, metin, font, iz=0.0):
        return font.getlength(metin) + iz * font.size * max(0, len(metin) - 1)

    def _ciz(self, d, xy, metin, font, fill, anchor, iz):
        if not iz:
            d.text(xy, metin, font=font, fill=fill, anchor=anchor)
            return
        # harf aralıklı (tracking) çizim
        w = self.genislik(metin, font, iz)
        x = xy[0] - {"l": 0, "m": w / 2, "r": w}[anchor[0]]
        for c in metin:
            d.text((x, xy[1]), c, font=font, fill=fill, anchor="l" + anchor[1])
            x += font.getlength(c) + iz * font.size

    def yazi(self, img, xy, metin, font, renk, alfa=1.0, golge=True, anchor="la", iz=0.0):
        if alfa <= 0:
            return
        if golge:
            g = Image.new("RGBA", img.size, (0, 0, 0, 0))
            self._ciz(ImageDraw.Draw(g), (xy[0], xy[1] + 2 * self.s), metin, font,
                      (0, 0, 0, int(230 * alfa)), anchor, iz)
            img.alpha_composite(g.filter(ImageFilter.GaussianBlur(6 * self.s)))
        katman = Image.new("RGBA", img.size, (0, 0, 0, 0))
        self._ciz(ImageDraw.Draw(katman), xy, metin, font, tuple(renk) + (int(255 * alfa),), anchor, iz)
        img.alpha_composite(katman)

    def saat(self, img, metin, y, alfa):
        """Rakamlar sabit genişlikte kutulara yerleşir; saniyeler değişirken yazı kaymaz."""
        f = self.f["saat"]
        kutu = max(f.getlength(str(r)) for r in range(10)) * 1.02
        iki_nokta = f.getlength(":") * 1.6
        genis = [iki_nokta if c == ":" else kutu for c in metin]
        x = (self.G - sum(genis)) / 2
        for c, w in zip(metin, genis):
            self.yazi(img, (x + w / 2, y), c, f, BEYAZ, alfa, False, "ms")
            x += w

    def altyazi(self, img, zt):
        T = self.T
        for k in ("b1", "b2", "b3", "c0", "d1", "d2", "d3", "e1", "e2", "e3"):
            bas, sure = T[k], T["_sure"][k]
            if bas - 0.1 <= zt <= bas + sure + 0.5:
                a = clamp((zt - bas + 0.1) / 0.2) * clamp((bas + sure + 0.5 - zt) / 0.2)
                f = self.f["alt"]
                satirlar = self.sar(ALTYAZI.get(k, METIN[k]), f, self.G - 2 * self.kenar)
                asc, desc = f.getmetrics()
                lh = (asc + desc) * 1.15
                taban = self.Y - (300 if self.dikey else 96) * self.s
                for i, sat in enumerate(satirlar):
                    self.yazi(img, (self.G / 2, taban - (len(satirlar) - 1 - i) * lh), sat, f, BEYAZ, a,
                              anchor="ms")

    # --- kare
    def kare(self, zt):
        G, Y, T, s = self.G, self.Y, self.T, self.s
        img = Image.new("RGBA", (G, Y), (0, 0, 0, 255))
        parlak, sars, foto_var = 1.0, 0.0, False

        if T["saat_bas"] - 0.6 <= zt < T["kesme"]:
            sn = 54 + max(0, int(zt - T["saat_bas"]))
            a = smooth((zt - T["saat_bas"] + 0.6) / 0.8)
            self.saat(img, f"04:{16 + sn // 60:02d}:{sn % 60:02d}", Y / 2 + 60 * s, a)
            self.yazi(img, (G / 2, Y / 2 - 125 * s), "6 ŞUBAT 2023", self.f["ust"], GRI, a, False, "ms", iz=0.45)
            if zt >= T["sarsinti"]:
                u = zt - T["sarsinti"]
                sars = min(1.0, u / 0.15) * (0.8 + 0.2 * math.sin(u * 23))

        elif T["ses_kart"] <= zt < T["C"]:
            a = smooth((zt - T["ses_kart"] - 0.6) / 0.9) * smooth((T["C"] - 0.3 - zt) / 0.6)
            self.yazi(img, (G / 2, Y / 2), "“Sesimi duyan var mı?”", self.f["kart"], BEYAZ, a, False, "mm")

        elif T["C"] <= zt < T["C_son"]:
            parlak = smooth((zt - T["C"]) / 0.8) * smooth((T["C_son"] - zt) / 0.6)
            img.alpha_composite(self.harita_taban)
            son = -1
            for i, ad in enumerate(ILLER):
                u = zt - T[f"il{i}"]
                if u > 0:
                    son = i
                    k = self.il_katman[ad]
                    if u < 0.5:
                        k = k.copy()
                        k.putalpha(k.getchannel("A").point(lambda v, a=smooth(u / 0.5): int(v * a)))
                    img.alpha_composite(k)
            ey = Y * (0.72 if self.dikey else 0.84)
            ba = clamp((zt - T["c0"]) / 0.6)
            self.yazi(img, (G / 2, ey - 125 * s), "DEPREMDEN ETKİLENEN İLLER", self.f["ust"], GRI, ba, False,
                      "ms", iz=0.45)
            if son >= 0:
                u = zt - T[f"il{son}"]
                dy = (1 - expo_out(u / 0.6)) * 16 * s
                self.yazi(img, (G / 2, ey + dy), tr_buyuk(ILLER[son]), self.f["il"], BEYAZ, clamp(u / 0.3), False,
                          "ms", iz=0.12)


        elif T["F"] <= zt:
            parlak = smooth((T["son"] - zt) / 1.2)
            x0 = self.kenar
            if zt < T["kunye"]:
                a = smooth((zt - T["f1"]) / 1.2) * smooth((T["kunye"] - zt) / 0.8)
                f = self.f["anma"]
                gen = G - 2 * x0 if self.dikey else G * 0.62
                asc, desc = f.getmetrics()
                lh = (asc + desc) * 1.12
                satirlar = self.sar(METIN["f1"], f, gen)
                ust = Y * (0.40 if self.dikey else 0.36) - len(satirlar) * lh / 2
                for i, sat in enumerate(satirlar):
                    self.yazi(img, (G / 2, ust + (i + 1) * lh), sat, f, BEYAZ, a, False, "ms")
                ti = zt - T["imza"]
                if ti > 0:
                    b = smooth(ti / 1.2) * smooth((T["kunye"] - zt) / 0.8)
                    iy = Y * (0.66 if self.dikey else 0.70)
                    w = 36 * s * expo_out(ti / 1.4)
                    ImageDraw.Draw(img).line([(G / 2 - w, iy - 78 * s), (G / 2 + w, iy - 78 * s)],
                                             fill=(150, 140, 125, int(255 * b)), width=max(1, int(1.5 * s)))
                    self.yazi(img, (G / 2, iy), tr_buyuk(BASKAN_ADI), self.f["isim"], BEYAZ, b, False, "ms", iz=0.22)
                    self.yazi(img, (G / 2, iy + 56 * s), UNVAN, self.f["unvan"], (185, 180, 172), b, False, "ms")
            else:
                a = smooth((zt - T["kunye"]) / 0.8)
                f = self.f["kunye"]
                asc, desc = f.getmetrics()
                y = Y * (0.36 if self.dikey else 0.33)
                for baslik, satirlar in KUNYE:
                    self.yazi(img, (G / 2, y), baslik, self.f["kunye_bas"], (200, 195, 185), a, False, "ms", iz=0.4)
                    y += (asc + desc) * 1.6
                    for p in satirlar:
                        for sat in self.sar(p, f, G - 2 * x0):
                            self.yazi(img, (G / 2, y), sat, f, (160, 160, 160), a, False, "ms")
                            y += (asc + desc) * 1.3
                    y += (asc + desc) * 1.2

        # fotoğraflı çekimler (çekimler arası 0.5 sn çapraz geçiş)
        for bas, son, ad, et, fx, i in self.plan:
            if bas <= zt < son:
                img = self.ken_burns(ad, (zt - bas) / (son - bas), fx, i).convert("RGBA")
                foto_var = True
                if zt - bas < 0.5:
                    onceki = [p for p in self.plan if abs(p[1] - bas) < 1e-6]
                    if onceki:
                        pb, ps, pad, _, pfx, pi = onceki[0]
                        eski = self.ken_burns(pad, (zt - pb) / (ps - pb), pfx, pi).convert("RGBA")
                        img = Image.blend(eski, img, smooth((zt - bas) / 0.5))
                img = Image.fromarray((np.asarray(img.convert("RGB"), np.float32) * self.vinyet)
                                      .astype(np.uint8)).convert("RGBA")
                sehir, tarih = [p.strip() for p in et.split("·")]
                ea = clamp((zt - bas - 0.4) / 0.6) * clamp((son - zt - 0.2) / 0.3)
                ey = (170 if self.dikey else 92) * s
                self.yazi(img, (self.kenar, ey), sehir, self.f["sehir"], (232, 228, 220), ea, True, "ls", iz=0.32)
                self.yazi(img, (self.kenar, ey + 40 * s), tarih_duz(tarih),
                          self.f["tarih"], (200, 196, 188), ea, True, "ls")
                break
        for b0, b1 in (("B", "B_son"), ("D", "D_son"), ("E", "E_son")):
            if T[b0] <= zt < T[b1]:
                parlak = smooth((zt - T[b0]) / 0.7) * smooth((T[b1] - zt) / 0.7)

        self.altyazi(img, zt)
        a = np.asarray(img.convert("RGB"), np.float32)
        a *= parlak
        if sars > 0:
            amp = 38 * s * sars
            dx, dy = self.rng.normal(0, amp, 2).astype(int)
            a = np.roll(a, (dy, dx), (0, 1))
            k = int(10 * s * sars) + 1
            a[..., 0] = np.roll(a[..., 0], k, 1)
            a[..., 2] = np.roll(a[..., 2], -k, 1)
            a = 0.55 * a + 0.45 * np.roll(a, int(amp * 0.5) + 1, 0)
        a += self.gren[int(zt * FPS) % len(self.gren)] * (1.0 if foto_var else 0.5)
        return np.clip(a, 0, 255).astype(np.uint8)


def kunye_olustur():
    k = json.load(open(os.path.join(KLASOR, "kaynak", "foto", "kaynaklar.json"), encoding="utf-8"))
    kullanilan = {ad for ad, *_ in CEKIM_B + CEKIM_D + CEKIM_E}
    gruplar = {}
    for no, v in k.items():
        if no.zfill(3) in kullanilan:
            lis = {"Public domain": "VOA, kamu malı"}.get(v["lisans"], v["lisans"])
            yazar = re.sub(r"\s*(\(VOA\)|\(Voice of America\)|/VOA)", "", " ".join(v["yazar"].split()))
            gruplar.setdefault(lis, set()).add(yazar)
    foto = [f"{', '.join(sorted(y))} ({lis})" for lis, y in gruplar.items()] + ["Kaynak: Wikimedia Commons"]
    return [("FOTOĞRAFLAR", foto),
            ("MÜZİK", ["“Heartbreaking” — Kevin MacLeod (incompetech.com), CC BY 4.0"])]


KUNYE = kunye_olustur()


def video_olustur(G, Y, cikti, T, ses_yolu):
    o = Kurgu(G, Y, T)
    komut = [ffmpeg(), "-y", "-loglevel", "error",
             "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{G}x{Y}", "-r", str(FPS), "-i", "-",
             "-i", ses_yolu,
             "-c:v", "libx264", "-preset", "slow", "-crf", "26", "-maxrate", "6M", "-bufsize", "12M",
             "-pix_fmt", "yuv420p", "-c:a", "aac", "-b:a", "192k", "-shortest", "-movflags", "+faststart", cikti]
    p = subprocess.Popen(komut, stdin=subprocess.PIPE)
    for i in range(int(T["son"] * FPS)):
        p.stdin.write(o.kare(i / FPS).tobytes())
        if i % (FPS * 15) == 0:
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
            anlar = [T["saat_bas"] + 2, T["sarsinti"] + 0.1, T["ses_kart"] + 2, T["il10"] + 1,
                     T["imza"] + 2, T["kunye"] + 2] + [(b + e) / 2 for b, e, *_ in o.plan]
            for j, zt in enumerate(sorted(anlar)):
                Image.fromarray(o.kare(zt)).save(os.path.join(KLASOR, f".onizleme_{ad}_{j:02d}.png"))
        sys.exit()
    ses_yolu = os.path.join(KLASOR, ".ses_karisim.wav")
    ses_olustur(klip, T, ses_yolu)
    if "--dikey" not in arg:
        video_olustur(1920, 1080, os.path.join(KLASOR, "6_subat_anma_yatay.mp4"), T, ses_yolu)
    if "--yatay" not in arg:
        video_olustur(1080, 1920, os.path.join(KLASOR, "6_subat_anma_dikey.mp4"), T, ses_yolu)
    os.remove(ses_yolu)
