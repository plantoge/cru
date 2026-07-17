# Setup FrankenPHP di Ubuntu Server

> Target: menjalankan eProposal (Laravel 12 + Livewire 3 + Mary UI) di Ubuntu Server
> memakai **FrankenPHP** sebagai app server, menggantikan kombinasi Nginx + PHP-FPM.
> Dev lokal tetap Laragon + `artisan serve` — dokumen ini khusus server.

## 1. Kenapa FrankenPHP

FrankenPHP = Caddy (web server, Go) + PHP tertanam di dalam prosesnya. Satu binary,
satu service. Konsekuensi praktis buat kita:

- **Satu proses, bukan dua.** Tidak ada Nginx + PHP-FPM yang harus disinkronkan
  (socket, timeout, `client_max_body_size` vs `post_max_size`, dst).
- **HTTPS otomatis** via Caddy (berguna nanti kalau eProposal keluar dari IP internal).
- **Worker mode** — aplikasi di-boot sekali lalu ditahan di memori. Ini sumber
  lonjakan performanya (§4), tapi juga sumber semua jebakannya.
- Kompresi modern (zstd/brotli) dan HTTP/2–3 tanpa konfigurasi tambahan.

Yang **tidak** diselesaikan FrankenPHP: queue worker dan scheduler tetap proses
terpisah (§6). Jangan berharap satu service menghandle semuanya.

## 2. Dua mode — pilih dulu sebelum instalasi

Ini keputusan terpenting di dokumen ini. Keduanya pakai binary yang sama.

| | Mode klasik (`php_server`) | Mode worker (Octane) |
|---|---|---|
| Cara kerja | boot Laravel tiap request, persis seperti PHP-FPM | boot sekali, tahan di memori |
| Performa | setara PHP-FPM | ±2–4× lebih cepat |
| Risiko | ~nol, drop-in | state bocor antar request, memory leak |
| Perlu ubah kode | tidak | mungkin (audit §7) |
| Deploy | copy file, selesai | wajib `octane:reload` |

**Rekomendasi: mulai dari mode klasik.** Naikkan ke worker setelah service-nya stabil
dan sudah dipakai user beberapa hari. Alasannya: kalau ada masalah, Anda tahu
penyebabnya FrankenPHP atau worker mode — bukan menebak dua variabel sekaligus.
Kabar baiknya, hasil audit di §7 menunjukkan kode eProposal saat ini **sudah aman**
untuk worker mode, jadi kenaikan itu kemungkinan besar mulus.

## 3. Instalasi

Pakai paket apt (bukan install script), supaya dapat systemd unit, path php.ini yang
jelas, dan bisa `apt upgrade` seperti paket lain.

```bash
VERSION=84   # cocokkan dengan PHP dev kita (8.4). 82–85 tersedia.

sudo mkdir -p /etc/apt/keyrings
sudo curl https://pkg.henderkes.com/api/packages/${VERSION}/debian/repository.key \
  -o /etc/apt/keyrings/static-php${VERSION}.asc
echo "deb [signed-by=/etc/apt/keyrings/static-php${VERSION}.asc] \
  https://pkg.henderkes.com/api/packages/${VERSION}/debian php-zts main" | \
  sudo tee /etc/apt/sources.list.d/static-php${VERSION}.list
sudo apt update
sudo apt install frankenphp
```

> **Kenapa `VERSION=84`, bukan 85?** Binary default FrankenPHP membawa PHP 8.5.
> `composer.json` kita hanya menuntut `^8.2`, tapi menyamakan versi server dengan
> versi dev (8.4.12) menghilangkan satu kelas bug "jalan di lokal, aneh di server".
> Naikkan ke 85 belakangan, sebagai perubahan tersendiri yang diuji sendiri.

Setelah instalasi:

| Item | Lokasi |
|---|---|
| Binary | `/usr/bin/frankenphp` (sudah di PATH — penting untuk Octane, §5) |
| Caddyfile | `/etc/frankenphp/Caddyfile` |
| php.ini | `/etc/php-zts/php.ini` |

### Ada DUA PHP di server ini — jangan tertukar

Ini sumber jebakan paling mahal di dokumen ini. Setelah instalasi, server punya dua
PHP yang **sama sekali terpisah**, masing-masing dengan php.ini dan daftar ekstensi
sendiri:

| | Binary | php.ini | Dipakai untuk |
|---|---|---|---|
| **PHP-ZTS** (milik FrankenPHP) | `/usr/bin/php-zts` | `/etc/php-zts/php.ini` | **melayani semua request web** |
| PHP biasa (mis. dari PPA Sury, sering sudah ada duluan) | `/usr/bin/php` | `/etc/php/8.4/cli/php.ini` | CLI: artisan, composer, queue worker (§6), cron |

Yang menjalankan aplikasi Anda adalah **PHP-ZTS**. Ekstensi yang terpasang di `php`
biasa **tidak** otomatis ada di PHP-ZTS.

Karena itu verifikasi ekstensi **wajib memakai binary ZTS**, bukan `php`:

```bash
/usr/bin/php-zts -v
/usr/bin/php-zts -m | grep -E 'pdo|pgsql|mbstring|intl|zip|gd'
```

> Memakai `php -m` di sini adalah **kesalahan yang lolos diam-diam**: hasilnya hijau
> semua (karena PHP Sury memang lengkap), lalu aplikasi tetap mati dengan
> `Class "PDO" not found` begitu request pertama masuk — sebab PHP-ZTS-nya kosong.
> Gejalanya: HTTP 500 dengan `X-Powered-By: PHP/8.4.x` (artinya request sudah sampai
> PHP, jadi bukan masalah Caddy/permission).

> Juga **bukan `frankenphp php-cli -m`.** Subcommand itu hanya menerima satu argumen
> posisional (path script atau command artisan, mis. `frankenphp php-cli artisan
> migrate`) — ia tidak mem-parsing flag PHP seperti `-m`/`-v`/`-r`, dan akan
> memperlakukan flag itu sebagai nama file (`Failed opening required '-m'`).

Paket ZTS default **tidak membawa PDO sama sekali**. Lihat yang tersedia lalu pasang
yang perlu:

```bash
apt-cache search php-zts | grep -iE 'pdo|pgsql|mysql|sqlite'
sudo apt install php-zts-pdo php-zts-pdo-pgsql php-zts-pgsql   # sesuaikan dengan DB Anda
sudo systemctl restart frankenphp
```

Ekstensi lain menyusul dengan pola sama: `sudo apt install php-zts-<nama-ekstensi>`.

### php.ini

Edit `/etc/php-zts/php.ini`. Angka upload di bawah **bukan** pilihan bebas — itu
turunan dari limit Livewire di `config/livewire.php` (`max:25600` = 25 MB, lihat
[setup-file-storage.md](setup-file-storage.md)). Kalau php.ini lebih kecil, file
ditolak diam-diam di lapis PHP sebelum validasi Livewire sempat jalan:

```ini
upload_max_filesize = 30M
post_max_size = 30M
memory_limit = 256M
max_execution_time = 60
```

30M, bukan 25M — beri margin untuk overhead multipart form. Restart service setiap
kali file ini berubah.

## 4. Mode klasik (mulai di sini)

> **Jangan taruh aplikasi di dalam `/home`.** Unit systemd bawaan paket memakai
> `ProtectHome=true` — seluruh `/home` disembunyikan dari proses FrankenPHP, apa pun
> permission Unix-nya. Gejalanya menyesatkan: **HTTP 403 dengan body kosong**, dan
> `storage/logs/laravel.log` tidak pernah terbuat (request tidak sampai ke PHP),
> sementara `namei -l` menunjukkan semua folder sudah `rwx`. `ProtectSystem=full`
> di unit yang sama hanya membuat `/usr`, `/boot`, `/etc` read-only — `/var/www`
> tidak kena, jadi taruh aplikasi di sana.

Deploy kode ke `/var/www/eproposal`, lalu ganti isi `/etc/frankenphp/Caddyfile`:

```caddyfile
{
	frankenphp
	order php_server before file_server
}

:80 {
	root /var/www/eproposal/public
	encode zstd br gzip
	php_server
}
```

Ganti `:80` dengan nama domain (mis. `eproposal.rs.local`) kalau sudah ada DNS —
Caddy akan urus sertifikat HTTPS otomatis. Untuk IP internal seperti sekarang
(`192.168.24.201`), biarkan `:80`; auto-TLS tidak bisa menerbitkan sertifikat untuk
alamat IP.

Permission dan jalankan. Perhatikan pemiliknya **`frankenphp`, bukan `www-data`** —
unit bawaan paket berjalan sebagai `User=frankenphp Group=frankenphp` (cek sendiri
dengan `systemctl cat frankenphp`):

```bash
sudo chown -R frankenphp:frankenphp /var/www/eproposal/storage /var/www/eproposal/bootstrap/cache
sudo systemctl enable --now frankenphp
sudo systemctl status frankenphp
```

Kalau port 80 masih dipegang Nginx/Apache lama, FrankenPHP gagal start dengan
`bind: address already in use` dan systemd hanya menampilkannya sebagai
`activating (auto-restart)`. Cek `sudo ss -tlnp | grep -E ':80|:443'`, lalu
`sudo systemctl disable --now apache2` (atau `nginx`) sebelum melanjutkan.

Sampai sini aplikasi sudah jalan. **Berhenti dulu di titik ini** — pakai beberapa hari,
pastikan upload dokumen, download, dan login normal, baru lanjut ke §5.

## 5. Mode worker via Octane

```bash
cd /var/www/eproposal
composer require laravel/octane
php artisan octane:install --server=frankenphp
```

`octane:install` mencari program bernama `frankenphp` di PATH — paket apt di §3 sudah
menaruhnya di `/usr/bin`, jadi Octane memakai binary itu dan tidak mengunduh lagi.

Di mode ini **FrankenPHP yang jadi web server-nya**, bukan systemd unit `frankenphp`
bawaan paket. Matikan yang lama supaya tidak berebut port:

```bash
sudo systemctl disable --now frankenphp
```

Jalankan `.env` produksi dengan `OCTANE_HTTPS=false` (kita masih HTTP internal). Kalau
kelak dipasang di belakang reverse proxy TLS, set `true` — tanpa itu semua URL yang
di-generate Laravel akan berskema `http://` dan Livewire akan kena mixed-content.

### systemd unit

`/etc/systemd/system/eproposal-octane.service`:

```ini
[Unit]
Description=eProposal Octane (FrankenPHP)
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/eproposal
ExecStart=/usr/bin/php artisan octane:start \
  --server=frankenphp --host=0.0.0.0 --port=80 --admin-port=2019
Restart=always
RestartSec=3
StandardOutput=append:/var/www/eproposal/storage/logs/octane.log
StandardError=inherit

[Install]
WantedBy=multi-user.target
```

Port 80 butuh privilege; karena unit jalan sebagai `www-data`, beri kapabilitas ke
binary sekali saja:

```bash
sudo setcap cap_net_bind_service=+ep /usr/bin/frankenphp
sudo systemctl daemon-reload
sudo systemctl enable --now eproposal-octane
```

Jumlah worker default = jumlah core CPU. Naikkan/turunkan lewat `--workers=N` kalau
RAM jadi sempit — tiap worker memegang satu instance Laravel di memori.
`--max-requests=500` (default) me-restart worker berkala; itu jaring pengaman terhadap
memory leak, jangan dinaikkan tanpa alasan.

## 6. Queue & scheduler tetap terpisah

FrankenPHP/Octane **tidak** menjalankan queue. `QUEUE_CONNECTION=database` dan email
Resend kita lewat queue, jadi tanpa ini email verifikasi tidak akan terkirim —
gejalanya "tidak ada error, tapi email tidak sampai".

`/etc/systemd/system/eproposal-queue.service`:

```ini
[Unit]
Description=eProposal queue worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/eproposal
ExecStart=/usr/bin/php artisan queue:work --tries=3 --timeout=90
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Scheduler via crontab `www-data`:

```cron
* * * * * cd /var/www/eproposal && php artisan schedule:run >> /dev/null 2>&1
```

Catat: queue worker juga menahan kode di memori. Setiap deploy, dia **wajib** di-restart
(`queue:restart`), sama seperti Octane.

## 7. Catatan khusus eProposal

Hasil audit kode terhadap jebakan Octane yang umum:

- **Singleton / state statis: aman.** Tidak ada `singleton()`, `static $`, atau
  injeksi container/request ke konstruktor di `app/`. Ini penyebab bug worker-mode
  paling umum, dan kita bersih.
- **`env()` di luar config: aman.** Tidak ada pemanggilan `env()` di `app/`, jadi
  `php artisan config:cache` (wajib di produksi) tidak akan memutus apa pun.
- **spatie/laravel-permission: aman.** `config/permission.php` → `'teams' => false`.
  Fitur teams inilah yang menyimpan state per-request di registrar dan bocor antar
  request di worker mode; kita tidak memakainya.
- **Mary UI.** `Blade::component('badge', ...)` di `AppServiceProvider::boot()` (workaround
  bug Mary 2.9) hanya jalan sekali saat worker boot — justru itu yang diinginkan.
- **`DOCUMENTS_PATH` harus path Linux.** `.env` sekarang berisi `D:\eproposal-files`
  (Windows). Di server jadi mis. `/var/eproposal-files`, dan folder itu **harus**
  writable oleh `www-data` — bukan user login Anda:
  ```bash
  sudo mkdir -p /var/eproposal-files
  sudo chown -R www-data:www-data /var/eproposal-files
  ```
- **Postgres remote** (`172.16.202.207`). Worker menahan koneksi DB terbuka lama.
  Kalau ada firewall yang memutus koneksi idle, gejalanya error sporadis
  "server closed the connection unexpectedly". Octane me-reset koneksi tiap request,
  jadi biasanya aman — tapi ini hal pertama yang dicurigai kalau muncul error DB acak.
- **Livewire temp upload** menulis ke `storage/app/private/livewire-tmp`. Pastikan ikut
  ter-`chown` (§4).
- **`storage:link`** tetap perlu untuk disk `public`. Disk `dokumen` sengaja privat dan
  tidak butuh symlink.

## 8. Alur deploy ulang

> **Node ≥ 20.19 wajib** (`vite ^7` + `tailwindcss ^4` di `package.json`). Node 12
> bawaan Ubuntu 22.04 gagal dengan `SyntaxError: Unexpected token '.'` — itu Node
> tersedak sintaks optional chaining, bukan bug Vite. Pasang dari NodeSource; kalau
> dpkg menolak karena bentrok file `/usr/include/node/common.gypi` dengan
> `libnode-dev` lama, buang dulu paket Node bawaan distro
> (`sudo apt remove libnode-dev libnode72 nodejs npm nodejs-doc`) baru install ulang.

Ini bagian yang paling sering salah setelah pindah ke worker mode: **kode baru tidak
aktif sampai worker di-reload.** Copy file saja tidak cukup.

```bash
cd /var/www/eproposal
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan octane:reload        # <- tanpa ini, kode lama masih dilayani
php artisan queue:restart        # <- worker queue juga menahan kode lama
```

`octane:reload` me-restart worker secara graceful — request yang sedang jalan
diselesaikan dulu, jadi tidak ada downtime. Di mode klasik (§4), dua baris terakhir
tidak diperlukan untuk Octane, tapi `queue:restart` tetap wajib.

## 9. Rollback

Kalau worker mode bermasalah dan Anda perlu cepat kembali ke kondisi aman:

```bash
sudo systemctl disable --now eproposal-octane
sudo systemctl enable --now frankenphp     # kembali ke mode klasik §4
```

Kode aplikasi tidak perlu diubah — inilah alasan §2 menyarankan mulai dari klasik.
Untuk kembali ke Nginx + PHP-FPM sepenuhnya, hentikan `frankenphp` lalu hidupkan lagi
service lama; tidak ada yang di sisi Laravel yang terkunci ke FrankenPHP.

## 10. Benchmark: klasik vs worker

### Apa yang sebenarnya diukur

Yang dipercepat worker mode adalah **bootstrap Laravel** (~20–50 ms/request) — bukan
query. Konsekuensinya:

- **Jangan** mengukur di halaman yang query-nya berat. Kalau query makan 2 detik,
  penghematan 40 ms tenggelam dan angkanya tidak mengukur FrankenPHP.
- **Jangan** mengukur mode klasik lalu menyimpulkan "FrankenPHP cepat/lambat".
  Mode klasik ≈ PHP-FPM (§2) — memang tidak ada yang berubah. Angka baru bermakna
  kalau ada **dua** pengukuran: klasik dan worker, endpoint sama, data sama.
- Volume data besar (§ seeder di bawah) menguji **database**, bukan app server.
  Itu pertanyaan yang berbeda — berguna, tapi jangan dicampur dengan pertanyaan
  "FrankenPHP cepat atau tidak".

### Jalankan dari laptop, bukan dari server

Server ini 2 core (`runtime.NumCPU=2` di log start-up). Alat benchmark memakan CPU;
kalau dijalankan di server yang sama, ia berebut CPU dengan worker yang sedang diukur
dan hasilnya bias ke bawah. Jalankan dari laptop lewat LAN.

```bash
# di laptop — oha paling ringkas; wrk / k6 / ab juga boleh
oha -z 30s -c 20 http://172.16.202.207/login
```

`/login` dipilih karena publik (tanpa sesi) dan query-nya ringan — jadi yang dominan
justru biaya bootstrap, persis yang mau diukur. Catat **req/detik** dan **latensi p95**.

Untuk halaman ber-auth (mis. `/proposal`), alat benchmark perlu cookie sesi:

```bash
# ambil cookie sekali lewat browser (DevTools → Application → Cookies →
# eproposal-session), lalu:
oha -z 30s -c 20 -H 'Cookie: eproposal-session=<isi-cookie>' http://172.16.202.207/proposal
```

### Urutan yang benar

1. **Baseline (klasik).** Pastikan `config:cache`, `route:cache`, `view:cache` sudah
   jalan — tanpa itu Anda mengukur cache miss, bukan mode server.
2. Catat angkanya.
3. Naikkan ke worker mode (§5).
4. Ukur **endpoint yang sama, data yang sama, perintah yang sama**.
5. Bandingkan. Selisihnya = nilai worker mode di aplikasi ini.

Ubah **satu variabel saja** antar pengukuran. Kalau sekalian mengganti data,
menambah index, atau mengubah `--workers`, Anda tidak lagi tahu apa yang bergerak.

### Uji volume besar (pertanyaan berbeda)

`ProposalBulkSeeder` mengisi tabel `proposal` lewat insert massal — bukan lewat
`ProposalWorkflow` seperti `ProposalSampleSeeder` (yang menulis puluhan baris per
proposal; realistis tapi tidak mungkin untuk sejuta).

```bash
BULK_PROPOSAL_COUNT=1000000 php artisan db:seed --class=ProposalBulkSeeder
```

Batasnya: data ini tanpa history/dokumen/penugasan, jadi tab **Riwayat** di antrian
(`whereHas('statusHistory')`) tetap kosong dan tidak ikut terukur. Yang terukur
`Proposal/Index` dan tab **Antrian**.

Yang akan ambruk duluan di volume ini **bukan** FrankenPHP, melainkan query — dan
tidak satupun tertolong worker mode:

| Lokasi | Masalah |
|---|---|
| `Proposal/Index.php:31`, `BaseAntrian.php:63` | `count()` penuh **tiap render**, termasuk tiap ketikan pencarian |
| `Proposal/Index.php:27`, `BaseAntrian.php:58` | `ilike '%kata%'` — wildcard di depan, index B-tree tidak terpakai → seq scan |
| `BaseAntrian.php:61` | `orderBy('updated_at')` sedangkan index hanya di `unit_sekarang`, `status`, `user_id` → sort seluruh hasil filter |
| `BaseAntrian.php:32` | `whereHas('statusHistory')` — subquery EXISTS per render |

Perbaikannya di ranah database (index trigram/GIN untuk pencarian, index
`updated_at`, buang `count()` per render, debounce input) — bukan di app server.
Bereskan itu **sebelum** menyimpulkan apa pun tentang FrankenPHP dari data sejuta baris.

## 11. Checklist

- [ ] `/usr/bin/php-zts -m` (**bukan** `php -m`) memuat `pdo`, `pdo_pgsql`, `pcntl`, `mbstring`, `intl`, `gd`, `zip`
- [ ] `upload_max_filesize`/`post_max_size` ≥ 30M di `/etc/php-zts/php.ini`
- [ ] `DOCUMENTS_PATH` = path Linux, dimiliki `www-data`
- [ ] `storage/` + `bootstrap/cache/` dimiliki `www-data`
- [ ] `php artisan storage:link` sudah dijalankan
- [ ] Service queue aktif (uji: registrasi user → email verifikasi masuk)
- [ ] Cron scheduler terpasang
- [ ] Uji upload dokumen 20 MB (raw data) → file muncul di `DOCUMENTS_PATH` → unduh dari UI
- [ ] Uji login, dashboard, dan satu alur Livewire penuh
- [ ] Alur deploy §8 didokumentasikan ke tim ops
