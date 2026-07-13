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

## F11 — Verifikasi email registrasi (2026-07-13) — TERPASANG, KEY BELUM DIISI

Peneliti daftar → dikirim link verifikasi (`Illuminate\Auth\Notifications\VerifyEmail` via event `Registered`) → sebelum klik, semua route ke-gate middleware `verified` (redirect ke `/email/verify`, halaman ada tombol kirim ulang). User demo dari `UserSeeder` sudah `email_verified_at => now()` jadi tidak ke-gate. Provider: **Resend** (`resend/resend-laravel` v1.4), dipilih user — gratis 3rb email/bulan, setup lebih gampang & deliverability lebih baik dari Gmail SMTP (limit 500/hari, rawan block).

File: `app/Models/User.php` (implements `MustVerifyEmail`), `app/Livewire/Auth/Register.php` (fire `Registered` event), `app/Livewire/Auth/VerifyEmailNotice.php` + view, `app/Http/Controllers/Auth/VerifyEmailController.php`, `routes/web.php` (group `auth`+`verified` baru).

**Status sekarang:** `MAIL_MAILER=log` di `.env` — email verifikasi tercatat ke `storage/logs/laravel.log`, BUKAN terkirim asli. Test (`EmailVerificationTest`, 4 test) pakai `Notification::fake()`/signed-URL langsung, gak butuh key.

**Yang user harus kerjakan sendiri sebelum email beneran jalan (gak bisa diotomasi dari sini):**
1. Daftar akun gratis di resend.com.
2. Verifikasi domain pengirim di dashboard Resend (tambah DNS record SPF/DKIM ke domain sendiri). Tanpa domain sendiri, Resend kasih domain sandbox tapi cuma bisa kirim ke email yang didaftarkan sendiri — untuk produksi tetap butuh domain sendiri.
3. Generate API key di dashboard Resend.
4. Isi `.env`: `RESEND_KEY=re_xxxxx` dan ganti `MAIL_MAILER=log` → `MAIL_MAILER=resend`. Set `MAIL_FROM_ADDRESS` ke alamat di domain terverifikasi (mis. `noreply@domainkamu.com`).

## Catatan berjalan

- `app/Helpers/ResponseFormatter.php` sisa app lama, `namespace app\Helpers` huruf kecil → langgar PSR-4. Perbaiki atau hapus saat menyentuh file ini.
- prd §8 mengasumsikan schema `eproposal` / `survey` / `public`; di `eprotocol` baru ada `public`. Putuskan saat F3.
- Titik masuk Direksi, Rekam Medis, dan Auditor belum ada di alur 4 tahap (prd §2 catatan). Ditangani paling awal di F4 (role) dan F10.
