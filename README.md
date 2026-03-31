# 🎬 VideyView - Next-Gen Video Hosting & Monetization

VideyView adalah platform hosting video premium yang dirancang khusus untuk efisiensi distribusi konten, optimalisasi iklan (monetisasi), dan sistem manajemen anggota yang terintegrasi. Platform ini menggunakan arsitektur **Hybrid Cloud-Local** untuk memastikan pengunduhan video berkecepatan tinggi tanpa membebani server utama.

---

## 💡 Mental Model & Core Logic (Quick Refresh)

Jika Anda lupa cara kerja aplikasi ini di masa depan, ingatlah **3 Tahap Utama** ini:

1.  **Tahap 1: Pengumpulan (Extractor)**  
    Admin memasukkan URL video mentah (dari Videy/CDN lain) ke dalam **Bulk Extractor** di Dashboard. Database akan mencatatnya sebagai `pending`.
2.  **Tahap 2: Jembatan (Sync Manifest)**  
    Admin mengklik tombol **"Sync Manifest"** untuk mengunduh file `sync_list.json`. File ini adalah daftar tugas untuk PC lokal Anda.
3.  **Tahap 3: Eksekusi (Local Worker)**  
    Jalankan `run_sync.bat` di PC lokal Anda. Script Python (`sync.py`) akan membaca manifest, mengunduh file besar, mengekstrak thumbnail, dan **mengirim balik status sukses** ke server (Cloud/VPS).

**Komponen Kritis Lainnya:**
-   **ISP Bypass**: Menggunakan `CURLOPT_RESOLVE` untuk menembus blokir DNS lokal tanpa VPN.
-   **Security**: Player menggunakan **Proxy Relay** sehingga URL asli video tidak pernah muncul di browser user.
-   **Auth**: Tidak ada email reset password. Semua diarahkan manual ke **@Mandorbuah** di Telegram.

---

## ⚡ Fitur Utama

- **🚀 Bulk Video Extractor**: Import ribuan link video sekaligus dengan sistem "Zero-Click Automation".
- **💎 Tiered Access Control**: Pembatasan konten berdasarkan level akun (Guest, Free, Premium).
- **🛡️ Security Masking 2.0**: Penyembunyian URL asli (Videy/Streamtape) dari Network Tab via Video Proxy Relay.
- **💰 Ultra-Monetization**: Integrasi Adsterra Native Banners yang "Native-Grid" (menyatu dengan konten) dan Anti-Adblock Guard.
- **🤖 Telegram Auth Sync**: Semua permintaan reset password diarahkan ke admin via Telegram untuk keamanan maksimal.
- **⚙️ Standalone Sync Worker**: Proses pengunduhan dan mirroring video dilakukan di PC lokal/VPS terpisah.

---

## 👥 Panduan Pengguna (User Guide)

### 1. Registrasi & Membership
- **Guest (Tamu)**: Dapat mencari video namun akses menonton dibatasi (Player locked) dan kolom komentar terkunci.
- **Free Member**: Akses menonton penuh dengan tampilan iklan Native di dalam grid video.
- **Premium Member**: Pengalaman menonton **100% Bebas Iklan** dan akses prioritas ke grup Telegram.

### 2. Keamanan Akun
- **Lupa Password**: Klik link lupa password akan mengarahkan Anda langsung ke Chat Admin **@Mandorbuah** di Telegram untuk verifikasi manual.
- **Logout**: Tombol Sign Out tersedia di header (Desktop) dan menu drawer (Mobile).

---

## 👑 Panduan Admin (Admin Guide)

### 1. Dashboard & Extractor
- Akses menu **Video Management** untuk melihat status mirroring.
- Gunakan **Bulk Extractor** untuk memasukkan URL sumber. Sistem akan otomatis membuat database entry dengan status `pending`.

### 2. Manajemen Iklan (Monetization)
- Atur kode iklan Adsterra melalui Dashboard Admin.
- **Anti-Adblock**: Sistem secara dinamis memuat script iklan melalui proxy server-side untuk menghindari deteksi browser.

### 3. Jembatan Sinkronisasi (Zero-Click Automation)
- Klik tombol **"Sync Manifest"** di Dashboard untuk mengunduh `sync_list.json`.
- File ini digunakan oleh *Local Worker* untuk mengetahui video mana yang perlu diunduh.

---

## ⚙️ Panduan Local Worker (Local Sync)

*Engine* utama VideyView berada pada script Python yang berjalan di PC lokal untuk menangani unduhan massal.

### Setup Awal
1. Pastikan Anda memiliki **Python 3.10+**.
2. Instal library yang dibutuhkan:
   ```bash
   pip install requests
   ```

### Alur Kerja Sinkronisasi
1. **Unduh Manifest**: Ambil file `sync_list.json` terbaru dari Dashboard Admin VideyView.
2. **Jalankan Worker**:
   - Double-click file `run_sync.bat`.
   - Masukkan jumlah **threads** (disarankan 3-5 untuk kecepatan optimal).
   - Masukkan **Remote Sync API Key** (dapat ditemukan di `.env` file server sebagai `INTERNAL_SYNC_KEY`).
3. **Monitoring**: Worker akan otomatis mengunduh video, mengekstrak thumbnail menggunakan FFmpeg, dan mengirimkan status keberhasilan kembali ke server secara real-time.

---

## 🛠️ Persyaratan Sistem & Instalasi

### Requirements
- **Server**: PHP 8.2+, MySQL 8.0+, Node.js 18+.
- **Local PC (Worker)**: Python 3+, FFmpeg installed in system PATH.

### Instalasi Backend
1. Clone repository ke folder `htdocs` (jika menggunakan XAMPP).
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Konfigurasi `.env`:
   - Atur `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
   - Atur `INTERNAL_SYNC_KEY` untuk koneksi worker.
4. Jalankan migrasi:
   ```bash
   php artisan migrate
   ```

### Pengembangan Frontend
Jalankan compiler Vite untuk memantau perubahan UI:
```bash
npm run dev
```

---

## 📡 ISP Bypass & Proxy Strategy

Platform ini dilengkapi dengan **IspBypassClient** yang menggunakan strategi `CURLOPT_RESOLVE`.
- **Fungsi**: Memotong jalur DNS ISP lokal yang sering memblokir situs video hosting.
- **Mekanisme**: Hardcoding IP address server hosting (Streamtape/Doodstream) langsung ke dalam request HTTP untuk memastikan transfer data tetap stabil dan cepat tanpa VPN.

---

## 📝 Maintenance & Logs
- **Laravel Logs**: `storage/logs/laravel.log`
- **Worker Logs**: Akan muncul langsung di terminal saat `run_sync.bat` dijalankan.

---

**Developed by Antigravity AI for VideyView.**
