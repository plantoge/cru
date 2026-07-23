# Setup Email (Resend) — Panduan dari Nol

> Dokumen ini untuk kamu yang **belum tahu apa-apa** soal pengiriman email di aplikasi ini.
> Baca urut dari atas. Tiap istilah dijelaskan saat pertama muncul.

---

## 1. Apa itu Resend & kenapa dipakai

**Resend** adalah layanan pihak ketiga untuk **mengirim email dari aplikasi** (email transaksional: link verifikasi, reset password, notifikasi alur proposal). Aplikasi tidak mengirim email sendiri — ia menitipkan ke Resend, Resend yang mengantar ke inbox tujuan.

**Kenapa tidak kirim langsung / pakai Gmail?**
- Server aplikasi kirim email sendiri = gampang masuk folder spam / diblok, tidak ada bukti terkirim.
- Gmail SMTP dibatasi ~500 email/hari dan rawan diblokir untuk pemakaian aplikasi.
- Resend: gratis **3.000 email/bulan**, deliverability bagus, setup relatif gampang. Dipilih untuk aplikasi ini.

**Analogi:** Resend itu seperti kantor pos. Aplikasi menulis surat, Resend yang mengecap & mengantar. Tapi kantor pos ini menolak mengantar surat "atas nama" domain yang belum kamu buktikan milikmu (biar orang tak bisa ngaku-ngaku kirim dari `@bankmana.com`). Pembuktian itu = **verifikasi domain** (bagian §4).

---

## 2. Status komponen di aplikasi (sudah terpasang)

| Komponen | Nilai | Lokasi |
|---|---|---|
| Paket | `resend/resend-laravel` `^1.4` | `composer.json` |
| Mailer aktif | `resend` | `.env` → `MAIL_MAILER=resend` |
| API key | `RESEND_API_KEY` | `.env` (rahasia — jangan commit / sebar) |
| Alamat pengirim | `simrs@suliantisarosohospital.com` | `.env` → `MAIL_FROM_ADDRESS` |
| Toggle verifikasi email | **`false`** (mati) | `.env` → `EMAIL_VERIFICATION_REQUIRED` |

> **PENTING nama env:** paket membaca **`RESEND_API_KEY`**, bukan `RESEND_KEY`. Salah nama = email tak terkirim tanpa error jelas. Cek `config/services.php` → `'resend' => ['key' => env('RESEND_API_KEY')]`.

---

## 3. Kenapa fitur verifikasi email SEKARANG dimatikan

Toggle `EMAIL_VERIFICATION_REQUIRED=false`. Sebabnya: **domain `suliantisarosohospital.com` belum berstatus "Verified" di Resend.** Selama belum Verified, Resend menolak mengirim → kalau toggle dinyalakan, proses daftar akun ikut gagal (karena gagal kirim email verifikasi).

Perilaku dua posisi toggle:

| Toggle | Yang terjadi saat user daftar |
|---|---|
| **`false`** (sekarang) | Akun langsung aktif (`email_verified_at` terisi otomatis). **Tidak ada percobaan kirim email sama sekali** — aman selama domain belum Verified. |
| **`true`** | User dapat email berisi link verifikasi. Sebelum klik, akses ke-gate (diarahkan ke halaman "verifikasi email dulu"). Nyalakan HANYA setelah domain Verified. |

Jadi urutan kerjanya: **verifikasi domain dulu (§4) → baru nyalakan toggle (§5).**

---

## 4. Verifikasi domain di Resend (langkah inti)

Tujuan: buat status domain di Resend berubah dari **Pending** → **Verified**. Caranya: menaruh 3 record DNS yang Resend minta ke pengaturan DNS domain (di sini providernya **Hostinger**).

### 4.1 Ambil record dari Resend
Login resend.com → menu **Domains** → klik `suliantisarosohospital.com`. Di bagian **DNS Records** ada 3 record. Isi (content) bisa berbeda tampilannya — **selalu pakai yang tampil di Resend**, struktur seperti ini:

| # | Type | Name | Content | TTL / Priority |
|---|---|---|---|---|
| 1 DKIM | TXT | `resend._domainkey` | `p=MIGf...` (kunci publik panjang — salin UTUH) | TTL 3600 |
| 2 SPF | MX | `send` | `feedback-smtp.us-east-1.amazonses.com` | TTL 3600, **Priority 10** |
| 3 SPF | TXT | `send` | `v=spf1 include:amazonses.com ~all` | TTL 3600 |

**Arti singkat (tak wajib paham):** DKIM = tanda tangan digital email; SPF = daftar server yang boleh kirim atas nama domain. Dua-duanya bukti ke dunia bahwa email dari domainmu itu sah, bukan spam/palsu.

### 4.2 Masukkan ke Hostinger
hPanel Hostinger → **Domains** → pilih `suliantisarosohospital.com` → **DNS / Nameservers** → **DNS Zone** → **Add Record**. Tambah 3 record di atas satu per satu.

### 4.3 Jebakan yang sering bikin gagal (BACA)
1. **Name isi bagian depan saja** — `resend._domainkey` dan `send`. JANGAN tulis full `send.suliantisarosohospital.com` — Hostinger otomatis menambahkan domain, kalau ditulis full jadi dobel (`send.suliantisarosohospital.com.suliantisarosohospital.com`) → salah.
2. **`_domainkey` memang pakai underscore & literal** — bukan diganti nama domain. Format baku DKIM: `{selector}._domainkey`. Di sini selector = `resend`. Jadi persis `resend._domainkey`.
3. **DKIM `p=` harus UTUH** — nilainya sangat panjang dan sering terpotong `[...]` di tampilan. Klik record DKIM di Resend, buka penuh, salin semua tanpa spasi/enter terselip. Ini penyebab gagal-verify nomor satu.
4. **TTL** — Hostinger tak punya opsi "Auto". Pilih **3600** (atau 14400), aman.
5. **MX butuh Priority** — isi **10**.
6. **Cek bentrok SPF** — record SPF di sini ada di subdomain `send`, jadi tidak bertabrakan dengan SPF di root domain (kalau ada). Aman.

### 4.4 Verifikasi & tunggu
Setelah 3 record disimpan → balik ke Resend → klik tombol **Verify / refresh** di halaman domain. Propagasi DNS Hostinger biasanya **< 1 jam**, kadang beberapa jam. Status akan berubah Pending → **Verified** begitu ketiga record terdeteksi.

> **Enable Receiving** (toggle terima email masuk di Resend) — **biarkan OFF**. Kita hanya perlu KIRIM, tidak menerima.
> **DMARC** — Resend menganjurkan tapi **tidak wajib** untuk status Verified. Boleh dilewati dulu.

---

## 5. Menyalakan verifikasi email (setelah domain Verified)

Lakukan HANYA jika §4 sudah menunjukkan **Verified**:

1. Pastikan `.env`:
   ```env
   MAIL_FROM_ADDRESS="simrs@suliantisarosohospital.com"   # alamat apa pun di domain terverifikasi
   EMAIL_VERIFICATION_REQUIRED=true
   ```
2. Bersihkan cache config (config custom sensitif terhadap cache):
   ```bash
   php artisan config:clear
   ```
3. Coba daftar akun baru → harus menerima email link verifikasi.

Untuk mematikan lagi kapan pun: `EMAIL_VERIFICATION_REQUIRED=false` lalu `php artisan config:clear`.

---

## 6. Cara cek email benar-benar terkirim

- **Resend → menu Logs / Emails:** setiap email yang dikirim tercatat di sini beserta status (delivered / bounced / dsb). Titik pertama untuk cek kalau email "tidak sampai".
- **User demo dari seeder** sudah `email_verified_at` terisi → tidak ke-gate walau toggle ON. Untuk uji verifikasi, daftar akun baru.

---

## 7. Troubleshooting

| Gejala | Kemungkinan sebab | Tindakan |
|---|---|---|
| Domain terus **Pending** berhari-hari | Record belum masuk DNS / salah tulis | Ulang §4, cek Name tidak dobel domain, DKIM `p=` utuh |
| Daftar akun **gagal/error** saat toggle ON | Domain belum Verified, kirim ditolak | Matikan toggle (`false`) sampai domain Verified |
| Email tak sampai tapi tak ada error | `MAIL_MAILER` bukan `resend`, atau `RESEND_API_KEY` salah/kosong | Cek `.env`; ingat namanya `RESEND_API_KEY` bukan `RESEND_KEY`; `config:clear` |
| Perubahan `.env` tak berefek | Config ter-cache | `php artisan config:clear` |
| Email masuk **spam** | DMARC belum diset / reputasi awal | Tambah DMARC (opsional), kirim rutin bangun reputasi |

---

## 8. Ringkas alur (TL;DR)

```
1. Tambah 3 record DNS (DKIM TXT, SPF MX, SPF TXT) di Hostinger DNS Zone
2. Resend → Verify → tunggu status Verified (< 1 jam biasanya)
3. .env: EMAIL_VERIFICATION_REQUIRED=true
4. php artisan config:clear
5. Daftar akun baru → cek email link verifikasi masuk
```

**File terkait di kode:** `config/eproposal.php` (toggle), `config/services.php` (`RESEND_API_KEY`), `config/mail.php` (mailer resend), `app/Livewire/Auth/Register.php` (kirim event verifikasi / aktif langsung), `app/Http/Middleware/EnsureEmailIsVerifiedIfRequired.php` (gate). Detail keputusan desain: lihat F11 di `docs/rebuild-progress.md`.
