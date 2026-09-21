# Arsitektur & Daftar Route LaravelSmartApi

Dokumentasi routing package `starlight93/laravel-smart-api`, struktur 4 varian route, mekanisme autentikasi, query parameter, payload, perilaku transaksi database (`:commit`), serta contoh cURL.

---

## 0. Environment & Request Variables (cURL)

Definisikan variabel berikut sebelum menjalankan contoh perintah:

```bash
# Host target
export HOST="http://localhost"

# Prefix Route API Authenticated (Default: 'api', Versi Lama/Legacy: 'operation')
export API_PREFIX="api"                 # Ubah ke "operation" jika menggunakan konfigurasi lama / API_ROUTE_PREFIX=operation

# Kredensial Laradev Editor (routesEditor.php & routesDoc.php)
export LARADEV_KEY="12345"          # Nilai dari env LARADEVPASSWORD / config('editor.password')
export DEVELOPER_TOKEN="dev_backend"    # Nama dev terdaftar di config('editor.backend_devs' / 'frontend_devs' / 'owners')

# Token User API (routesApi.php)
export BEARER_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

---

## 1. Ikhtisar 4 Varian Route

Package mendaftarkan route melalui `ApiServiceProvider` dan `EditorServiceProvider`:

| Varian Route | File Sumber | Provider | Base Path / Prefix | Mekanisme Autentikasi | Fungsi Utama |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **1. API Authenticated** | `src/routesApi.php` | `ApiServiceProvider` | `/$API_PREFIX` (default: `/api`, legacy: `/operation`) | Header `Authorization: Bearer $BEARER_TOKEN` via `AuthenticateApi` | Manajemen user & CRUD RESTful Eloquent multi-level (Parent, Detail, Subdetail). |
| **2. API Public** | `src/routesApiPublic.php` | `ApiServiceProvider` | `/public` | Tanpa auth (Public) | Menjalankan method dinamis `public_{function}` di `App\Models\CustomModels\{modelname}`. |
| **3. Documentation** | `src/routesDoc.php` | `EditorServiceProvider` | `/docs` | Form payload `password=$LARADEV_KEY` pada endpoint sensitif | Visual diff developer activities, uploader data, log viewer, schema, scheduler info. |
| **4. Laradev Editor** | `src/routesEditor.php` | `EditorServiceProvider` | `/laradev` | Header `laradev: $LARADEV_KEY` & `developer-token: $DEVELOPER_TOKEN` via `EditorMiddleware` | GUI & Backend studio: kelola database, migration, model generator, test runner, query runner, file manager. |

---

## 2. Rincian Varian Non-Editor

### A. Varian 1: API Authenticated (`routesApi.php`)

#### Mekanisme Auth & Prefix Configuration
- Base path dikontrol oleh `config('api.route_prefix')` (environment variable `API_ROUTE_PREFIX`).
- **Default**: `/api` (contoh: `/$HOST/api/{modelname}`)
- **Versi Lama / Legacy Project**: Menggunakan `/operation` (contoh: `/$HOST/operation/{modelname}`) dengan mengatur `.env`:
  ```env
  API_ROUTE_PREFIX=operation
  ```
- Endpoint login publik (`POST /login`) mengembalikan JWT/Sanctum bearer token.
- Endpoint terproteksi membutuhkan header: `Authorization: Bearer $BEARER_TOKEN`.

#### Contoh cURL:
```bash
# 1. Login & Dapatkan Token
curl -s -X POST "$HOST/login" \
     -H "Content-Type: application/json" \
     -d '{
       "username": "admin",
       "password": "secretpassword"
     }'

# 2. Get Profil User
curl -s -X GET "$HOST/user" \
     -H "Authorization: Bearer $BEARER_TOKEN"

# 3. List Data Model (Pagination, Search, Sort) - Default Prefix (/api/products)
curl -s -X GET "$HOST/$API_PREFIX/products?page=1&per_page=10&sort=id:desc" \
     -H "Authorization: Bearer $BEARER_TOKEN"

# 4. Mode Legacy Prefix (/operation/products)
curl -s -X GET "$HOST/operation/products?page=1&per_page=10&sort=id:desc" \
     -H "Authorization: Bearer $BEARER_TOKEN"

# 5. Get Single Data by ID
curl -s -X GET "$HOST/$API_PREFIX/products/12" \
     -H "Authorization: Bearer $BEARER_TOKEN"

# 6. Create Data (Parent + Relasi Details)
curl -s -X POST "$HOST/$API_PREFIX/orders" \
     -H "Authorization: Bearer $BEARER_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "customer_id": 5,
       "total_amount": 150000,
       "order_details": [
         {"product_id": 1, "qty": 2, "price": 50000},
         {"product_id": 2, "qty": 1, "price": 50000}
       ]
     }'

# 7. Update Data
curl -s -X PUT "$HOST/$API_PREFIX/products/12" \
     -H "Authorization: Bearer $BEARER_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name": "Produk Baru", "price": 75000}'

# 8. Delete Data
curl -s -X DELETE "$HOST/$API_PREFIX/products/12" \
     -H "Authorization: Bearer $BEARER_TOKEN"

# 9. Upload Temporary File (Base64 Blob)
curl -s -X POST "$HOST/upload" \
     -H "Authorization: Bearer $BEARER_TOKEN" \
     -F "file=@/path/to/file.pdf"
```

---

### B. Varian 2: API Public (`routesApiPublic.php`)

#### Mekanisme Auth
- Bebas diakses tanpa token autentikasi.
- Otomatis memanggil function berprefix `public_` pada class `App\Models\CustomModels\{modelname}`.

#### Contoh cURL:
```bash
# Memanggil App\Models\CustomModels\Product::public_catalog($req)
curl -s -X GET "$HOST/public/Product/catalog?category=beverage"

# Memanggil via POST
curl -s -X POST "$HOST/public/Report/generate" \
     -H "Content-Type: application/json" \
     -d '{"period": "2024-05", "type": "summary"}'
```

---

### C. Varian 3: Documentation & Developer Tools (`routesDoc.php`)

#### Mekanisme Auth
- Endpoint publik: `/docs/`, `/docs/schema`, `/docs/api-request`, `/docs/scheduler`, `/docs/menu`.
- Endpoint terproteksi (`/docs/activities`, `/docs/uploader`, `/docs/logs`): membutuhkan form field `password=$LARADEV_KEY`.

#### Contoh cURL:
```bash
# 1. Info Sistem & Tautan Menu (Public)
curl -s -X GET "$HOST/docs"

# 2. Schema Model Ter-generate (Public)
curl -s -X GET "$HOST/docs/schema"

# 3. Riwayat Aktivitas Developer / Diff (Protected)
curl -s -X POST "$HOST/docs/activities" \
     -d "password=$LARADEV_KEY"

# 4. Antarmuka Bulk Uploader (Protected)
curl -s -X POST "$HOST/docs/uploader" \
     -d "password=$LARADEV_KEY"
```

---

## 3. Analisis Mendalam: Laradev Editor (`routesEditor.php`)

Prefix Base: `/laradev`  
Middleware: `EditorMiddleware`

### A. Mekanisme Autentikasi `EditorMiddleware`
Setiap request wajib mengirimkan 2 header (kecuali route `/laradev/connect` dan `/laradev/assets/*`):
1. `laradev`: Harus sama persis dengan `config('editor.password')` (default env `LARADEVPASSWORD`).
2. `developer-token`: Harus sesuai nama developer yang terdaftar pada config `editor.frontend_devs`, `editor.backend_devs`, atau `editor.owners`.

---

### B. Query Runner & Aturan Transaksi Non-Select (`:commit`)

Endpoint `POST /laradev/run-query` menangani eksekusi query SQL ad-hoc dengan mekanisme keamanan transaksi otomatis:

#### 1. Mekanisme Mutasi Database (`INSERT`, `UPDATE`, `DELETE`)
- Jika statement query mengandung string mutasi data (`insert into`, `update `, `delete from`), controller secara otomatis memulai database transaction (`DB::beginTransaction()`).
- **Dry-run / Rollback Default**: Jika query dijalankan **tanpa** string `:commit`, maka seluruh perubahan data akan di-**ROLLBACK** (`DB::rollback()`).
- **Eksekusi Permanen dengan `:commit`**: Jika string statement mengandung penanda `:commit` (case-insensitive):
  1. Penanda `:commit` dibersihkan dari sintaks SQL (`str_ireplace(':commit', '', $state)`).
  2. SQL dieksekusi via `DB::unprepared()`.
  3. Perubahan disimpan permanen ke database via `DB::commit()`.
- Mendukung multi-query yang dipisahkan oleh tanda titik koma (`;`).

#### 2. Contoh cURL Eksekusi Query

```bash
# A. Query SELECT Biasa (Read-Only)
curl -s -X POST "$HOST/laradev/run-query" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "statement": "SELECT id, name, email FROM users LIMIT 5"
     }'

# B. Query UPDATE dengan Simulasi / Dry-Run (Otomatis ROLLBACK karena tanpa :commit)
curl -s -X POST "$HOST/laradev/run-query" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "statement": "UPDATE users SET status = 0 WHERE id = 10"
     }'

# C. Query UPDATE Non-Select Permanen (Menggunakan :commit)
curl -s -X POST "$HOST/laradev/run-query" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "statement": "UPDATE users SET status = 1 WHERE id = 10 :commit"
     }'

# D. Multi-statement Non-Select Permanen dengan :commit
curl -s -X POST "$HOST/laradev/run-query" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "statement": "INSERT INTO logs (action) VALUES (\"test\"); UPDATE settings SET val = 1 WHERE key = \"active\" :commit"
     }'
```

---

### C. Detail Endpoint Model & Parameter Query (`custom=true`, `basic=true`, `script_only=1`)

Pada endpoint `GET /laradev/models/{table}`, controller membaca file model Eloquent yang di-generate maupun yang di-customize:

| Query Parameter | Tipe | Efek Response |
| :--- | :--- | :--- |
| *(tanpa param)* | - | Mengembalikan JSON metadata: `last_update`, `table`, `columns`, dan `text` (isi file CustomModel). |
| `custom=true` / `custom=1` | Boolean | Mengembalikan **raw string kode PHP** dari file `app/Models/CustomModels/{table}.php`. |
| `basic=true` / `basic=1` | Boolean | Mengembalikan **raw string kode PHP** dari file generator dasar `app/Models/GeneratedModels/{table}.php`. |
| `script_only=1` | Boolean | Mengembalikan JSON object berisi kode kedua file: `{"basic": "...", "custom": "..."}`. |
| `password=...` | String | Wajib dikirimkan jika class model memiliki property `$password` terproteksi. |

#### Contoh cURL Pembacaan Model:
```bash
# 1. Ambil Metadata & Script Custom Model (Default JSON)
curl -s -X GET "$HOST/laradev/models/users" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"

# 2. Ambil RAW Isi File Custom Model (custom=true)
curl -s -X GET "$HOST/laradev/models/users?custom=true" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"

# 3. Ambil RAW Isi File Generated/Basic Model (basic=true)
curl -s -X GET "$HOST/laradev/models/users?basic=true" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"

# 4. Ambil Kedua Skrip Basic & Custom Sekaligus (script_only=1)
curl -s -X GET "$HOST/laradev/models/users?script_only=1" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"
```

---

### D. Daftar Lengkap Endpoint Laradev Editor Beserta Query Param & Variannya

#### 1. Database Management
| Method | Path & Query Parameters | Action | Deskripsi & Variant |
| :--- | :--- | :--- | :--- |
| `GET` | `/databases` | `databaseCheck` | Cek status database. Param: `?db_autocreate=true` (auto-create jika DB belum ada), `?host=...`, `?driver=...`, `?database=...`. |
| `POST` | `/databases` | `createDatabase` | Buat database baru. Body JSON: `{"name": "nama_db"}`. |
| `DELETE` | `/databases/{db}` | `deleteDatabase` | Drop database fisik `{db}`. |
| `POST` | `/database-create-local` | `databaseCreateLocal` | Buat database lokal + migrasi/seeding. Param: `db_migrate=true`, `db_fresh=true`, `db_seed=true`, `db_passport=true`. |

```bash
# Cek Database dengan Auto Create
curl -s -X GET "$HOST/laradev/databases?db_autocreate=true" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"

# Buat Database Baru
curl -s -X POST "$HOST/laradev/databases" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name": "db_ecommerce_dev"}'
```

---

#### 2. Table, Foreign Keys, & Triggers Management
| Method | Path & Query Parameters | Action | Deskripsi & Variant |
| :--- | :--- | :--- | :--- |
| `GET` | `/tables` | `readTables` | Ambil list semua tabel. Param: `?details=true` (sertakan detail kolom & index). |
| `GET` | `/tables/{table}` | `readTables` | Ambil struktur kolom & skema tabel `{table}`. |
| `POST` | `/tables` | `createTables` | Generate tabel baru berserta skema kolom. Body JSON: `{"table": "orders", "columns": [...]}`. |
| `DELETE` | `/tables/{table}` | `deleteTables` | Drop tabel fisik. Param: `?models=true` (hapus juga file Model Eloquent terkait). |
| `PUT` | `/tables/{table}/trigger` | `makeTrigger` | Buat trigger database. Body: `time` (`before`/`after`), `event` (`insert`/`update`/`delete`), `script` (SQL body). |
| `DELETE` | `/tables/{table}/trigger` | `makeTrigger` | Hapus trigger database. Body: `time`, `event`. |
| `GET` | `/realfk` | `getPhysicalForeignKeys` | Ambil data relasi Foreign Key fisik database. |
| `GET` | `/dorealfk` | `setPhysicalForeignKeys` | Terapkan foreign key fisik. Param: `?drop=true` (drop semua foreign key fisik). |

```bash
# Ambil Semua Tabel beserta Detail Skema
curl -s -X GET "$HOST/laradev/tables?details=true" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"

# Drop Tabel & Hapus File Modelnya Sekaligus
curl -s -X DELETE "$HOST/laradev/tables/temporary_logs?models=true" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"
```

---

#### 3. Model Generator & Updater
| Method | Path & Query Parameters | Action | Deskripsi & Variant |
| :--- | :--- | :--- | :--- |
| `GET` | `/models` | `readMigrationsOrCache` | Ambil daftar konfigurasi model dari cache/migration. |
| `GET` | `/models/{table}` | `readModelsOne` | Ambil data model. Param: `?custom=true`, `?basic=true`, `?script_only=1`, `password=...`. |
| `POST` | `/models` | `createModels` | Generate seluruh Model Eloquent batch. Param: `?fresh=true`, `?rewrite_custom=true`. |
| `POST` | `/models/{table}` | `createModels` | Generate Model Eloquent khusus `{table}`. Param: `?rewrite_custom=true`. |
| `PUT` | `/models/{table}` | `updateModelsOne` | Simpan kode PHP CustomModel. Body JSON: `{"text": "<?php ..."}` (linter `php -l` dijalankan otomatis). |

```bash
# Generate Ulang Seluruh Model Eloquent & Rewrite Custom Model
curl -s -X POST "$HOST/laradev/models?fresh=true&rewrite_custom=true" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"

# Update Kode File Custom Model
curl -s -X PUT "$HOST/laradev/models/users" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "text": "<?php\nnamespace App\\Models\\CustomModels;\nuse App\\Models\\GeneratedModels\\users as GeneratedUsers;\nclass users extends GeneratedUsers {}\n"
     }'
```

---

#### 4. Migrations, Alter & Test Runner
| Method | Path & Query Parameters | Action | Deskripsi & Variant |
| :--- | :--- | :--- | :--- |
| `GET` | `/migrations` | `readMigrations` | Baca daftar file migration Laravel. |
| `GET` | `/migrations/{table}` | `readMigrations` | Baca isi file migration tabel `{table}`. |
| `POST` | `/migrations` | `editMigrations` | Tulis/generate migration baru. Body JSON: `{"modul": "create_products_table", "text": "..."}`. |
| `POST` | `/migrate` | `migrateDefault` | Jalankan Artisan migrate. Param: `?fresh=true` (`migrate:fresh`), `?seed=true` (`--seed`), `?passport=true`. |
| `GET` | `/migrate/{table}` | `doMigrate` | Eksekusi migration spesifik. Param: `?down=true` (jalankan `down()`), `?alter=true` (eksekusi alter file). |
| `GET` | `/alter/{table}` | `readAlter` | Ambil skrip SQL alter table. |
| `PUT` | `/alter/{table}` | `editAlter` | Simpan & jalankan alter file. Body JSON: `{"text": "..."}`. |
| `GET` | `/tests/{table}` | `readTest` | Baca file PHPUnit test tabel `{table}`. |
| `PUT` | `/tests/{table}` | `editTest` | Tulis/update file PHPUnit test tabel `{table}`. Body JSON: `{"text": "..."}`. |
| `GET` | `/do-test/{table}` | `doTest` | Jalankan PHPUnit test tabel `{table}` secara real-time. |

```bash
# Jalankan Migrate Fresh + Seeder
curl -s -X POST "$HOST/laradev/migrate?fresh=true&seed=true" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"

# Rollback Migrasi Khusus 1 Tabel (down)
curl -s -X GET "$HOST/laradev/migrate/orders?down=true" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"
```

---

#### 5. File Manager & Code Editor (Core, JS, Blade, Frontend)
| Method | Path | Action | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/edit-core-file/{file}` | `editCoreFile` | Baca file PHP di direktori `app/Cores/`. |
| `POST` | `/save-core-file/{file}` | `saveCoreFile` | Simpan perubahan file di `app/Cores/`. Body: `{"text": "..."}`. |
| `GET` | `/edit-js-file/{file}` | `editJsFile` | Baca file Javascript di `resources/js/projects/`. |
| `POST` | `/save-js-file/{file}` | `saveJsFile` | Simpan file Javascript di `resources/js/projects/`. Body: `{"text": "..."}`. |
| `GET` | `/edit-blade-file/{file}` | `editBladeFile` | Baca file template Blade di `resources/views/projects/`. |
| `POST` | `/save-blade-file/{file}` | `saveBladeFile` | Simpan file template Blade. Body: `{"text": "..."}`. |

```bash
# Baca File Core Logic
curl -s -X GET "$HOST/laradev/edit-core-file/OrderProcessor.php" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"
```

---

#### 6. Database Utilities, Backup, Uploader & Sockets
| Method | Path & Query Parameters | Action | Deskripsi & Variant |
| :--- | :--- | :--- | :--- |
| `GET` | `/queries10rows/{table}` | `queries10rows` | Ambil preview 10 baris pertama tabel. Param: `?json=true` (format JSON). |
| `GET` | `/run-backup` | `runBackup` | Jalankan proses backup database ke file dump SQL. |
| `GET` | `/backup` | `getBackup` | Download file backup database. Param: `key=$LARADEV_KEY`, `?fresh=true`. |
| `POST` | `/upload-test` | `uploadTest` | Simulasi uji coba bulk data upload. |
| `POST` | `/upload-with-create` | `uploadWithCreate` | Upload data bulk sekaligus pembuatan tabel penampung. |
| `POST` | `/upload-lengkapi` | `uploadLengkapi` | Melengkapi foreign data upload. |
| `POST` | `/upload-template` | `uploadTemplate` | Simpan & update konfigurasi template upload mapping. |
| `POST` | `/paramaker` | `paramaker` | Simpan custom prepared queries & parameter preset ke database. |
| `POST` | `/socket-connect` | `socketConnect` | Registrasi koneksi WebSocket real-time untuk channel notifikasi. |
| `POST` | `/connect` | `connect` | Verifikasi awal koneksi GUI Laradev. Body JSON: `{"password": "$LARADEV_KEY"}`. |

```bash
# Preview 10 Baris Tabel Format JSON
curl -s -X GET "$HOST/laradev/queries10rows/users?json=true" \
     -H "laradev: $LARADEV_KEY" \
     -H "developer-token: $DEVELOPER_TOKEN"

# Download File Backup Database
curl -s -X GET "$HOST/laradev/backup?key=$LARADEV_KEY&fresh=true" \
     -o database_backup.sql
```
