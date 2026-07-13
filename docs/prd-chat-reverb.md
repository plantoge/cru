# PRD — Chat Real-time per Proposal (Laravel Reverb)

> **Status: DIBANGUN 2026-07-13** (C1 dikunci: langsung Reverb, bukan polling). R1–R3 & R5 selesai; R4 (badge unread) belum. Ringkasan hasil bangun & cara jalanin ada di `docs/rebuild-progress.md` §F13. Dokumen ini tetap jadi acuan desain.

---

## 1. Tujuan & Masalah

Komunikasi peneliti ↔ petugas (CRU/KEPK) selama ini di luar sistem (WA/telepon/email pribadi) — tidak tercatat, tidak bisa diaudit, gampang hilang konteks (proposal mana yang dibahas). Chat terintegrasi bikin diskusi **melekat ke proposal**, tersimpan, dan konsisten dengan prinsip audit trail yang sudah dibangun (`proposal_status_history`).

**Bukan** chat umum (bukan pengganti WA/messenger app). **Kontekstual per proposal** — tiap thread nempel di satu proposal, bukan direct-message bebas antar sembarang user.

## 2. Aktor & Aturan Akses

Wajib **konsisten dengan aturan kerahasiaan yang sudah dibangun** (lihat `docs/prd.md` §4 Tahap 2):

| Aktor | Boleh chat di thread proposal X? |
|---|---|
| Peneliti pemilik proposal X | Selalu boleh |
| CRU | Boleh (permission `antrian-cru.read`) — tidak dibatasi `unit_sekarang` biar riwayat diskusi lama tetap kebaca sebagai read-only, tapi kirim pesan baru cuma masuk akal kalau proposal masih di jalur CRU |
| KEPK | Boleh (permission `kaji-etik.read`) |
| **Reviewer** | **TIDAK BOLEH** chat dengan peneliti — identitas & komunikasi reviewer dirahasiakan, sama seperti komentar review sekarang. Kalau reviewer perlu ngobrol soal proposal, itu lewat KEPK (di luar scope v1, atau thread terpisah `unit=kaji_etik` internal — non-goal v1) |
| Superadmin/Auditor | Read-only lewat audit log (non-goal v1, lihat §7) |

Aturan ini **plek sama** dengan yang sudah dipakai `Proposal\Show::mount()` (`app/Livewire/Proposal/Show.php`) — tinggal reuse, jangan bikin aturan otorisasi baru yang beda logic.

## 3. Model Data

Ikut konvensi wajib prd §8.0 (uuid v7, `timestamps()+softDeletes()+auditColumns()`), pola penamaan sama seperti `proposal_documents`/`proposal_reviews`/`proposal_status_history` yang sudah ada.

### `proposal_messages`

```php
Schema::create('proposal_messages', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('proposal_id');              // relasi proposal (FK menyusul, sesuai konvensi project)
    $t->uuid('sender_id');                 // users.id pengirim
    $t->string('sender_unit')->nullable(); // enum Unit snapshot saat kirim (penelitian|kaji_etik) — null utk peneliti
    $t->text('pesan');
    $t->timestamp('dibaca_at')->nullable(); // dibaca oleh pihak lawan bicara (single, bukan per-user — thread cuma 2 pihak)
    $t->timestamps();
    $t->softDeletes();
    $t->auditColumns();

    $t->index(['proposal_id', 'created_at']);
});
```

Model `ProposalMessage` — trait `HasUuidAndAudit, SoftDeletes` seperti model lain, `belongsTo(Proposal::class)`, `belongsTo(User::class, 'sender_id')`.

**Kenapa bukan tabel generik `messages` + `conversations`:** proyek ini proposal-centric, bukan aplikasi messaging umum. Thread SELALU nempel ke satu proposal (`proposal_id` wajib, bukan nullable) — lebih simpel dari desain conversation/participant generik, dan otorisasi tinggal reuse aturan proposal yang sudah ada (§2).

## 4. Real-time via Laravel Reverb

### 4.1 Instalasi (belum dikerjakan)

```bash
composer require laravel/reverb
php artisan reverb:install   # generate config/broadcasting.php, config/reverb.php, isi .env REVERB_*
```

`.env` baru yang bakal muncul: `BROADCAST_CONNECTION=reverb`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT` (default 8080), `REVERB_SCHEME`. Frontend butuh `laravel-echo` + `pusher-js` (Reverb protokol-kompatibel Pusher) via `npm install --save-dev laravel-echo pusher-js`, lalu `resources/js/echo.js` diaktifkan (Laravel 12 skeleton biasanya sudah nyediakan file ini ter-comment).

### 4.2 Channel privat & otorisasi

```php
// routes/channels.php (file baru)
Broadcast::channel('proposal.{proposalId}', function (User $user, string $proposalId) {
    $proposal = Proposal::find($proposalId);
    if (! $proposal) return false;

    return $proposal->user_id === $user->id
        || $user->canAny(['antrian-cru.read', 'kaji-etik.read']); // TANPA antrian-reviewer.read — sengaja
});
```

Satu channel privat per proposal (`proposal.{id}`), bukan per-pasangan-user — karena tiap proposal cuma punya lawan bicara tunggal di sisi petugas pada satu waktu (siapa pun yang megang permission itu ikut lihat, sama seperti antrian sekarang).

### 4.3 Event

```php
class ProposalMessageSent implements ShouldBroadcastNow // *Now = kirim langsung, gak lewat queue
{
    public function __construct(public ProposalMessage $message) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("proposal.{$this->message->proposal_id}");
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'pesan' => $this->message->pesan,
            'pengirim' => $this->message->sender->name,
            'dibuat' => $this->message->created_at->format('H:i'),
        ];
    }
}
```

`ShouldBroadcastNow` (bukan `ShouldBroadcast`) dipilih supaya **tidak butuh queue worker tambahan** — kirim pesan langsung broadcast synchronous. Trade-off: request kirim pesan sedikit lebih lama (nunggu broadcast selesai), tapi infra lebih simpel (relevan karena project ini belum punya queue worker berjalan di manapun).

## 5. Komponen Livewire

`app/Livewire/Proposal/Chat.php` — komponen kecil, di-embed di `resources/views/livewire/proposal/show.blade.php` (kolom kanan, sebelah "Riwayat Status" yang sudah ada).

- `mount(Proposal $proposal)` — load pesan lama (paginated/limit 50 terbaru), cek otorisasi (reuse logic §2, idealnya taruh di satu tempat: `Proposal::bisaChat(User $user): bool` biar dipakai `Show::mount()` juga suatu saat).
- `kirim()` — validasi `pesan` (required, max 2000 karakter), simpan `ProposalMessage`, `broadcast(new ProposalMessageSent($msg))->toOthers()` (`toOthers()` biar pengirim sendiri gak dobel nerima via broadcast — dia udah lihat optimistic update dari Livewire).
- Listener browser event dari Echo: `#[On('echo-private:proposal.{proposalId},ProposalMessageSent')]` method `pesanMasuk($data)` — push ke array pesan lokal, trigger auto-scroll (JS kecil di view).

UI: daftar bubble pesan (kiri = lawan bicara, kanan = diri sendiri, gaya chat standar), textarea + tombol kirim di bawah, auto-scroll ke pesan terbaru.

## 6. Operasional — proses Reverb harus jalan terus

**Beda dari fitur lain di project ini** (yang semuanya request-response biasa) — Reverb butuh **proses server terpisah yang jalan terus-menerus**:

```bash
php artisan reverb:start          # dev
php artisan reverb:start --host=0.0.0.0 --port=8080  # akses dari luar localhost
```

Implikasi buat RS (infra internal, Windows/Laragon per `toolchain-paths` memory):
- Proses ini **bukan** `php artisan serve` biasa — kalau servernya restart/mati, chat berhenti real-time (fallback: pesan tetap kesimpen di DB, cuma gak muncul instan, perlu refresh manual).
- Butuh disupervisi biar auto-restart kalau crash — di Windows bisa pakai NSSM (jadikan Windows Service) atau Task Scheduler; di Linux biasanya `supervisor`. **Belum diputuskan** — perlu keputusan user sebelum implementasi (§8).
- Port Reverb (default 8080) harus kebuka di firewall RS kalau CRU/KEPK akses dari jaringan lain, bukan cuma server yang sama.

**Fallback tanpa Reverb (kalau operasional dianggap kerepotan):** turunkan ke `wire:poll="3s"` di komponen Chat — polling tiap 3 detik, tanpa proses tambahan, tanpa WebSocket. Delay kecil (maks 3 detik), tapi jauh lebih simpel dioperasikan dan tetap "cukup real-time" buat chat kerja kayak gini. Bisa jadi v1 dulu, upgrade ke Reverb kalau sudah terbukti kepake & infra siap.

## 7. Non-goals v1

- **Reviewer TIDAK ikut chat** — tetap rahasia sesuai desain Tahap 2 yang sudah ada.
- Tidak ada file attachment di chat (upload dokumen tetap lewat alur `proposal_documents` yang sudah ada, bukan drag-drop ke chat).
- Tidak ada group chat / lebih dari 2 pihak per thread.
- Tidak ada read receipt granular ("dibaca jam berapa oleh siapa") — cukup satu `dibaca_at` boolean-ish.
- Tidak ada notifikasi push/email tiap pesan masuk (badge unread di dashboard cukup untuk v1).
- Superadmin/Auditor read-only viewer belum dibangun (bisa nyusul lewat `audit-log` yang sudah ada, tinggal tambah tab).

## 8. Keputusan yang perlu dikunci sebelum mulai bangun

| # | Isu | Opsi |
|---|---|---|
| C1 | Reverb (real-time penuh) vs `wire:poll` (polling sederhana) buat v1 | Rekomendasi: **mulai polling**, upgrade ke Reverb kalau fitur udah terbukti dipakai — hindari beban operasional proses tambahan di awal |
| C2 | Kalau pilih Reverb: siapa yang supervisi proses `reverb:start` biar auto-restart | NSSM (Windows Service) / Task Scheduler / lainnya — infra RS, di luar kendali kode |
| C3 | Retensi pesan — disimpan selamanya (ikut softDeletes proposal) atau ada kebijakan arsip/hapus setelah proposal `Selesai` sekian lama | Belum diputuskan, ranah kebijakan RS bukan teknis |

## 9. Fase implementasi

- [x] **R1** — Migration `proposal_messages` + model + `Proposal::bisaChat()` helper (reuse otorisasi).
- [x] **R2** — Komponen `Chat.php` + view (bubble chat, `x-mary-card`).
- [x] **R3** — C1 dikunci langsung Reverb (bukan polling): `composer require laravel/reverb` + `laravel-echo`/`pusher-js`, event `ProposalMessageSent` (`ShouldBroadcastNow`), `routes/channels.php`, `resources/js/echo.js`, listener `#[On('echo-private:proposal.{proposal.id},ProposalMessageSent')]`.
- [ ] **R4** — Badge unread count di sidebar/dashboard. *(belum, follow-up)*
- [x] **R5** — `tests/Feature/ChatTest.php`, 12 test: otorisasi per-role (reviewer 403 dua lapis — `bisaChat()` langsung + `mount()`), kirim/terima pesan, isolasi antar proposal, widget tak muncul di UI untuk reviewer.

### Jebakan yang kena & fix-nya

1. **`php artisan reverb:install --no-interaction` crash** (`TypeError` di `Laravel\Prompts\select()`) — command ini masih minta pilih driver secara interaktif meski `--no-interaction`, dan prompt-nya gak punya default fallback. Untungnya sempat jalan sebagian sebelum crash: `bootstrap/app.php` ke-update otomatis (`channels: routes/channels.php`), `.env` dapet `REVERB_APP_ID/KEY/SECRET/HOST/PORT/SCHEME` (digenerate lokal, bukan dari akun luar) + `VITE_REVERB_*`, `config/broadcasting.php` & `routes/channels.php` ter-publish (placeholder, ditimpa manual). Yang perlu dilengkapi manual: `BROADCAST_CONNECTION` (masih `log`, ganti ke `reverb`), `resources/js/echo.js` (belum dibuat sama sekali), `resources/js/bootstrap.js` (belum import echo).
2. **`Proposal::bisaChat()` SENGAJA lebih sempit dari akses lihat halaman** (`Proposal\Show::mount()`). Reviewer boleh **lihat** proposal yang ditugaskan ke dia (buat kerja reviewnya), tapi TIDAK boleh **chat**. Widget Chat cuma di-embed di `show.blade.php` untuk `$isPemilik || $isCru || $isKepk` — kalau lupa kondisi ini, reviewer yang buka halaman proposal akan ke-403 seketika (component mount gagal), bukan cuma "gak lihat tombol chat".
3. **Urutan kode di `mount()` penting**: `abort_unless()` harus dipanggil **setelah** `$this->proposal` di-assign, bukan sebelumnya. Kalau kebalik, proses internal Livewire yang butuh akses `$this->proposal` saat menangani exception nemu property masih kosong → `ErrorException` yang membingungkan, bukan `HttpException` 403 yang bersih.
4. **`broadcast($event)->toOthers()` di dalam kode yang diuji via `Livewire::test()`** — `PendingBroadcast::__destruct()` yang beneran mengirim event, dan timing `__destruct()` (garbage collection PHP) gak deterministik relatif ke siklus hydrate/dehydrate Livewire saat testing → bikin `Livewire::test()` gagal decode snapshot komponen dengan pesan error yang gak nyambung sama sekali ke kodenya. Fix: `Event::fake([ProposalMessageSent::class])` di `setUp()` test.
5. **`Livewire::test(Chat::class, [...])` tidak mem-propagate `HttpException` dari `mount()` ke pemanggil test** untuk komponen non-full-page (beda dari komponen full-page yang di-mount lewat route HTTP, exception-nya diproses middleware+handler standar Laravel). Diverifikasi lewat debug manual — `abort_unless()` betul-betul dipanggil dengan kondisi `false`, tapi `Livewire::test()` tetap "sukses" tanpa exception apa pun. Fix: untuk kasus ini, test manggil `(new Chat())->mount($proposal)` langsung (PHP call biasa, bukan lewat testing harness Livewire), plus test pelengkap yang verifikasi widget gak muncul di response HTTP halaman (defense-in-depth di level UI).
