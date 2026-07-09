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

## Keputusan yang menunggu jawaban user

Ini memblokir Fase 3 (migration + enum). Jangan tulis migration `proposal` / `respon` sebelum ini dijawab. Latar belakang lengkap: temuan telaah `docs/prd.md`.

| # | Isu | Usulan default |
|---|---|---|
| D1 | `tahapan()` bentrok: tabel §7b beri `Ditolak`→1 & `Ditolak Kaji Etik`→2, kode beri `null` | Ikuti **kode** (`null`); status terminal tak punya tahap |
| D2 | `unit_sekarang` kolom tersimpan atau accessor turunan? | **Kolom tersimpan**, di-sync observer, di-index (prd §8.1 butuh untuk antrian) |
| D3 | Kosakata unit beda: `penelitian\|kaji_etik\|reviewer` (§8.3) vs `cru\|kepk\|reviewer` (§8.4) | Satukan jadi enum `Unit` = `penelitian\|kaji_etik\|reviewer` (ikut method `unit()`) |
| D4 | Tak ada jalan mundur dari `Menunggu Verifikasi Pembayaran`, `Menunggu Verifikasi Akhir`, `Menunggu Kelengkapan Berkas Etik`; tak ada status batal | Tambah transisi tolak/revisi + status `Dibatalkan` |
| D5 | `respon` tak punya `proposal_id` → survey proposal A membuka izin proposal B | **Tambah `proposal_id`** ke `respon` (bug nyata) |
| D6 | Kode `RSPISS###` tabrakan antar tahun (nomor increment per tahun, kode tanpa tahun) | Tambah kolom `tahun` + `unique(tahun, nomor)`; format kode memuat tahun |

Celah kecil (tidak memblokir): `proposal_status_history` kena softDeletes + `updated_by`/`deleted_by` yang melemahkan integritas audit; `actor_id` duplikat `created_by`; `proposal_documents.uploaded_by` duplikat `created_by`; nama `jenis` dokumen meleset dari §7c (`raw_data` vs `raw_data_penelitian`, `proposal` vs `proposal_penelitian`).

## Fase

- [x] **F0 — Fondasi.** Skeleton Laravel 12, `.env` ke `eprotocol`, `APP_KEY`, git init, file rencana ini.
- [ ] **F1 — Paket & UI shell.** Livewire 3, Tailwind + daisyUI + Mary UI, spatie/laravel-permission. Layout dasar + halaman kosong yang render.
- [ ] **F2 — Konvensi §8.0.** `Blueprint::macro('auditColumns')` di `AppServiceProvider`, trait `HasUuidAndAudit`, verifikasi `Str::uuid7()` jalan di PG 14.
- [ ] **F3 — Domain inti.** *(butuh D1–D6)* Enum `ProposalStatus`, `DocumentType`, `Unit`. Migration: `proposal`, `proposal_documents`, `proposal_status_history`, `proposal_reviews`. Model + observer `unit_sekarang`.
- [ ] **F4 — RBAC & menu dinamis.** Tabel `menus`, auto-generate permission `{slug}.read|create|update|delete`, seeder 9 role (prd §1), matriks role × menu, sidebar ter-filter permission.
- [ ] **F5 — Auth + layout.** Registrasi/login peneliti, layout panel Livewire, sidebar dinamis. **Tanpa 2FA.**
- [ ] **F6 — Tahap 1 (CRU).** Ajukan proposal, review berkas, minta revisi, jadwal presentasi, tolak, loloskan ke KEPK.
- [ ] **F7 — Tahap 2 (KEPK + Reviewer).** Lengkapi berkas etik, arahkan ke Reviewer, loop komentar/revisi (>1×), ACC, lanjut/tolak etik.
- [ ] **F8 — Tahap 3.** Upload bukti bayar + verifikasi CRU.
- [ ] **F9 — Tahap 4.** Draft izin, upload laporan + raw data, izin final, **gate survey kepuasan sebelum unduh**.
- [ ] **F10 — Pelengkap.** Laravel Reverb (notif realtime), laporan, audit log, object storage (S3/MinIO — belum final).

## Catatan berjalan

- `app/Helpers/ResponseFormatter.php` sisa app lama, `namespace app\Helpers` huruf kecil → langgar PSR-4. Perbaiki atau hapus saat menyentuh file ini.
- prd §8 mengasumsikan schema `eproposal` / `survey` / `public`; di `eprotocol` baru ada `public`. Putuskan saat F3.
- Titik masuk Direksi, Rekam Medis, dan Auditor belum ada di alur 4 tahap (prd §2 catatan). Ditangani paling awal di F4 (role) dan F10.
