# Setup Penyimpanan File Dokumen (disk `dokumen`)

> Sejak 2026-07-16 file upload proposal TIDAK lagi disimpan di dalam folder aplikasi
> (`storage/app/public`), melainkan di folder khusus di luar aplikasi yang ditentukan
> lewat env **`DOCUMENTS_PATH`** (disk Laravel bernama `dokumen`, `config/filesystems.php`).

## Kenapa

- File terpisah dari kode: deploy ulang / ganti folder app tidak menyentuh dokumen.
- Backup dokumen bisa dijadwalkan terpisah dari backup aplikasi.
- Disk privat (tanpa `url`, `serve => false`) — file **tidak pernah** bisa diakses
  langsung lewat web; satu-satunya pintu adalah `DocumentDownloadController` yang
  memeriksa otorisasi (pemilik/petugas, gate survey izin final, kerahasiaan
  tanggapan reviewer).

Konteks keputusan: awalnya dipertimbangkan server terpisah (MinIO/SFTP) dan S3 cloud
gratis (R2/B2) — ditolak; data penelitian RS sebaiknya tidak keluar jaringan, dan server
terpisah menambah beban operasional. Kalau kelak mau pindah ke MinIO/S3: **cukup ganti
definisi disk `dokumen`** di `config/filesystems.php` ke driver `s3` — kode aplikasi
dan isi DB tidak berubah (kolom `path` berisi key relatif).

## A. Local dev (Windows/Laragon)

1. Buat folder tujuan, mis. `D:\eproposal-files` — atau lewati; tanpa `DOCUMENTS_PATH`
   fallback-nya `storage/app/dokumen` (tetap jalan, tapi di dalam app).
2. `.env`:
   ```
   DOCUMENTS_PATH=D:\eproposal-files
   ```
3. Bersihkan cache config (wajib tiap ubah `.env`):
   ```bash
   PHP='/c/laragon/bin/php/php-8.4.12-nts-Win32-vs17-x64/php.exe'
   "$PHP" artisan config:clear
   ```
4. Jalankan app seperti biasa. Subfolder `proposal/{uuid}/{jenis}/` dibuat otomatis
   saat upload pertama.

## B. Deploy ke server produksi

1. Tentukan folder di drive berkapasitas cukup, mis. `D:\eproposal-files` —
   **di luar** folder aplikasi & web root.
2. Pastikan user yang menjalankan PHP punya hak tulis ke folder itu
   (Laragon/`artisan serve`: user login, biasanya aman; IIS/Apache sebagai service:
   cek ACL folder untuk identity service-nya).
3. `.env` produksi: `DOCUMENTS_PATH=D:\eproposal-files`, lalu `artisan config:clear`
   (atau `config:cache` ulang bila produksi memakai cache config).
4. **Migrasi file lama** (sekali saja, bila server itu pernah menyimpan file di lokasi lama):
   ```
   robocopy storage\app\public\proposal D:\eproposal-files\proposal /E
   ```
   Kolom `path` di DB berisi path relatif yang sama — tidak ada langkah DB.
5. Masukkan `D:\eproposal-files` ke rutinitas backup server.
6. Uji: upload 1 dokumen → file muncul di `D:\eproposal-files\proposal\...` → unduh dari UI.

## Batas ukuran upload

Dua lapis, keduanya harus memadai:

| Lapis | Nilai | Lokasi |
|---|---|---|
| Temp upload Livewire | 25 MB | `config/livewire.php` → `temporary_file_upload.rules` |
| Validasi per jenis dokumen | pdf 10 MB · bukti bayar 5 MB · raw data 20 MB | `app/Enums/DocumentType.php` → `aturanValidasi()` |

Sebelumnya limit Livewire masih default 12 MB < raw data 20 MB — file 13–20 MB tertolak
sebelum validasi jenis dokumen sempat jalan (sudah diperbaiki 2026-07-16).
Perhatikan juga `upload_max_filesize` & `post_max_size` di `php.ini` produksi ≥ 25 MB.
