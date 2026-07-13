# Rebuild eProposal — Rencana & Progres

> **File ini adalah titik masuk untuk melanjutkan pekerjaan.**
> Sesi baru cukup baca: `docs/prd.md` (spesifikasi) → file ini (posisi terakhir) → lanjut dari fase pertama yang belum ✅.
> Setiap fase selesai = 1 commit. Kalau sesi mati di tengah, `git log` + checklist di bawah menunjukkan persis di mana berhenti.

## Konteks tetap

| Hal | Nilai |
|---|---|
| Lokasi build | `d:\app\eproposalnew` (greenfield) |
| Referensi read-only | `d:\app\eproposal` (app lama, Laravel 12 + Blade biasa) — **jangan diubah** |
| Spesifikasi | `docs/prd.md` — sumber kebenaran tunggal |
| Database | PostgreSQL 14.23 @ `172.16.202.207`, db `eprotocol` — kosong, mulai bersih |
| PHP | `C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe` (**tidak di PATH**) |
| Composer | `C:\laragon\bin\composer\composer.phar` (**tidak di PATH**) |

Jalankan artisan begini:
```bash
PHP='/c/laragon/bin/php/php-8.4.12-nts-Win32-vs17-x64/php.exe'
"$PHP" artisan migrate
```

## Keputusan (DIKUNCI user 2026-07-09 — semua default disetujui)

| # | Isu | Keputusan terpasang |
|---|---|---|
| D1 | `tahapan()` bentrok tabel vs kode §7b | Terminal → `null` (`ProposalStatus::tahapan()`) |
| D2 | `unit_sekarang` | Kolom tersimpan + index, di-sync `ProposalWorkflow::transition()` |
| D3 | Kosakata unit | Enum `Unit` = `penelitian\|kaji_etik\|reviewer` di semua tabel |
| D4 | Jalan mundur | `MenungguVerifikasiPembayaran→MenungguPembayaran`, `MenungguVerifikasiAkhir→PelaksanaanPenelitian`, `MenungguKelengkapanBerkasEtik→DitolakKajiEtik`, + status `Dibatalkan` (dari semua non-terminal) |
| D5 | Survey per proposal | `respon.proposal_id` + partial unique; gate unduh di `DocumentDownloadController` |
| D6 | Kode proposal | `RSPISS-YYYY-###`, kolom `tahun`+`nomor` `unique(tahun,nomor)` |

Celah kecil (tidak memblokir): `proposal_status_history` kena softDeletes + `updated_by`/`deleted_by` yang melemahkan integritas audit; `actor_id` duplikat `created_by`; `proposal_documents.uploaded_by` duplikat `created_by`; nama `jenis` dokumen meleset dari §7c (`raw_data` vs `raw_data_penelitian`, `proposal` vs `proposal_penelitian`).

## Fase

- [x] **F0 — Fondasi.** Skeleton Laravel 12.63, `.env` ke `eprotocol`, `APP_KEY`, git init.
- [x] **F1 — Paket & UI shell.** Livewire 3.8, Mary UI 2.9 (prefix `mary-` di `config/mary.php`), daisyUI 5, spatie/permission 8.3 (morph key uuid).
- [x] **F2 — Konvensi §8.0.** Macro `auditColumns` (`AppServiceProvider`), trait `app/Concerns/HasUuidAndAudit.php`, users → uuid + kolom prd.
- [x] **F3 — Domain inti.** Enum `ProposalStatus`/`DocumentType`/`Unit`; migration proposal + documents + status_history + reviews; `app/Services/ProposalWorkflow.php` (pintu tunggal transisi + generateKode). 25 tabel jalan di `eprotocol`.
- [x] **F4 — RBAC & menu dinamis.** `MenuObserver`→`MenuPermissionSync`; seeder 9 role, 12 menu, 48 permission, 9 user demo (`{role}@eproposal.test` / `password`).
- [x] **F5 — Auth + layout.** Login/register Livewire (tanpa 2FA), layout Mary, sidebar dinamis ter-filter permission, dashboard per role.
- [x] **F6 — Tahap 1 (CRU).** `Proposal\Create/Index/Show` + `Antrian\Cru`: revisi, presentasi, tolak, loloskan.
- [x] **F7 — Tahap 2 (KEPK + Reviewer).** Berkas etik 4 wajib, loop reviewer (ronde di `proposal_reviews`), ACC, KEPK lanjut/tolak.
- [x] **F8 — Tahap 3.** Bukti bayar + verifikasi/tolak (D4) + info rekening dari `informasi_kontak`.
- [x] **F9 — Tahap 4.** Draft izin, laporan+raw data, izin final, **survey gate** di `DocumentDownloadController` (uji: `SurveyGateTest`).
- [ ] **F10 — Pelengkap.** Laravel Reverb (notif realtime), export laporan Excel, object storage S3/MinIO (sekarang: disk lokal `public` via controller ber-otorisasi), reset password email.

**Verifikasi terakhir (2026-07-10):** `artisan test` 25 lulus / 51 assertion; `view:cache` bersih. Semua aksi UI lewat `ProposalWorkflow` — jangan set `proposal->status` langsung.

## Revisi alur (permintaan user 2026-07-10) — TERPASANG

1. **Tahap 2, KEPK perantara penuh:** peneliti submit berkas etik → status baru `Menunggu Penunjukan Reviewer` (bola KEPK) → KEPK tunjuk **≥1 reviewer** (tabel `proposal_reviewers`, model `ProposalReviewerAssignment`) → tanggapan reviewer (komentar + file `tanggapan_reviewer` + ACC/revisi) **masuk ke KEPK, bukan peneliti**; status proposal tetap sampai KEPK meneruskan revisi (`Perlu Revisi Reviewer`, unit kini `kaji_etik`) atau **semua reviewer ACC → otomatis `Disetujui Reviewer`** (guard `Proposal::semuaReviewerAcc()` di `kepkLanjut`). Revisi peneliti → `resetPenugasanReviewer()` (ronde baru semua reviewer). **Kerahasiaan:** komentar reviewer & file tanggapan tak terlihat/terunduh peneliti; nama reviewer di riwayat disamarkan jadi "Reviewer"; reviewer hanya bisa buka proposal yang ditugaskan padanya.
2. **Tahap 3, dua pembayaran:** `bukti_bayar` dipecah `bukti_bayar_cru` + `bukti_bayar_kepk` (keduanya wajib). Verifikasi tetap satu pintu CRU. **Payment gateway menyusul setelah alur dikonfirmasi benar.**
3. Docs disinkronkan: prd.md (§4, §7b enum+tabel, §7c, §8.5 baru `proposal_reviewers`) + 3 HTML + 3 PDF di-regenerate (perlu `--headless=new` di Edge).

## F11 — Verifikasi email registrasi (2026-07-13) — TERPASANG, dengan TOGGLE ON/OFF

Peneliti daftar → (kalau toggle ON) dikirim link verifikasi (`Illuminate\Auth\Notifications\VerifyEmail` via event `Registered`) → sebelum klik, route ke-gate (redirect ke `/email/verify`, halaman ada tombol kirim ulang). User demo dari `UserSeeder` sudah `email_verified_at => now()` jadi tidak ke-gate. Provider: **Resend** (`resend/resend-laravel` v1.4) — gratis 3rb email/bulan, setup lebih gampang & deliverability lebih baik dari Gmail SMTP (limit 500/hari, rawan block).

**Toggle** `EMAIL_VERIFICATION_REQUIRED` (`.env`, dibaca `config/eproposal.php`) — ditambah karena domain pengirim (`suliantisarosohospital.com`) belum terverifikasi di Resend, jadi email gagal terkirim saat registrasi (blocking, bikin proses daftar ikut gagal). Default **`false`**:
- **`false`** (skarang): daftar langsung `email_verified_at` terisi, **tidak ada percobaan kirim email sama sekali** — aman dipakai selama domain belum verified.
- **`true`**: alur wajib verifikasi penuh seperti biasa — nyalakan begitu domain sudah status "Verified" di resend.com/domains.

Mekanisme gating pakai middleware custom `verified.optional` (`app/Http/Middleware/EnsureEmailIsVerifiedIfRequired.php`, alias di `bootstrap/app.php`) — **bukan** middleware bawaan `verified` yang dipasang kondisional saat route register. Alasan: keputusan dicek saat request (baca `config()` tiap kali), bukan dibekukan saat route didaftarkan — jadi toggle langsung berlaku tanpa perlu `route:cache` ulang, dan gampang di-test per-skenario (`config(['eproposal.email_verification_required' => true])` di dalam method test).

File: `app/Models/User.php` (`MustVerifyEmail`), `app/Livewire/Auth/Register.php` (conditional: fire `Registered` event ATAU `markEmailAsVerified()` langsung), `app/Http/Middleware/EnsureEmailIsVerifiedIfRequired.php`, `config/eproposal.php`, `routes/web.php` (`verified.optional` bukan `verified`).

**PENTING — nama env var Resend:** package baca **`RESEND_API_KEY`**, BUKAN `RESEND_KEY` (salah tulis di commit awal, sudah diperbaiki). Cek `config/services.php` → `'resend' => ['key' => env('RESEND_API_KEY')]`.

**Kenapa domain verification wajib** (bukan bisa pakai `MAIL_FROM_ADDRESS=nama@gmail.com`): Resend (dan provider transactional sejenis: SendGrid/Mailgun/Postmark) nolak kirim atas nama domain yang gak dibuktikan kepemilikannya via DNS — anti-spoofing, kalau boleh sembarangan siapa saja bisa ngaku-ngaku kirim dari `@gmail.com`/`@bankmana.com` buat phishing.

**Langkah nyalain lagi pas siap** (toggle ON + domain verified):
1. Tambah domain (mis. `suliantisarosohospital.com`) di resend.com/domains → Resend kasih DNS record (SPF/DKIM/DMARC) → tambahkan ke DNS management domain → tunggu status "Verified".
2. `.env`: `MAIL_FROM_ADDRESS=noreply@suliantisarosohospital.com` (alamat apa pun di domain terverifikasi itu).
3. `.env`: `EMAIL_VERIFICATION_REQUIRED=true`.
4. `php artisan config:clear` (config custom kayak gini sensitif ke cache).

## F12 — Captcha login & registrasi (2026-07-13) — TERPASANG, self-hosted

**Percobaan pertama pakai Cloudflare Turnstile sempat dipasang & di-commit, lalu di-revert total** (dianggap "terlalu rumit" oleh user). Diganti **math captcha self-hosted** — soal hitung sederhana (mis. `7 + 3`), jawaban dicek di server, **nol dependency eksternal, nol panggilan API, nol ekstensi PHP tambahan** (gak butuh GD dkk).

Mekanisme: `app/Services/MathCaptcha.php` generate soal (dua angka 1-15, operator `+`/`-`), simpan jawaban benar di **session server-side saja** (gak pernah dikirim ke client — beda dari naive approach nyimpen expected value di public property Livewire, yang bakal bocor ke HTML page source lewat snapshot Livewire). Sekali pakai — session key dihapus begitu diverifikasi (replay ditolak, teruji `CaptchaTest::test_token_sekali_pakai_gagal_kalau_dipakai_ulang`).

File: `app/Services/MathCaptcha.php`, `app/Rules/ValidCaptcha.php` (ValidationRule, terima `$captchaId` lewat constructor saat rule dibentuk di komponen), `resources/views/components/captcha.blade.php` (widget: pertanyaan + input angka + tombol ganti soal, murni Blade/Livewire — gak ada JS/CDN sama sekali). Dipasang di `Login.php` & `Register.php`: property `captchaId`/`captchaQuestion`/`captchaAnswer`, `mount()` generate soal awal, `regenerateCaptcha()` dipanggil ulang tiap validasi gagal ATAU auth gagal (lewat try/catch `ValidationException` di `register()`, biar soal captcha lama gak bisa dipakai ulang).

Test: `tests/Feature/CaptchaTest.php` (8 test — service langsung + integrasi Login/Register) + `EmailVerificationTest.php` diupdate (helper `isiCaptchaBenar()` ambil jawaban dari session, gak fake/skip). Total 40 test lulus, gak ada lagi dependency `Http::fake()` buat captcha (beda dari Turnstile yang butuh fake network di base `TestCase.php` — sekarang dihapus lagi karena gak relevan).

## Catatan berjalan

- `app/Helpers/ResponseFormatter.php` sisa app lama, `namespace app\Helpers` huruf kecil → langgar PSR-4. Perbaiki atau hapus saat menyentuh file ini.
- prd §8 mengasumsikan schema `eproposal` / `survey` / `public`; di `eprotocol` baru ada `public`. Putuskan saat F3.
- Titik masuk Direksi, Rekam Medis, dan Auditor belum ada di alur 4 tahap (prd §2 catatan). Ditangani paling awal di F4 (role) dan F10.
