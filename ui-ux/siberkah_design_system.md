---
name: "Berkah Kreatif Floral Luxe"
description: "Sistem desain elegan, artistik, dan hangat untuk platform sewa buket bunga & akrilik wisuda estetik @berkahkreatif_ (SIBERKAH)."
---

## Overview

SIBERKAH adalah platform digital penyewaan perlengkapan kreatif, bunga segar/artificial, standing mirror sign, dan papan akrilik wisuda estetik dari **Berkah Kreatif** (@berkahkreatif_). 

Arah visual bertransisi dari nuansa fungsional kaku menjadi **Floral Elegance & Modern Aesthetic**:
- **Tone & Mood:** Mewah namun ramah (Velvet Crimson Burgundy, Rose Petal Pink, Soft Sand & Warm Champagne), artistik, romantis wisuda, dan hidup (vibrant, bernapas, tidak flat/native).
- **Elemen Brand:** Monogram floral ikonis 'BK' yang memadukan garis serif anggun dan sulur mawar merona.
- **Prinsip Utama:** Visual produk yang kaya estetika (flowerbox, mirror sign, papan akrilik kaligrafi, easel kayu berhias daun eucalyptus), tipografi elegan dengan keterbacaan tajam, dan alur sewa mandiri yang sangat intuitif.

## Colors

Palet warna diturunkan langsung dari logo resmi Berkah Kreatif (Velvet Wine / Deep Crimson Rose) berpadu dengan aksen champagne blush dan netral hangat bertekstur lembut:

- **Primary (Velvet Rose Crimson #881337):** Identitas mawar klasik dari monogram logo Berkah Kreatif, memberikan nuansa premium dan berkelas.
- **Secondary (Dark Slate Rose #4C0519):** Kontras penegas untuk teks headline dan status formal.
- **Accent (Rose Gold Blush #BE123C / Amber Champagne #D97706):** Elemen pemanis untuk badge ketersediaan produk, hitung mundur timer sewa, dan tombol aksi sekunder.
- **Neutral Surface (#FFFDF9 / #FAF5F0):** Warna dasar krem sutra lembut (soft linen) yang memberi nafas hangat dan estetik dibandingkan putih klinis dingin.

```yaml
colors:
  accent: "#BE123C"
  success: "#15803D"
  warning: "#B45309"
  error: "#991B1B"
  status-success-bg: "#DCFCE7"
  status-warning-bg: "#FEF3C7"
  status-accent-bg: "#FFE4E6"
  status-error-bg: "#FEE2E2"
  item-bg-warm: "#FAF5F0"
  item-bg-cool: "#FDF2F4"
  item-bg-soft: "#F5EBE6"
  light:
    primary: "#881337"
    on-primary: "#FFFFFF"
    primary-container: "#FFE4E6"
    on-primary-container: "#4C0519"
    secondary: "#4C0519"
    on-secondary: "#FFFFFF"
    secondary-container: "#FDF2F4"
    on-secondary-container: "#4C0519"
    surface: "#FFFDF9"
    on-surface: "#261318"
    surface-container: "#FAF5F0"
    on-surface-container: "#381E24"
    surface-container-high: "#F2E8E1"
    on-surface-container-high: "#261318"
    outline: "#6E5459"
    outline-variant: "#E7DBD5"
  dark:
    primary: "#FDA4AF"
    on-primary: "#4C0519"
    primary-container: "#881337"
    on-primary-container: "#FFE4E6"
    secondary: "#FECDD3"
    on-secondary: "#4C0519"
    secondary-container: "#33121A"
    on-secondary-container: "#FFE4E6"
    surface: "#1C1014"
    on-surface: "#FAF5F0"
    surface-container: "#2B1920"
    on-surface-container: "#F2E8E1"
    surface-container-high: "#3D242D"
    on-surface-container-high: "#FFFDF9"
    outline: "#A38B90"
    outline-variant: "#3D242D"
```

## Typography

Kombinasi tipografi dirancang untuk menghadirkan atmosfer boutique kreatif yang profesional:
- **Headline & Display (Playfair Display):** Serif anggun bertaraf editorial dan romantis, sangat serasi dengan monogram bunga dan papan wisuda estetik.
- **Body & Controls (Plus Jakarta Sans):** Sans-serif modern berkarakter ramah dengan legibilitas mobile luar biasa untuk form, badge, dan spesifikasi produk.
- **Monospace (Red Hat Mono):** Untuk kode booking, countdown timer 30 menit, dan perhitungan denda (jam × Rp25.000).

```yaml
typography:
  display-lg:
    fontFamily: "Playfair Display"
    fontSize: 40px
    fontWeight: 700
    lineHeight: 48px
    letterSpacing: -0.5px
  display-lg-mobile:
    fontFamily: "Playfair Display"
    fontSize: 32px
    fontWeight: 700
    lineHeight: 40px
    letterSpacing: -0.25px
  headline-lg:
    fontFamily: "Playfair Display"
    fontSize: 32px
    fontWeight: 700
    lineHeight: 40px
  headline-lg-mobile:
    fontFamily: "Playfair Display"
    fontSize: 26px
    fontWeight: 700
    lineHeight: 34px
  headline-md:
    fontFamily: "Playfair Display"
    fontSize: 24px
    fontWeight: 600
    lineHeight: 32px
  headline-sm:
    fontFamily: "Playfair Display"
    fontSize: 18px
    fontWeight: 600
    lineHeight: 26px
  body-lg:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 18px
    fontWeight: 400
    lineHeight: 28px
  body-md:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 16px
    fontWeight: 400
    lineHeight: 24px
  body-sm:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 20px
  label-lg:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 15px
    fontWeight: 600
    lineHeight: 20px
  label-md:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 13px
    fontWeight: 600
    lineHeight: 18px
  label-sm:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 12px
    fontWeight: 500
    lineHeight: 16px
  mono-md:
    fontFamily: "Red Hat Mono"
    fontSize: 15px
    fontWeight: 500
    lineHeight: 22px
  mono-lg:
    fontFamily: "Red Hat Mono"
    fontSize: 20px
    fontWeight: 600
    lineHeight: 28px
```

## Layout & Spacing

Menggunakan fluid mobile container (390px) dan desktop admin panel (1280px). Spacing scale 8-point berpadu dengan layout kartu bernafas (generous padding) agar visual dekorasi buket dan akrilik tampil menonjol dan tidak sesak.

```yaml
spacing:
  gutter-xs: 4px
  gutter-sm: 8px
  gutter-md: 16px
  gutter-lg: 24px
  gutter-xl: 32px
  section: 48px
  page-margin: 16px
```

## Elevation & Depth

Menghadirkan kesan estetik "hidup" tanpa dekorasi berlebihan:
- **Soft Rose Ambient Shadows:** `box-shadow: 0 10px 25px -5px rgba(136, 19, 55, 0.08)` memberi kesan kartu melayang anggun di atas permukaan linen hangat.
- **Glassmorphic Glow:** Latar belakang transparan lembut dengan border mawar tipis `border-primary/20` untuk hero banner dan header navigasi.
- **Borders:** Border halus `border-outline-variant` yang teratur.

## Shapes

Lengkungan lembut yang harmonis dengan kurva monogram logo:

```yaml
rounded:
  sm: 6px
  DEFAULT: 12px
  md: 16px
  lg: 20px
  xl: 28px
  full: 9999px
```

## Motion & Micro-interaction

Interaksi mulus dan cepat:

```yaml
motion:
  state-change:
    duration: 150ms
    delay: 0ms
    timingFunction: ease-out
  surface-enter:
    duration: 250ms
    delay: 0ms
    timingFunction: cubic-bezier(0.16, 1, 0.3, 1)
  surface-exit:
    duration: 150ms
    delay: 0ms
    timingFunction: cubic-bezier(0.7, 0, 0.84, 0)
```

## Voice & Content Strategy

- **Tone of Voice:** Anggun, hangat, menyemangati momen wisuda dan perayaan, jelas dan ramah UMKM.
- **Label Aksi:** *"Kunci Unit Cantik Ini"*, *"Verifikasi via WhatsApp"*, *"Bayar Instan QRIS"*, *"Cek Status Live"*.

## Components

- `components/button.html`
- `components/card.html`
- `components/input-field.html`
- `components/badge.html`
