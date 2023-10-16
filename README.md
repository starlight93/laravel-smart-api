# Laravel/Lumen Online Editor & Rest API Generator
## Online Editor
Fitur editing project laravel atau lumen secara online dengan protocol HTTP(s) dengan editor online: https://ngopi.netlify.app atau https://ngopi.vercel.app secara aman dan sangat cepat.


## Background
Editor online untuk development sangat bermanfaat untuk mengurangi kelambatan development, publikasi aplikasi, bahkan bisa langsung dilakukan di server staging hingga umur laptop developer bisa lebih lama karena tak pernah menjalankan versi localhost.

Berbagai macam editor online yang pernah kita temui:
1. Online Gitlab/Github Editor yang dapat dimanfaatkan hingga melakukan committing tanpa harus melakukan push dari local. Server git sudah memfasilitasi hal ini dengan keterbatasan editornya (rata-rata menggunakan library [Monaco Editor](https://microsoft.github.io/monaco-editor/) yakni pendukung utama core dari VSCode)
2. Ekstensi-ekstensi pada editor misal VSCode, Atom, Sublime, atau yang lain berbasis FTP. Developer dapat melakukan editing project dengan editor andalannya secara online dengan protokol FTP. Namun sayangnya tracking dari perubahan file adalah issue besar, belum lagi jika banyak developer yang mengotak-atik file yang sama. Refresh dari list file pun tidak realtime.
3. Ekstensi lain dengan protokol berbeda yakni SSH. Developer dapat langsung mengotak-atik file di server dengan protocol secure ini dengan sesuka hati sebagaimana melakukan editting di local. Ini cara sebagaimana sultan merasa menjadi pemilik folder project pada si server. Namun jika kita memberikan akses SSH hanya untuk _edit online_ ke developer yang tidak seharusnya mendapat hak tinggi ke server, rasanya cukup mengerikan.

Ketiganya sangat bermanfaat terutama jika project dikerjakan tidak secara keroyokan alias single fighter. Sedangkan cara normal yakni localhost dan git dipadu dengan CI/CD untuk deployment ke server sebenarnya sudah powerful dan ideal. Namun untuk bebarapa kasus khusus, CI/CD memerlukan cost besar entah di biaya service maupun kesukarelaan untuk menukar security key agar dapat masuk ke server tujuan. Selain itu, pengumpulan garapan dari banyak developer yang sekiranya sudah mumpuni (tanpa perlu banyak direview kodingnya) menjadi sangat lambat. Sehingga dari development ke publikasi hingga review para tester di sisi aplikasi penuh menjadi lambat.

Pada akhirnya package ini menjadi solusi lain bagi kita untuk mengotak atik project dengan mudah dan cepat, aman (karena protocol HTTP(s) seperti web pada umumnya), realtime, dan pastinya membatasi gerak-gerik tidak penting dari developer. Apa maksudnya membatasi gerak gerik? Editor online ini memiliki pattern yang mudah dipahami dengan mengarahkan developer ke pattern kerja yang khusus (tidak dapat secara bebas create file).


## Online Editor API
Fitur:

- [x] Editor online dibuat menggunakan [Monaco Editor](https://microsoft.github.io/monaco-editor/) (core dari vscode) yang dicustomisasi agar ringan dan semakin meningkatkan produktivitas
- [x] Menuntun pattern memulai project dengan membatasi developer untuk membuat file sesuai role
- [x] Membuat, edit, dan eksekusi migration file (up - down) tanpa command line
- [x] Membuat, edit, dan eksekusi alter file (up - down) tanpa command line
- [x] Auto create template model yang relevan dengan migration dan bisa diedit sesuai kebutuhan seperti role, events CRUD, format response.
- [x] Visualisasi view data 10 rows terakhir
- [x] Visualisasi view data sesuai query statement di dalam editor (run)
- [x] Run Query (default rollback) atau commit untuk create, update, delete via editor
- [x] Data Editor (CRUD + commit) dengan mini DB Client di dalam editor
- [x] Membuat, edit, dan eksekusi hingga preview result Testing file
- [x] Membuat, edit, dan publish blade file dan javascript file dengan
- [x] Fitur truncate table
- [x] Membuat, edit, dan autoload class untuk keperluan selain CRUD, misal logic kompleks yang disendirikan di suatu class di dalam folder app/Cores
- [x] Fitur backup file yang editable (non-generated files) dan database
- [x] Fitur restore backup file hingga database
- [x] Fitur sinkronisasi antar project dengan 1 sumber (replicate project)
- [x] Visualisasi tracking file changes (mini diff file seperti git)
- [x] Fitur uploader data copy paste dari excel dan query-able sebelum diupload
- [x] Aman dan dapat dimatikan fitur editor ini jika project sudah running tanpa perlu editing lagi

## Instalasi:
 - Create laravel project baru seperti di  [Tutorial Official](https://laravel.com/docs/master/installation#your-first-laravel-project)
 - Masuk ke root project yang baru dibuat
 - Install package dengan composer:
 ```sh
    composer require starlight93/laravel-smart-api
```
 - Daftarkan Provider Editor dan API Generator
- Laravel: buka file config/app.php, tambahkan di bagian key: `providers`
 `Starlight93\LaravelSmartApi\ApiServiceProvider::class`

- Lumen: buka file bootstrap/app.php tambahkan baris berikut:
```php
   $app->register(Starlight93\LaravelSmartApi\ApiServiceProvider::class);
```
   Untuk lumen tak perlu mengaktifkan withFacades() dan withEloquent() karena akan auto dinyalakan oleh provider generator

## Start Project:
 - Matikan tracking file permission di git ROOT Project (jika project anda sudah di `git init` sebelumnya)
 ```sh
    git config core.fileMode false
 ```
 - Buka console command line posisi di ROOT Project, dan lakukan step berikut:
 ```sh
    php artisan project:start
 ```

 ## Environment Variable
 Lihat vendor/starlight93/config/ untuk lebih lengkapnya. Variable di bawah dapat dipasang di .env folder project

### Basic Env Key
| Key | Description | Default |
| --- | --- | --- |
| EDITOR_PASSWORD | Password untuk diisikan di header request dengan key header: laradev, cek [Middleware](src/Http/Middleware/EditorMiddleware.php?plain=1#L16) | 12345 |
| EDITOR_FRONTENDERS | List user untuk developer frontend yang akan hanya mendapat akses blade dan js di editor online, misal: 001-dev-fe,002-dev-fe,dst cek [Middleware](src/Http/Middleware/EditorMiddleware.php?plain=1#L19) | - |
| EDITOR_BACKENDERS | List user untuk developer backend yang akan hanya mendapat akses migration,model,dan bebarapa hal terkait Backend saja di editor online cek [Middleware](src/Http/Middleware/EditorMiddleware.php?plain=1#L20)| - |
| EDITOR_OWNERS | List user untuk root dveloper yang akan dapat melihat semua fitur [Middleware](src/Http/Middleware/EditorMiddleware.php?plain=1#L21)| dev-owner |
| GOOGLE_CLIENT_ID | Untuk keperluan config google auth (Laravel Socialite) [Usage](src/ApiServiceProvider.php?plain=1#L199)| - |
| GOOGLE_CLIENT_SECRET | Untuk keperluan config google auth (Laravel Socialite) [Usage](src/ApiServiceProvider.php?plain=1#L200)| - |
| LOG_SENDER | Untuk logging websocket [Usage](src/Helpers/EditorFunc.php?plain=1#207)| - |
| LOG_PATH | Untuk path channel logging websocket [Usage](src/Helpers/EditorFunc.php?plain=1#205)| - |
| CLIENT_CHANNEL | Untuk trigger send websocket ke listener lain seperti frontend misalnya [Usage](src/Helpers/EditorFunc.php?plain=1#205)| - |
| API_ROUTE_PREFIX | Prefix Endpoint route untuk restful API [Usage](config/api.php?plain=1#6)| api |
| API_USER_TABLE | Default User's table name [Usage](config/api.php?plain=1#4)| users |
| API_PROVIDER | Register Provider Class Name Tambahan, contoh "\\App\\Your\\Class" [Usage](config/api.php?plain=1#7)| - |

 - Setelah melakukan pengaturan di .env file, usahakan setting database connection telah benar, maka aplikasi akan mampu melakukan create models (di /app/Models/CustomModels) secara otomatis sesuai isi table di database (reverse engineering). Lakukan command CLI berikut:
 ```sh
    php artisan project:model
 ```

## Rest API Generator
Dengan membuat file migration, atau struktur DB yang ada maka akan mendapatkan fitur lengkap berikut:

- [x] Create Read Update Delete (CRUD) Rest API single, detail, maupun sub details tanpa batasan child
- [x] Auto Join Relationship dengan `src` dan `fk` column fake-relation (comment table)
- [x] Fitur search, pagination, filter, ordering, dan standard reading API
- [x] Auto validation sesuai tipe dan length data di kolom database
- [x] Auto create Generated Models File sesuai DB (reverse Engineering)
- [x] Auto create default Custom Models File (editable) sesuai DB (reverse Engineering)
- [x] Auto create Visualisasi Database + Relasi sesuai DB (reverse Engineering)
- [x] Auto create mini API Documentation + Relasi sesuai DB (reverse Engineering)
- [x] Error logging ke tabel dan notifikasi
- [x] Dapat berjalan dengan pattern/koding di dalam project yang lain (tanpa generator)
- [x] Zero Config JWT Auth
- [x] Zero Controller
- [x] Zero Config routing
- [x] Zero Config Schedulers
- [ ] Multi .env file sesuai sub domain atau port
- [ ] Banyak helper yang sangat sering digunakan di project seperti export pdf,excel hingga direct printing


