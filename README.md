# Spectra Watermarking

Aplikasi web untuk menyisipkan dan menguji **invisible blind watermark** pada citra digital. Proyek ini menggabungkan antarmuka Laravel dengan mesin pemrosesan citra Python.

## Teknologi

| Kompponen | Teknologi |
|-----------|-----------|
| Backend web | PHP 8.3+, Laravel 13 |
| Mesin pemrosesan | Python 3.10+ (OpenCV, NumPy, SciPy, scikit-image) |
| Antarmuka | Blade, Vite, Tailwind CSS |
| Database | MySQL |
| Python Bridge | Subprocess JSON via stdin/stdout |

## Persyaratan Sistem

Pastikan perangkat sudah memiliki:

- **Python 3.10+** - untuk mesin watermarking
- **PHP 8.3+** - untuk aplikasi Laravel
- **Composer** - untuk dependency PHP
- **Node.js & npm** - untuk build assets
- **MySQL 5.7+** - untuk database
- **Git** - untuk version control

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/username/repo-name.git
cd repo-name
```

### 2. Siapkan Mesin Python

**Windows (PowerShell):**
```powershell
py -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
python -m pip install -r python-engine\requirements.txt
```

**Linux/Mac:**
```bash
python3 -m venv .venv
source .venv/bin/activate
python -m pip install --upgrade pip
python -m pip install -r python-engine/requirements.txt
```

### 3. Siapkan Database MySQL

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS watermarking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Siapkan Aplikasi Laravel

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

### 5. Konfigurasi Environment

Edit file `.env` sesuai konfigurasi MySQL Anda:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=watermarking
DB_USERNAME=root
DB_PASSWORD=password_anda

# Path Python sesuaikan dengan lokasi Anda
WATERMARK_PYTHON=C:\laragon\www\repo-name\.venv\Scripts\python.exe
WATERMARK_BRIDGE=C:\laragon\www\repo-name\python-engine\web_bridge.py
```

**Catatan Windows:** Gunakan path absolut untuk `WATERMARK_PYTHON` dan `WATERMARK_BRIDGE`.

**Catatan Linux/Mac:** Gunakan path relatif atau absolut:
```dotenv
WATERMARK_PYTHON=.venv/bin/python
WATERMARK_BRIDGE=python-engine/web_bridge.py
```

## Menjalankan Aplikasi

```bash
php artisan serve
```

Buka `http://127.0.0.1:8000` di browser.

## Penggunaan

1. Buka halaman **Embedding** (`/embedding`)
2. Upload citra asli dan citra watermark
3. Masukkan secret key dan nilai Alpha (> 0)
4. Klik "Proses Embedding"
5. Lanjut ke **Attack** untuk simulasi serangan
6. Lanjut ke **Extraction** untuk ekstraksi blind
7. Lihat metrik di **Evaluation**

## Alur Aplikasi

```
Embedding → Attack → Extraction → Evaluation
```

| Halaman | Fungsi |
|---------|--------|
| `/` | Beranda dengan penjelasan algoritma |
| `/embedding` | Upload citra + watermark, set parameter |
| `/attack` | Simulasi serangan (JPEG, resize, crop) |
| `/extraction` | Ekstraksi watermark blind dengan secret key |
| `/evaluation` | Tabel metrik PSNR, SSIM, NC, BER |

## Struktur Proyek

```
.
├── app/
│   ├── Http/Controllers/        # Embedding, Attack, Extraction, Evaluation
│   └── Services/                # PythonWatermarkEngine, WatermarkStorageService
├── config/
│   └── watermark.php            # Konfigurasi Python binary & bridge
├── database/
│   ├── migrations/              # Tabel: users, cache, jobs
│   └── factories/
├── python-engine/
│   ├── app/
│   │   ├── dct.py               # Transformasi DCT/IDCT
│   │   ├── key.py               # Secret key → urutan blok
│   │   ├── watermark.py         # Embedding & extraction
│   │   ├── metrics.py           # PSNR, SSIM, NC, BER
│   │   └── pipeline.py          # Orkestrasi pipeline
│   ├── tests/                   # Unit tests Python
│   ├── web_bridge.py            # JSON adapter untuk Laravel
│   ├── attack_simulation.py     # Simulasi serangan
│   └── requirements.txt
├── public/
│   └── assets/                  # CSS & JS
├── resources/
│   ├── views/                   # Blade templates
│   ├── css/
│   └── js/
├── routes/
│   └── web.php
├── storage/
│   └── app/private/watermark-runs/
├── tests/                       # Feature & Unit tests PHP
├── .env.example
├── composer.json
├── package.json
└── vite.config.js
```

## Menjalankan Pengujian

**Test Python:**
```bash
python -m unittest discover -s python-engine/tests -v
```

**Test Laravel:**
```bash
php artisan test
```

## Troubleshooting

### Error: "Python bridge tidak ditemukan"
- Cek `WATERMARK_BRIDGE` di `.env` pointing ke path yang benar
- Pastikan `python-engine/web_bridge.py` ada

### Error: "Python engine tidak dapat dijalankan"
- Cek `WATERMARK_PYTHON` di `.env`
- Pastikan virtual environment sudah aktif atau path benar

### Error: "Permission denied" di storage
```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Error: MySQL connection refused
- Pastikan MySQL server berjalan
- Cek `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` di `.env`

## Batasan

- Upload citra maksimum 10 MB per file
- Secret key untuk ekstraksi harus sama dengan saat embedding
- `centered_crop` mengasumsikan pemotongan simetris dari tengah
- Hasil ekstraksi bergantung pada kekuatan watermark (alpha) dan serangan yang diterima

## Lisensi

MIT License
