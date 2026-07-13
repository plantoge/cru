# PRD — Chat Real-time per Proposal (Laravel Reverb)

> **Status: RANCANGAN, belum diimplementasikan.** Bagian dari F10 (`docs/rebuild-progress.md`). Dokumen terpisah dari `docs/prd.md` supaya spec inti tidak membengkak — begitu disetujui & dibangun, ringkasannya digabung ke `rebuild-progress.md`.
>
> Dicek 2026-07-13: `laravel/reverb` **belum ter-install**, `routes/channels.php` **belum ada**, `BROADCAST_CONNECTION=log` (belum aktif). Semua di bawah ini rancangan dari nol.

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

## 9. Fase implementasi (kalau disetujui)

- **R1** — Migration `proposal_messages` + model + `Proposal::bisaChat()` helper (reuse otorisasi).
- **R2** — Komponen `Chat.php` + view, mode **polling** dulu (`wire:poll`) — jalan tanpa Reverb sama sekali, bisa dites & dipakai user secepatnya.
- **R3** — (opsional, setelah C1 dikunci) `composer require laravel/reverb`, event `ProposalMessageSent`, `routes/channels.php`, ganti polling jadi Echo listener.
- **R4** — Badge unread count di sidebar/dashboard.
- **R5** — Test: otorisasi per-role (termasuk **reviewer harus 403**), kirim/terima pesan, isolasi antar proposal (pesan proposal A gak bocor ke B — pola yang sama dipakai `SurveyGateTest`).
