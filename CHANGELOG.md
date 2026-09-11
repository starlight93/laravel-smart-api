# Changelog

Semua perubahan penting pada package ini dicatat di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan versioning mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [Unreleased]

### Added
- Auth driver switchable lewat `.env`: `API_AUTH_DRIVER=jwt|sanctum|passport`. Package tidak lagi
  wajib memakai JWT bawaan; `sanctum`/`passport` memakai guard, provider, dan model User aplikasi host.
  Config baru di `config/api.php`: `api.auth.driver`, `api.auth.guard`, `api.auth.override_config`,
  `api.auth.token_name` (env: `API_AUTH_DRIVER`, `API_AUTH_GUARD`, `API_AUTH_OVERRIDE_CONFIG`,
  `API_AUTH_TOKEN_NAME`).
- Helper driver-agnostic di `ApiFunc`: `authDriver()`, `authGuard()`, `guard()`,
  `overridesAuthConfig()`, `issueToken(array $credentials)`, `revokeToken()`, `tokenTtlMinutes()`.
  `issueToken()` lempar `RuntimeException` bila model User host belum memakai trait `HasApiTokens`
  saat driver `sanctum`/`passport`.
- `Http/Middleware/AuthenticateApi` — pengganti `SocialiteMiddleware`. Resolve user lewat guard aktif
  (guard membaca bearer token sendiri), 401 bila gagal, lalu `auth()->shouldUse()` agar `Auth::user()`
  di `ApiController`/`Logger`/`Cryptor` konsisten dengan guard package.
- `project:start --auth=jwt|sanctum|passport`; tanpa flag command bertanya interaktif dan memperingatkan
  bila `laravel/sanctum` atau `laravel/passport` belum terpasang.
- Key `API_AUTH_*` beserta komentarnya ikut ditulis `project:env`.

### Changed
- **BREAKING** `laravel/socialite` dihapus dari dependency. `src/routesSocialite.php`,
  `Http/Middleware/SocialiteMiddleware.php`, registrasi `SocialiteServiceProvider`, alias `Socialite`,
  override `services.google`, serta env `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` dihapus.
- **BREAKING** Route group utama sekarang memakai `AuthenticateApi::class`, bukan `SocialiteMiddleware::class`.
- `overrideConfigs()` hanya menimpa `auth.guards`, `auth.providers`, dan `auth.defaults` bila
  `Api::overridesAuthConfig()` true (default: hanya driver `jwt`). Dengan sanctum/passport config auth
  aplikasi host dibiarkan utuh.
- `UserController@login` memakai `Api::issueToken()`, `@logout` memakai `Api::revokeToken()`,
  `expires_in_mins` memakai `Api::tokenTtlMinutes()` (sebelumnya hardcode `config('jwt.ttl')`).
  `@user`, `@changePassword`, `@unlockScreen` memakai `Api::guard()->user()`.
- `Tymon\JWTAuth\Providers\LaravelServiceProvider` / `LumenServiceProvider` hanya diregistrasi saat
  driver `jwt`. `laravel/sanctum` dan `laravel/passport` ditambahkan sebagai `suggest`.
- `project:start` tidak menimpa `API_AUTH_DRIVER` yang sudah ada di `.env` kecuali `--auth=` diberikan,
  dan `jwt:secret` hanya jalan saat driver `jwt` + `JWT_SECRET` kosong (sebelumnya dipanggil dua kali
  tanpa syarat).
- `project:start --migrate` kini minta konfirmasi karena `migrate:refresh` men-DROP tabel default.

### Fixed
- `config(['sanctum'=>[]])` sebelumnya dijalankan tanpa syarat saat driver jwt sehingga config sanctum
  aplikasi host (stateful domains, guard, expiration) dikosongkan diam-diam. Sekarang hanya jalan bila
  `Laravel\Sanctum\Sanctum` tidak ada.
- `UserController@register` memakai `unique:'.config('api.user_table')`, sebelumnya hardcode
  `unique:default_users` sehingga salah tabel saat `API_USER_TABLE` diubah.

## [2.0.0] - 2025-11-05

Rilis kompatibilitas Laravel 11/12/13. Breaking: dukungan Laravel <= 10 dihentikan.

### Added
- `EditorFunc::schemaManager($connection = null)` — membangun Doctrine DBAL `SchemaManager`
  dari config koneksi Laravel, pengganti `Connection::getDoctrineSchemaManager()` yang dihapus
  di Laravel 11. Hasilnya di-cache per nama koneksi dan sudah meregistrasi mapping
  `enum` -> `string`.
- `EditorFunc::serverSchemaManager($connection = null)` — koneksi DBAL tanpa database terpilih,
  dipakai untuk `listDatabases()` / `createDatabase()` / `dropDatabase()`.
- `tests/schema_shim_check.php` — self-check assert-based untuk kedua helper di atas.
  Jalankan: `SHIM_DSN='pgsql;host;port;db;user;pass' php artisan tinker --execute="require 'vendor/starlight93/laravel-smart-api/tests/schema_shim_check.php';"`
- Batasan `php` dan `illuminate/support` eksplisit di `composer.json`.

### Changed
- **BREAKING** Minimum Laravel 11 (`illuminate/support ^11|^12|^13`), minimum PHP 8.2.
- `kitloong/laravel-migrations-generator` `^6.11` -> `^7.4` (v6 mentok di Laravel 10).
- `rap2hpoutre/laravel-log-viewer` `^2.3` -> `^3.1` (v2 mentok di Laravel 11).
- `staudenmeir/laravel-cte` `^1.7` -> `^1.13` (driver Laravel 12/13).
- `tymon/jwt-auth` `^2.0` -> `^2.2`.
- Seluruh pemanggilan `DB::getDoctrineSchemaManager()` di `EditorController` (14 titik) diganti
  `Ed::schemaManager()` / `Ed::serverSchemaManager()`.
- Pemanggilan `registerDoctrineTypeMapping('enum','string')` yang tersebar di controller dihapus;
  sekarang terpusat di `EditorFunc::schemaManager()`.
- Instalasi tidak lagi butuh registrasi provider manual — pakai Laravel package auto-discovery
  (`extra.laravel.providers`). Instruksi `config/app.php` di README dihapus untuk Laravel 11+.

### Fixed
- `EditorServiceProvider::overrideConfigs()` membaca `config("colrs.paths")` (salah ketik) sehingga
  `cors.paths` bawaan aplikasi tertimpa jadi `["*/*"]` saja. Sekarang membaca `cors.paths` yang benar,
  menambahkan `*/*`, dan mende-duplikasi hasilnya.

### Notes
- `doctrine/dbal ^3.6` masih dibutuhkan karena introspeksi schema editor memakai object model
  Doctrine (`Table`, `Column`, `Index`, `ForeignKeyConstraint`). Untuk melepasnya, port
  `EditorController::getFullTables()` ke `Schema::getTables()/getColumns()/getIndexes()/getForeignKeys()`
  bawaan Laravel 11+.
- `laravel/socialite` menarik `league/oauth1-client` yang membatasi `guzzlehttp/guzzle` ke `^7`,
  jadi aplikasi host akan memakai Guzzle 7 walau Laravel 13 mengizinkan Guzzle 8.

## [1.x] - sebelum 2025-11

Riwayat sebelum penomoran versi; lihat `git log`. Ringkas:
- Editor online (Monaco) + generator REST API untuk Laravel/Lumen.
- Generator model, migration, test; backup; scheduler dari tabel `default_schedules`.
- Auth JWT (`tymon/jwt-auth`) + Socialite, log viewer, API docs di `/docs`.

[Unreleased]: https://github.com/starlight93/laravel-smart-api/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/starlight93/laravel-smart-api/releases/tag/v2.0.0
