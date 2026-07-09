# PRD — Aplikasi eProposal RSPI

> **Product Requirements Document (Dokumentasi Sistem Berjalan)**
> Dokumen ini menjelaskan alur dan fitur aplikasi **eProposal** sebagaimana yang telah diimplementasikan saat ini (di-*reverse-engineer* dari folder `app/Http/Controllers` dan `routes/web.php`). Bersifat deskriptif atas sistem yang sudah ada.
>
> Struktur database & alur target untuk rebuild ada di §7b–§8.

---

## 0. Scope

- Manajemen Proposal
- Workflow Persetujuan (multi-unit)
- Ethical Review (KEPK)
- Upload Dokumen
- Monitoring Penelitian
- Dataset Request (permintaan data ke Rekam Medis)
- Audit Log
- Dashboard
- Laporan

---

## 1. Ringkasan Produk

**eProposal** adalah aplikasi web untuk **pengelolaan pengajuan dan review proposal penelitian** di lingkungan rumah sakit (RSPI). Aplikasi memfasilitasi peneliti eksternal/internal mengajukan proposal penelitian secara daring, lalu petugas (operator) memverifikasinya secara bertahap sampai izin penelitian terbit dan penelitian selesai. Di akhir siklus, peneliti mengisi **survey kepuasan** layanan.

- **Tujuan:** mendigitalkan alur pengajuan proposal penelitian — dari unggah dokumen, verifikasi bertahap, penerbitan izin, pelaporan hasil, hingga survey kepuasan — dalam satu sistem yang terlacak dan ternotifikasi.
- **Kode proposal:** dibuat otomatis dengan format `RSPISS###` (increment per tahun, mis. `RSPISS001`).
- **Platform:** aplikasi web berbasis **Laravel (PHP)**, dengan render halaman server-side (Blade) dan sejumlah aksi berbasis AJAX/JSON.

Saat ini proses tersebut di banyak rumah sakit masih dilakukan secara manual menggunakan dokumen fisik, email, atau spreadsheet sehingga menyebabkan:

Sulit memonitor status penelitian.
Dokumen tersebar di berbagai tempat.
Sulit mengetahui siapa yang sedang mengakses data pasien.
Tidak ada audit trail.
Sulit membuat laporan penelitian tahunan.

Aplikasi CRU bertujuan mendigitalisasi seluruh siklus penelitian agar lebih cepat, transparan, terdokumentasi, dan sesuai regulasi.

### Pengguna Sasaran
| Aktor | Peran singkat |
|---|---|
| **Superadmin** | Administrasi sistem penuh: user, role & permission, master data, konfigurasi. |
| **Direksi** | Persetujuan (approval) penelitian tingkat pimpinan; memantau dashboard & laporan. |
| **CRU** (Clinical Research Unit) | Koordinator penelitian: verifikasi berkas administratif, monitoring seluruh penelitian, penerbitan izin. |
| **Administrator** | Pengelola operasional aplikasi: antrian, data pendukung, informasi kontak. |
| **Peneliti** | Mengajukan proposal, mengunggah dokumen bertahap, revisi, mengisi survey, meminta dataset. |
| **Reviewer** | Telaah ilmiah/metodologi proposal (scientific review) & memberi rekomendasi. |
| **KEPK** (Komite Etik Penelitian Kesehatan) | Telaah etik (ethical review): verifikasi berkas etik, minta revisi, setujui/tolak. |
| **Rekam Medis** | Penyedia data penelitian: memproses permintaan dataset sesuai izin. |
| **Auditor** | Audit penelitian: akses audit trail & pemeriksaan kepatuhan (read-only). |


## 2. Aktor & Hak Akses

Rincian tanggung jawab & akses tiap aktor dalam sistem:

### Superadmin
Kelola user, role & permission (RBAC), master data (aspek/pertanyaan/skala survey, informasi kontak) & konfigurasi sistem. Tidak terlibat langsung dalam alur persetujuan proposal.

### Direksi
Memberi **persetujuan akhir** penelitian (level pimpinan). Akses **dashboard eksekutif** & **laporan** agregat (jumlah, status, tren penelitian). Umumnya read-only + aksi approve.

### CRU (Clinical Research Unit)
Unit pemilik **Tahap 1, 3, 4** alur proposal: verifikasi berkas awal, minta revisi/tolak, verifikasi pembayaran, terbitkan surat izin (draft & final), jadwalkan presentasi. Melakukan **monitoring** seluruh penelitian berjalan.

### Administrator
Pengelola **operasional aplikasi**: memantau antrian & status, mengelola data pendukung (informasi kontak/biaya), membantu troubleshooting pengguna. *(Catatan: bila secara organisasi Administrator = CRU, dua peran ini dapat digabung.)*

### Peneliti (visitor)
Registrasi & login (email + password). Mengajukan proposal & mengunggah dokumen bertahap (proposal, berkas etik, bukti bayar, laporan + raw data), melakukan revisi, memantau progres & riwayat, mengisi survey kepuasan, serta mengajukan **permintaan dataset** (bila fitur aktif).

### Reviewer
Melakukan **telaah ilmiah/metodologi** proposal (scientific review). Memberi catatan & rekomendasi (layak / revisi / tidak layak) dari sisi keilmuan.

### KEPK (Komite Etik Penelitian Kesehatan)
Unit pemilik **Tahap 2**: telaah etik berkas kaji etik. Dapat minta revisi etik (loop dengan peneliti), menyetujui, atau **menolak** secara etik (`Ditolak Kaji Etik`).

### Rekam Medis
**Penyedia data** penelitian: menerima & memproses **permintaan dataset** dari peneliti yang penelitiannya telah disetujui/berizin, sesuai lingkup izin & etik.

### Auditor
Mengakses **audit trail / log** seluruh aktivitas (perubahan status, akses data, unggah dokumen) dan memeriksa kepatuhan penelitian terhadap regulasi. Read-only.

> Catatan: alur inti pada §4 saat ini memodelkan jalur **Peneliti ↔ CRU ↔ KEPK**. Peran Reviewer (telaah ilmiah), Direksi (approval), Rekam Medis (dataset request), dan Auditor (audit) adalah bagian dari cakupan (§0) yang disisipkan pada titik yang sesuai saat implementasi. 


---

## 3. Arsitektur & Teknologi

| Aspek | Implementasi |
|---|---|
| Framework | Laravel 12 (PHP) |
| UI | Livewire 3 + Tailwind + **daisyUI + Mary UI** (dipilih). *Catatan: Filament tidak dipakai (preferensi tim); Flux UI berbayar.* |
| Otorisasi | **spatie/laravel-permission** — ya, standar & paling matang untuk RBAC (role & permission granular). Direkomendasikan. |
| Realtime / Chat | **Laravel Reverb** (WebSocket resmi) + Laravel Echo. Tepat. |
| Penyimpanan file | **Jangan di dalam app.** Rekomendasi: **object storage S3-compatible** — **MinIO** (self-host, cocok on-prem RS) atau **AWS S3**, diakses via `Storage::disk('s3')`. Alternatif: tetap **SFTP/NAS** jika infrastruktur sudah ada. |
| Database | PostgreSQL (schema `eproposal`, `survey`, `public`) |
| Export/Spreadsheet | Maatwebsite Excel + PhpSpreadsheet |
| Primary Key | UUID (UUIDv7 untuk entitas transaksional) |

Aplikasi menggunakan pola **status + tahapan** pada entitas proposal untuk merepresentasikan posisi proposal dalam alur.

### 3.1 Otorisasi Menu — CRUD Granular (Spatie Permission)

Setiap menu/modul memiliki **permission granular** bergaya `{menu}.{aksi}` dengan 4 aksi dasar: **read, create, update, delete**. Permission di-assign ke **role**, role di-assign ke **user** (spatie/laravel-permission). Dengan begitu tiap user hanya dapat melakukan aksi yang diizinkan pada menu tertentu (mis. ada role yang hanya boleh *read*, ada yang boleh *create/update/delete*).

**Konvensi penamaan:** `proposal.read`, `proposal.create`, `proposal.update`, `proposal.delete` — dan seterusnya untuk tiap menu.

**Contoh matriks** (✔ = diizinkan). Pemetaan ini **dikelola dinamis** oleh Superadmin lewat **Modul Manajemen Menu & Hak Akses (§5)** — bukan di-hardcode:

| Menu / Modul | read | create | update | delete |
|---|:---:|:---:|:---:|:---:|
| proposal | ✔ | ✔ | ✔ | ✔ |
| kaji-etik | ✔ | | ✔ | |
| dataset-request | ✔ | ✔ | ✔ | |
| master-survey (aspek/pertanyaan/skala) | ✔ | ✔ | ✔ | ✔ |
| users | ✔ | ✔ | ✔ | ✔ |
| role & permission | ✔ | ✔ | ✔ | ✔ |
| laporan | ✔ | | | |
| audit-log | ✔ | | | |

**Penegakan (enforcement):**
- **Route / Livewire:** middleware `permission:proposal.update` pada route, atau `$this->authorize('proposal.update')` di komponen.
- **Tampilan:** tombol/aksi disembunyikan dengan `@can('proposal.create')` — user tanpa izin tidak melihat aksinya.
- **Menu sidebar:** item menu hanya tampil bila user punya minimal `{menu}.read`.
- Pengelolaan mapping permission→role dilakukan di menu **Role & Permission** (Superadmin).

---

## 4. Alur Utama (End-to-End)

> **Sumber: informasi langsung dari user (pemilik proses).** Inilah alur utama acuan pembangunan aplikasi — **4 tahap**. Referensi visual: [`alur-utama-eproposal.pdf`](alur-utama-eproposal.pdf). Nilai `proposal_status` kanonik ada di §7b (akan diselaraskan dengan langkah presentasi & reviewer di bawah).

Alur **sequential**; unit pemegang berpindah antar tahap. Aktor terlibat: **Peneliti, CRU, KEPK, Reviewer, Administrator CRU**.

### Tahap 1 — Pengajuan & Review Proposal *(Bagian CRU)*
1. Peneliti **mengajukan proposal** — isian: peneliti utama, tim peneliti, judul; upload surat pengantar & proposal (opsional kaji etik & sertifikat GCP).
2. **Admin CRU me-review berkas.**
3. CRU **meminta peneliti mempresentasikan proposal**.
4. Dari hasil review/presentasi, CRU dapat: **minta revisi berkas**, **menolak**, atau **melanjutkan ke Tahap 2**.

### Tahap 2 — Kaji Etik *(Bagian KEPK + Reviewer)*
1. Peneliti **melengkapi berkas kaji etik**: form kaji etik, **informed consent**, perjanjian kerjasama (PKS), kerahasiaan data.
2. Setelah lengkap, **KEPK mengarahkan berkas ke Reviewer** untuk ditelaah.
3. **Reviewer me-review.** Bila ada kekurangan/kesalahan, Reviewer memberi **komentar & masukan** → peneliti **merevisi & mengisi ulang** berkas. **Revisi dapat lebih dari satu kali** (loop Reviewer ↔ Peneliti).
4. Bila sudah benar, **Reviewer meng-ACC**, lalu **KEPK melanjutkan ke Tahap 3**.

### Tahap 3 — Administrasi Pembayaran *(Bagian CRU)*
1. **Administrasi pembayaran** proposal oleh peneliti, diverifikasi CRU.
2. *Rencana ke depan: integrasi **payment gateway**.*

### Tahap 4 — Perizinan, Pelaporan & Survey *(Bagian CRU)*
1. **Administrator CRU menerbitkan surat izin (draft)** + informasi ke peneliti bahwa penelitian **boleh dilaksanakan**.
2. Peneliti melaksanakan penelitian, lalu **mengunggah laporan penelitian + raw data** ke sistem.
3. **Administrator CRU menerbitkan surat izin final** — namun **sebelum surat izin final dapat diunduh, peneliti WAJIB mengisi survey kepuasan** terlebih dahulu.

### Ringkasan Langkah & Status (referensi teknis)

> Tabel pemetaan status per-langkah — **sudah diselaraskan** dengan deskripsi 4 tahap di atas (presentasi Tahap 1, Reviewer Tahap 2, gate survey Tahap 4) & enum §7b.

| # | Aktor | Langkah | Status |
|---|---|---|---|
| 1 | Peneliti | Ajukan proposal (surat pengantar, proposal; opsional kaji etik & GCP) | `Menunggu Verifikasi Berkas` · T1 |
| 2 | CRU | Review berkas → minta revisi / presentasi / tolak | — |
| 2a | CRU | Minta revisi | `Perlu Revisi Proposal` |
| ↻ | Peneliti | Perbaiki proposal | `Menunggu Verifikasi Revisi` |
| 2b | CRU | Minta presentasi | `Menunggu Presentasi` · T1 |
| 2c | CRU | Tolak | `Ditolak` |
| 3 | CRU | (usai presentasi) Loloskan ke KEPK | `Menunggu Kelengkapan Berkas Etik` · T2 |
| 4 | Peneliti | Lengkapi berkas etik (form kaji etik, informed consent, PKS, kerahasiaan) → KEPK arahkan ke Reviewer | `Menunggu Review Reviewer` |
| 5 | Reviewer | Beri komentar / minta revisi | `Perlu Revisi Reviewer` |
| 5↻ | Peneliti | Perbaiki & isi ulang (loop bisa >1×) | `Menunggu Review Reviewer` |
| 6 | Reviewer | ACC berkas | `Disetujui Reviewer` |
| 7 | KEPK | Lanjut ke Tahap 3 / tolak etik | `Menunggu Pembayaran` · T3 / `Ditolak Kaji Etik` |
| 8 | Peneliti | Upload bukti pembayaran | `Menunggu Verifikasi Pembayaran` · T3 |
| 9 | CRU | Terbitkan draft izin | `Pelaksanaan Penelitian` · T4 |
| 10 | Peneliti | Upload laporan + raw data | `Menunggu Verifikasi Akhir` · T4 |
| 11 | CRU | Terbitkan izin final (unduh terkunci) | `Menunggu Survey Kepuasan` · T4 |
| 12 | Peneliti | Isi survey kepuasan → unduh izin final terbuka | `Selesai` |

Terminal: `Selesai`, `Ditolak` (CRU), `Ditolak Kaji Etik` (KEPK). Detail transisi & enum di §7b.

## 5. Modul Manajemen Menu & Hak Akses (Superadmin) — Dinamis

Superadmin dapat mengelola **menu aplikasi dan hak akses CRUD secara dinamis dari UI**, tanpa mengubah kode. Tiap user hanya bisa mengakses menu tertentu dengan aksi yang diizinkan: **read**, **create**, **update**, **delete**, atau **semua**. Terdiri dari 2 bagian.

### 5.1 Master Menu (CRUD menu dinamis)
Superadmin menambah / mengubah / menghapus menu. Field menu:

| Field | Keterangan |
|---|---|
| `nama` | Label menu (mis. "Proposal") |
| `slug` | Kunci unik untuk permission & identitas menu (mis. `proposal`) |
| `route` / `url` | Tujuan menu |
| `icon` | Ikon sidebar |
| `parent_id` | Untuk submenu (hierarki) |
| `urutan` | Urutan tampil |
| `aktif` | Tampil / sembunyikan |

Saat menu dibuat, sistem **otomatis membuat 4 permission** spatie: `{slug}.read`, `{slug}.create`, `{slug}.update`, `{slug}.delete`. Rename slug → sinkronkan ulang; hapus menu → hapus permission terkait.

### 5.2 Pengaturan Hak Akses (Role × Menu × CRUD)
Superadmin memilih **role** (atau user tertentu), lalu untuk tiap menu mencentang aksi yang diizinkan. Perubahan disimpan sebagai sinkronisasi permission spatie ke role (`syncPermissions`).

Contoh UI (matriks centang):

| Menu | Read | Create | Update | Delete | Semua |
|---|:-:|:-:|:-:|:-:|:-:|
| Proposal | ☑ | ☑ | ☑ | ☐ | ☐ |
| Kaji Etik | ☑ | ☐ | ☑ | ☐ | ☐ |
| Laporan | ☑ | ☐ | ☐ | ☐ | ☐ |

- Centang **Semua** = read + create + update + delete sekaligus.
- User mewarisi hak dari **role**; dapat pula **override per user** (spatie direct permission) bila perlu.

### 5.3 Dampak ke UI
- **Sidebar** hanya menampilkan menu yang punya minimal `read` untuk user tersebut, terurut & hierarkis sesuai master menu.
- Tombol **Create / Edit / Delete** muncul hanya bila user punya permission terkait (`@can('{slug}.{aksi}')`).

### 5.4 Model Data
| Tabel | Kolom kunci |
|---|---|
| `menus` | `id`, `nama`, `slug`, `route`, `icon`, `parent_id`, `urutan`, `aktif` |
| `permissions` (spatie) | otomatis: `{slug}.read/create/update/delete` |
| `role_has_permissions` (spatie) | relasi role ↔ permission |
| `model_has_permissions` (spatie) | override per user (opsional) |

---

## 6. Model Data Proposal

Entitas `proposal` (tabel `eproposal.proposal`, PK UUIDv7) menyimpan seluruh state pengajuan. Field utama:

| Kelompok | Field |
|---|---|
| Identitas | `proposal_nomor`, `proposal_kode` (RSPISS###), `proposal_user_id`, `proposal_institusi_asal`, `proposal_email`, `proposal_phone` |
| Isian | `proposal_peneliti_utama`, `proposal_tim_peneliti`, `proposal_judul_penelitian` |
| Dokumen tahap awal | `proposal_surat_pengantar`, `proposal_proposal_penelitian`, `proposal_kaji_etik`, `proposal_sertifikat_gcp` |
| Dokumen kelengkapan | `proposal_kaji_etik_rspi`, `proposal_kerahasiaan`, `proposal_pks`, `proposal_mta` |
| Administrasi | `proposal_bukti_bayar` |
| Hasil penelitian | `proposal_laporan_penelitian`, `proposal_raw_data_penelitian` |
| Perizinan | `proposal_izin_penelitian_draft`, `proposal_izin_penelitian` |
| Penolakan/revisi | `proposal_surat_penolakan`, `proposal_surat_tanggapan` |
| Presentasi | `proposal_tanggal_presentasi`, `proposal_kategori_presentasi`, `proposal_media_presentasi` |
| State | `proposal_status`, `proposal_tahapan` (1–5) |
| Survey | `isi_survey_kepuasan` (boolean) |

Entitas pendukung: `historyFile` (riwayat versi file proposal), `infokontak_model` (info kontak & biaya), `Aspek`/`Pertanyaan`/`Skala`/`Respon`/`Jawaban` (survey).

---

## 7. State Machine Status Proposal

| Status Awal | Aksi | Aktor | Status Akhir | Tahapan |
|---|---|---|---|---|
| — | Buat proposal (`store`) | Peneliti | `-` | 1 |
| `-` / apa pun | Tolak (`tolak`) | Operator | `Ditolak` | — |
| `-` / apa pun | Tanggapan revisi (`tanggapanrevisi`) | Operator | `Revisi Proposal` | — |
| `Revisi Proposal` | Revisi proposal (`update`) | Peneliti | `Verifikasi Revisi Proposal` | — |
| (setelah verifikasi) | Lengkapi dokumen (`tahap2`) | Peneliti | `Verifikasi Dokumen` | 2 |
| `Verifikasi Dokumen` | Upload bukti bayar (`tahap3`) | Peneliti | `Verifikasi dan Menunggu Draft Izin Penelitian` | 3 |
| `Verifikasi dan Menunggu Draft Izin Penelitian` | Terbitkan draft izin (`izin`) | Operator | `Pelaksanaan Penelitian` | 4 |
| `Pelaksanaan Penelitian` | Upload laporan + raw data (`tahap4`) | Peneliti | `Verifikasi Akhir` | — |
| `Verifikasi Akhir` | Terbitkan izin final (`izin`) | Operator | `Penelitian Selesai` | — |
| `Penelitian Selesai` | Return (`return`) | Operator | `Dokumen Akhir` | 5 |
| `Ditolak` | Return (`return`) | Operator | `-` | 1 |

> Catatan: `antrianProposalController::update` juga dapat menetapkan status/tahapan secara manual (Verifikasi=1, Kelengkapan Dokumen=2, Administrasi=3, Pelaksanaan Penelitian=4, Dokumen Akhir=5).

---

## 7b. Standar Nilai Status (Enum) — Target Rebuild Multi-Unit (CRU + KEPK)

> **Desain target untuk rebuild** (belum ada di kode berjalan; §7 di atas menggambarkan sistem lama single-operator). Prinsip: **satu field `proposal_status` sebagai sumber kebenaran tunggal**. `proposal_tahapan` dan pihak pemegang (`unit_sekarang`) adalah **turunan** dari status. Cabang (revisi/tolak) = status alternatif. Tiga pihak admin: **CRU/Penelitian** (Tahap 1, 3, 4), **KEPK/Kaji Etik** (Tahap 2), dan **Reviewer** (telaah berkas etik di Tahap 2). Alur sequential; termasuk langkah **presentasi** (Tahap 1) & **gate survey kepuasan** sebelum unduh izin final (Tahap 4).

| # | `proposal_status` | Tahap (turunan) | Unit (turunan) | Bola | Pemicu → lanjut |
|---|---|---|---|---|---|
| 1 | `Menunggu Verifikasi Berkas` | 1 | CRU | Admin CRU | peneliti ajukan |
| 2 | `Perlu Revisi Proposal` | 1 | CRU | Peneliti | CRU minta revisi *(cabang)* |
| 3 | `Menunggu Verifikasi Revisi` | 1 | CRU | Admin CRU | peneliti revisi |
| 4 | `Menunggu Presentasi` | 1 | CRU | Peneliti | CRU minta presentasi; usai presentasi → loloskan/revisi/tolak |
| 5 | `Menunggu Kelengkapan Berkas Etik` | 2 | KEPK | Peneliti | CRU loloskan ke KEPK |
| 6 | `Menunggu Review Reviewer` | 2 | Reviewer | Reviewer | peneliti lengkapi etik; KEPK arahkan ke Reviewer |
| 7 | `Perlu Revisi Reviewer` | 2 | Reviewer | Peneliti | Reviewer beri komentar *(loop → #6, bisa >1×)* |
| 8 | `Disetujui Reviewer` | 2 | KEPK | KEPK | Reviewer ACC → KEPK lanjut/tolak |
| 9 | `Menunggu Pembayaran` | 3 | CRU | Peneliti | KEPK lanjut ke Tahap 3 |
| 10 | `Menunggu Verifikasi Pembayaran` | 3 | CRU | Admin CRU | peneliti upload bukti bayar |
| 11 | `Pelaksanaan Penelitian` | 4 | CRU | Peneliti | CRU terbit draft izin |
| 12 | `Menunggu Verifikasi Akhir` | 4 | CRU | Admin CRU | peneliti upload laporan + raw data |
| 13 | `Menunggu Survey Kepuasan` | 4 | CRU | Peneliti | CRU terbit izin final (unduh terkunci sampai survey diisi) |
| 14 | `Selesai` | — | — | — | peneliti isi survey → unduh izin final terbuka *(terminal)* |
| T1 | `Ditolak` | 1 | CRU | — | CRU tolak *(terminal)* |
| T2 | `Ditolak Kaji Etik` | 2 | KEPK | — | KEPK tolak *(terminal)* |

**Implementasi enum (PHP 8.1+ backed enum) — `App\Enums\ProposalStatus`:**

```php
enum ProposalStatus: string
{
    case MenungguVerifikasiBerkas      = 'Menunggu Verifikasi Berkas';
    case PerluRevisiProposal           = 'Perlu Revisi Proposal';
    case MenungguVerifikasiRevisi      = 'Menunggu Verifikasi Revisi';
    case MenungguPresentasi            = 'Menunggu Presentasi';
    case Ditolak                       = 'Ditolak';
    case MenungguKelengkapanBerkasEtik = 'Menunggu Kelengkapan Berkas Etik';
    case MenungguReviewReviewer        = 'Menunggu Review Reviewer';
    case PerluRevisiReviewer           = 'Perlu Revisi Reviewer';
    case DisetujuiReviewer             = 'Disetujui Reviewer';
    case DitolakKajiEtik               = 'Ditolak Kaji Etik';
    case MenungguPembayaran            = 'Menunggu Pembayaran';
    case MenungguVerifikasiPembayaran  = 'Menunggu Verifikasi Pembayaran';
    case PelaksanaanPenelitian         = 'Pelaksanaan Penelitian';
    case MenungguVerifikasiAkhir       = 'Menunggu Verifikasi Akhir';
    case MenungguSurveyKepuasan        = 'Menunggu Survey Kepuasan';
    case Selesai                       = 'Selesai';

    /** Tahapan diturunkan dari status (null = terminal). */
    public function tahapan(): ?int
    {
        return match ($this) {
            self::MenungguVerifikasiBerkas, self::PerluRevisiProposal,
            self::MenungguVerifikasiRevisi, self::MenungguPresentasi => 1,
            self::MenungguKelengkapanBerkasEtik, self::MenungguReviewReviewer,
            self::PerluRevisiReviewer, self::DisetujuiReviewer => 2,
            self::MenungguPembayaran, self::MenungguVerifikasiPembayaran => 3,
            self::PelaksanaanPenelitian, self::MenungguVerifikasiAkhir,
            self::MenungguSurveyKepuasan => 4,
            self::Selesai, self::Ditolak, self::DitolakKajiEtik => null,
        };
    }

    /** Pihak pemegang diturunkan dari status (null = selesai). */
    public function unit(): ?string
    {
        return match ($this) {
            self::MenungguReviewReviewer, self::PerluRevisiReviewer => 'reviewer',
            self::MenungguKelengkapanBerkasEtik, self::DisetujuiReviewer,
            self::DitolakKajiEtik => 'kaji_etik',
            self::Selesai => null,
            default => 'penelitian',
        };
    }

    /** Status berikutnya yang sah dari status ini (peta alur — cegah loncat). */
    public function allowedNext(): array
    {
        return match ($this) {
            // Tahap 1 (CRU)
            self::MenungguVerifikasiBerkas      => [self::PerluRevisiProposal, self::MenungguPresentasi, self::Ditolak],
            self::PerluRevisiProposal           => [self::MenungguVerifikasiRevisi],
            self::MenungguVerifikasiRevisi      => [self::PerluRevisiProposal, self::MenungguPresentasi, self::Ditolak],
            self::MenungguPresentasi            => [self::MenungguKelengkapanBerkasEtik, self::PerluRevisiProposal, self::Ditolak],
            // Tahap 2 (KEPK + Reviewer)
            self::MenungguKelengkapanBerkasEtik => [self::MenungguReviewReviewer],
            self::MenungguReviewReviewer        => [self::PerluRevisiReviewer, self::DisetujuiReviewer],
            self::PerluRevisiReviewer           => [self::MenungguReviewReviewer],
            self::DisetujuiReviewer             => [self::MenungguPembayaran, self::DitolakKajiEtik],
            // Tahap 3 (CRU)
            self::MenungguPembayaran            => [self::MenungguVerifikasiPembayaran],
            self::MenungguVerifikasiPembayaran  => [self::PelaksanaanPenelitian],
            // Tahap 4 (CRU)
            self::PelaksanaanPenelitian         => [self::MenungguVerifikasiAkhir],
            self::MenungguVerifikasiAkhir       => [self::MenungguSurveyKepuasan],
            self::MenungguSurveyKepuasan        => [self::Selesai],
            // terminal
            self::Selesai, self::Ditolak, self::DitolakKajiEtik => [],
        };
    }

    public function canGoTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Selesai, self::Ditolak, self::DitolakKajiEtik], true);
    }
}
```

Transisi divalidasi lewat `allowedNext()` / `canGoTo()` di enum (mencegah loncat). Untuk **audit tektokan** (siapa/kapan pindah) & **komentar Reviewer**, tersedia tabel terpisah `proposal_status_history` dan `proposal_reviews` (lihat §8.3 & §8.4).

**Contoh pemakaian (guard + set status):**

```php
$ke = ProposalStatus::PerluRevisiReviewer;
abort_unless($proposal->status->canGoTo($ke), 403, 'Transisi tidak sah'); // cegah loncat
$proposal->status = $ke;
$proposal->save();
```

---

## 7c. Input & Berkas per Langkah

Field/berkas yang diisi tiap aktor pada tiap transisi (nama kolom mengacu tabel `proposal`; berkas berformat file):

| Aksi → Status | Aktor | Input / Berkas |
|---|---|---|
| Ajukan proposal → `Menunggu Verifikasi Berkas` | Peneliti | `peneliti_utama`, `tim_peneliti`, `judul_penelitian`; upload **`surat_pengantar`** (pdf, wajib), **`proposal_penelitian`** (pdf, wajib), `kaji_etik` (pdf, opsional), `sertifikat_gcp` (pdf, opsional) |
| Minta revisi → `Perlu Revisi Proposal` | CRU | catatan revisi / upload `surat_tanggapan` (pdf) |
| Perbaiki → `Menunggu Verifikasi Revisi` | Peneliti | re-upload `proposal_penelitian` (± `surat_pengantar`) |
| Minta presentasi → `Menunggu Presentasi` | CRU | `tanggal_presentasi`, `kategori_presentasi`, `media_presentasi` |
| Tolak → `Ditolak` | CRU | upload `surat_penolakan` (pdf) |
| Loloskan → `Menunggu Kelengkapan Berkas Etik` | CRU | (aksi; catatan opsional) |
| Lengkapi berkas etik → `Menunggu Review Reviewer` | Peneliti | upload **`form_kaji_etik`**, **`informed_consent`**, **`pks`** (perjanjian kerjasama), **`kerahasiaan_data`** (semua wajib) |
| Minta revisi etik → `Perlu Revisi Reviewer` | Reviewer | `komentar` / masukan (per berkas atau umum) |
| Perbaiki berkas → `Menunggu Review Reviewer` | Peneliti | re-upload berkas etik terkait |
| ACC berkas → `Disetujui Reviewer` | Reviewer | keputusan approve (+ catatan opsional) |
| Tolak etik → `Ditolak Kaji Etik` | KEPK | alasan penolakan / surat |
| Lanjut ke Tahap 3 → `Menunggu Pembayaran` | KEPK | (aksi) |
| Upload bukti bayar → `Menunggu Verifikasi Pembayaran` | Peneliti | upload `bukti_bayar` (jpg/pdf) |
| Terbitkan draft izin → `Pelaksanaan Penelitian` | CRU | upload `izin_penelitian_draft` (pdf) |
| Upload hasil → `Menunggu Verifikasi Akhir` | Peneliti | upload `laporan_penelitian` (pdf), `raw_data_penelitian` (xls/xlsx) |
| Terbitkan izin final → `Menunggu Survey Kepuasan` | CRU | upload `izin_penelitian` (pdf final) |
| Isi survey → `Selesai` | Peneliti | jawaban survey (per pertanyaan) + `saran` → izin final dapat diunduh |

> Berkas kaji etik Tahap 2 memakai **informed consent** (menggantikan MTA). Kolom lama `proposal_mta` tidak dipakai pada alur ini.

---

## 8. Struktur Database Target (Rebuild)

> Skema acuan untuk rebuild. **Foreign key sengaja ditunda dulu** (per permintaan) — kolom relasi (`*_id`) tetap ada, constraint FK ditambahkan kemudian. Perubahan besar vs skema lama: (a) kolom file yang "melebar" dipindah ke tabel **`proposal_documents`**; (b) `proposal_status` pakai enum & `proposal_tahapan` dibuang (turunan); (c) tambah tabel **`proposal_status_history`** & **`proposal_reviews`**; (d) prefix `proposal_` dilepas.

### 8.0 Konvensi Umum (berlaku untuk SEMUA tabel & model)

**Kolom wajib di setiap tabel:**

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK, UUIDv7 |
| `created_at` / `updated_at` | timestamp | `$t->timestamps()` |
| `deleted_at` | timestamp | `$t->softDeletes()` — **semua tabel soft delete** |
| `created_by` / `updated_by` / `deleted_by` | uuid | pelaku aksi (audit) |

**Migration** — daftarkan macro sekali, pakai di semua tabel:

```php
// AppServiceProvider::boot()
Blueprint::macro('auditColumns', function () {
    $this->uuid('created_by')->nullable();
    $this->uuid('updated_by')->nullable();
    $this->uuid('deleted_by')->nullable();
});
```

```php
// pola tiap migration
$t->uuid('id')->primary();
// ... kolom domain ...
$t->timestamps();
$t->softDeletes();
$t->auditColumns();
```

**Model** — pakai trait supaya `boot()` tidak di-copy ke tiap model:

```php
namespace App\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait HasUuidAndAudit
{
    protected static function bootHasUuidAndAudit(): void
    {
        static::creating(function ($model) {
            if (! $model->id) {
                $model->id = (string) Str::uuid7();
            }

            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id(); // isi sekalian saat create
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
                $model->deleted_by = Auth::id();  // pelaku penghapusan
                $model->saveQuietly();            // hindari memicu event updating lagi
            }
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }
}
```

**Pemakaian di model:**

```php
class Proposal extends Model
{
    use HasUuidAndAudit, SoftDeletes;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $casts = [
        'status'              => ProposalStatus::class,
        'isi_survey_kepuasan' => 'boolean',
    ];
}
```

> Catatan: `Str::uuid7()` tersedia di Laravel 11+. Jika tidak memakai trait, tempel isi `bootHasUuidAndAudit()` sebagai `boot()` di tiap model (kode identik).

### 8.1 `proposal` (ramping)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK (UUIDv7) |
| `nomor` | bigint | nomor urut per tahun |
| `kode` | varchar | RSPISS### |
| `peneliti_utama` | varchar | |
| `tim_peneliti` | text | |
| `judul_penelitian` | text | |
| `institusi_asal` / `email` / `phone` | varchar | snapshot pengaju |
| `user_id` | uuid | pengaju (relasi users) |
| `status` | varchar | cast ke enum `ProposalStatus` |
| `unit_sekarang` | varchar | turunan status; materialized + index untuk antrian |
| `tanggal_presentasi` | timestamp | |
| `kategori_presentasi` / `media_presentasi` | varchar | |
| `isi_survey_kepuasan` | **boolean** | (dulu varchar) |
| `created_by` / `updated_by` / `deleted_by` | uuid | audit (§8.0) |
| `created_at` / `updated_at` / `deleted_at` | timestamp | timestamps + softDeletes |

*Dibuang: seluruh kolom file `proposal_*` (→ `proposal_documents`), `proposal_tahapan` (→ turunan status).*

### 8.2 `proposal_documents` (file per baris — pengganti kolom file melebar)
```php
Schema::create('proposal_documents', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('proposal_id');                 // relasi proposal (FK menyusul)
    $t->string('jenis');                     // enum DocumentType (lihat bawah)
    $t->string('path');                      // lokasi di object storage (S3/MinIO)
    $t->string('nama_asli')->nullable();
    $t->unsignedSmallInteger('versi')->default(1);
    $t->uuid('uploaded_by')->nullable();
    $t->timestamps();
    $t->softDeletes();
    $t->auditColumns();                      // created_by, updated_by, deleted_by (§8.0)
    $t->index(['proposal_id', 'jenis']);
});
```
**`jenis` (DocumentType enum)** — sesuai §7c: `surat_pengantar`, `proposal`, `kaji_etik`, `sertifikat_gcp` *(T1)*; `form_kaji_etik`, `informed_consent`, `pks`, `kerahasiaan_data` *(T2)*; `bukti_bayar` *(T3)*; `laporan_penelitian`, `raw_data` *(T4)*; `izin_draft`, `izin_final`, `surat_penolakan`, `surat_tanggapan` *(output admin)*. Tambah jenis dokumen = tambah nilai enum, **bukan** kolom baru. `versi` mendukung riwayat revisi file.

### 8.3 `proposal_status_history` (audit tektokan → memenuhi Audit Log)
```php
Schema::create('proposal_status_history', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('proposal_id');
    $t->string('from_status')->nullable();
    $t->string('to_status');
    $t->string('unit')->nullable();          // penelitian|kaji_etik|reviewer
    $t->uuid('actor_id')->nullable();        // siapa yang memindahkan
    $t->text('catatan')->nullable();
    $t->timestamps();
    $t->softDeletes();
    $t->auditColumns();                      // §8.0
});
```

### 8.4 `proposal_reviews` (komentar & keputusan per ronde)
```php
Schema::create('proposal_reviews', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('proposal_id');
    $t->unsignedTinyInteger('tahap');        // 1..4
    $t->string('unit');                      // cru|kepk|reviewer
    $t->uuid('reviewer_id')->nullable();
    $t->string('keputusan');                 // approve|revise|reject
    $t->text('komentar')->nullable();
    $t->unsignedSmallInteger('ronde')->default(1);
    $t->timestamps();
    $t->softDeletes();
    $t->auditColumns();                      // §8.0
});
```

### 8.5 Pembersihan tabel lama (rebuild)
- `users`: tinjau `status_user` (tumpang tindih dengan role spatie).
- `survey.respon`: `responden_id` → `uuid` (dari varchar).
- Lepas prefix `proposal_` pada kolom entitas `proposal`.
- Perbaiki tipe boolean/int yang sebelumnya `varchar`.

> Foreign key untuk semua kolom `*_id` di atas ditambahkan pada tahap berikutnya (di-skip sesuai permintaan). Master survey (`master_aspek`/`master_pertanyaan`/`master_skala`/`respon`/`jawaban`) & RBAC spatie tetap seperti terdokumentasi.

