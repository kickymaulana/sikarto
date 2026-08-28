# AGENTS.md

SI KARTO — Sistem Kalibrasi Toleransi Operasional Alat Ukur Rutin Bulanan. PRD lengkap: `docs/PRD.md`. Ikuti schema & business logic di sana.

## Stack
- Laravel 13 + Inertia **3** (bukan v2) + Vue 3 + TypeScript + Varlet UI + Ziggy + Spatie Permission
- Setup **bersih** (tanpa starter kit, tanpa Tailwind). Jangan tambah Tailwind.
- Varlet full import (`.use(Varlet)` di `resources/js/app.ts`), bukan on-demand.

## Commands
- Dev: `npm run dev` + `php artisan serve`
- Build: `npm run build` (setelah frontend berubah)
- Typecheck: `npm run typecheck` (`vue-tsc --noEmit`) — jalankan sebelum build
- Test: `php artisan test`
- Code style: `vendor/bin/pint`

## Struktur frontend
- Halaman: `resources/js/Pages/**/*.vue` (auto-resolve oleh `app.ts`)
- Shared props type: `resources/js/types/index.d.ts` (module augmentation `@inertiajs/core` PageProps). Tambah prop baru di `HandleInertiaRequests::share()` DAN di file types ini — typecheck fail kalau nggak sinkron.
- Routes di JS pakai Ziggy (`route()`), di-inject via `@routes` di `resources/views/app.blade.php`.
- Varlet full import = bundle ~887 KB. Jika bundle terlalu besar, upgrade ke on-demand via `@varlet/import-resolver` + unplugin.

## Desain
- Referensi: aplikasi SUKIRMAN (`D:\Apache24\htdocs\sukirman`) — mobile-first "android-layout" + Varlet MD3 light (`StyleProvider(Themes.md3Light)` di `app.ts`).
- **Tema orange**: primary `#fb8c00`, peach fill `#fdf0ea`, bg `#f8fafc`, kartu putih radius 16 border `#f1f5f9`. Tombol utama gradient pill `#fb8c00→#f57c00`.
- **Satu warna di semua CRUD**: appbar = FAB = tombol primary, semuanya orange. `StyleProvider` override `'color-primary': '#fb8c00'` di `app.ts` (wajib — tanpa itu `Themes.md3Light` bikin primary ungu `#6750A4`). AppBar pakai gradient `#fb8c00→#f57c00`, teks/ikon putih; search mode tetap putih.
- Layout helper ada di `app.css`: `.android-layout`, `.android-content`, `.top-app-bar`, `.white-card`. Halaman baru wajib pakai pola ini + AppLayout (bottom nav Beranda/Pengujian/Laporan + FAB).
- **Master menu** (`/masters` → `Masters/Menu.vue`) = landing card grid semua entity (Factory/Departemen/Jenis/Merk/Kapasitas/Limit + Alat Ukur). Route list: `masters.menu`, `masters.index`.
- Master CRUD pakai **halaman terpisah** (`/masters/{entity}/create`, `/{entity}/{id}/edit`) — bukan inline form. Contoh: `Masters/Form.vue`, `Instruments/Form.vue`. List = row-card **klik-card → halaman edit**. Tombol Edit/Hapus TIDAK di list; Hapus ada di halaman edit.
- List (master & instruments) = android pattern: **app-bar search icon → toggles search input** (`composables/search.ts` shared `searchState`), `var-pull-refresh` + `var-list` infinite scroll (load-more via `router.get` + `only: [...]` + merge props). Contoh `Masters/Index.vue`.
- **AppBar = satu baris**: judul halaman (dari `pageTitle` map di `AppLayout.vue`, dinamis per route) + icon search (list pages) + icon `power` logout. Sub-page ada back button. Dashboard tampil brand "SI KARTO". AppBar sticky **lengket ke browser** — `html, body { margin:0 }` wajib di `app.css` (tanpa reset, body default margin 8px muncul celah).
- **Tombol Tambah = FAB** di semua CRUD list (masters/instruments dari AppLayout `showAdd`; tests dari page sendiri) — bukan icon di app bar.
- List pakai request-card (kode monospace bold + `var-chip` round + meta) — contoh `Tests/Index.vue`.
- Font Inter (`@fontsource/inter`). Transisi halaman slide via `<Transition>` di `app.ts`.

## Otorisasi
- Spatie (`spatie/laravel-permission`), config `config/permission.php`. Role: `super_admin`, `admin`, `inspector`, `user`. Users table tanpa kolom role — pakai Spatie.
- `super_admin` = full (master + user.manage + test + report). `admin` = master CRUD + test.read + report.read/export (BUKAN test.create, BUKAN user.manage). `inspector` = master CRUD + test.create + test.read. `user` = read-only (master.read, test.read, report.read).
- Endpoint harus cek permission, bukan hanya role. Semua form validasi server-side (jangan percaya browser).

## SSO Login (Manual Provisioning)
- **Login wajib SSO** (OAuth2 Authorization Code) — BUKAN email/password. SSO server: `sekali_login` (`http://localhost/sekali_login/public`). Panduan: `public/SSO-Integrasi-Laravel-Manual-Provisioning.md`. Implementasi: `AuthController` (redirectSso/callbackSso/pendingRole).
- Client terdaftar di DB `oauth_clients` sekalilogin (client_id `299dec87-8cd6-4882-b525-faa5ae86d853`, secret `sikarto-sso-secret-2026`), redirect_uri `http://localhost/sikarto/public/callback`. Config di `.env` (`SSO_BASE_URL`, `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`) + `config/services.php`.
- **Manual Provisioning**: callback match by `nik`. NIK tak dikenal → auto-create user `is_approved=false` → redirect `/pending-role` (pilih role inspector/admin + factory). Admin approve via `/users` (`Users/Index.vue`, permission `user.manage`, role di-assign via Spatie + `is_approved=true`).
- Users pakai kolom `nik` (unique), `is_approved`, `requested_role`. User `is_approved=false` TIDAK bisa login.

## DB
- Default SQLite (`database/database.sqlite`). Prod: MySQL/MariaDB — ubah `.env`.
- Index wajib: `instruments.code`, `calibration_tests.next_test_date`, `calibration_tests.instrument_id`.
- `factories` & `departments` TIDAK punya kolom `code` (dihapus). Identitas = `name`. Departemen unik per factory via relasi, bukan code.

## Testing
- `php artisan test` — feature test business logic PASS/FAIL + permission di `tests/Feature/CalibrationTestTest.php`.
- **phpunit.xml override `APP_URL=http://localhost`** — jangan dihapus. `.env` pakai subfolder `/sikarto/public`; tanpa override ini `$this->post('/x')` di test jadi `http://localhost/sikarto/public/x` → 404.

## Gotchas
- Nama package composer: `tightenco/ziggy` (composer) + `ziggy-js` (npm) — dua-duanya ada.
- Business logic PASS/FAIL: `correction = penunjukan − standar`; FAIL jika ≥1 titik melewati `acceptable_limits`. Test FAIL tetap tersimpan. Implementasi: `TestController::store` + `AcceptableLimit::isWithin()`.
- `next_test_date = test_date + 1 bulan` (auto, wajib di semua jalur simpan pengujian).
- Varlet `InputType` HANYA `text|password|number|tel|email`. Textarea = `:textarea="true"`, tanggal = native `<input type="date">` (bukan `type="date"`/`type="textarea"` — typecheck fail).
- **Varlet icon font KUSTOM** (bukan MDI) — daftar ikon cek `node_modules/@varlet/ui/es/icon/icon.css` (`.var-icon-<name>`). `logout`, `pencil`, `gauge`, `close`, `account`, `tune-variant`, `chart-timeline-variant` dll TIDAK ADA. Pakai tombol teks untuk aksi (Edit/Hapus/Keluar), bukan ikon.
- **Konfirmasi (hapus dll) wajib pakai `Dialog` Varlet** (import `{ Dialog } from '@varlet/ui'`), BUKAN `confirm()`. Tombol bahasa Indonesia: `confirmButtonText: 'Ya, Hapus'`, `cancelButtonText: 'Batal'`. Contoh: `Masters/Form.vue`.
- **Varlet Form**: pakai prop `:onsubmit="fn"` (handler terima `valid: boolean`, guard `if (!valid) return`). JANGAN `@submit.prevent` — `.prevent` bungkus handler, Varlet panggil `onSubmit(valid)` → `valid.preventDefault()` crash ("e.preventDefault is not a function").
- **Varlet API yang TIDAK ada** (cek `node_modules/@varlet/ui/types/*.d.ts` dulu): `var-avatar` tidak punya `text-color`; `var-input` tidak punya `focus` (pakai `autofocus`).
- MCP Varlet: package npm yang benar **`@fe-fast/varlet-mcp`** (di `opencode.json`). `@varlet/mcp` 404 — jangan dipakai. Registry MCP parsial: `var-app-bar`, `var-bottom-navigation`, `var-chip`, `var-select` dll TIDAK ada di MCP — cek types lokal.
- Role/permission endpoint: cek `permission:` middleware. Role list: `super_admin`, `admin`, `inspector`, `user`. Inspector BISA master CRUD (sesuai permission); `user` read-only.
- **Paginator Inertia TIDAK punya key `meta`** — keys top-level: `current_page`, `last_page`, `from`, `to`, `total`, `data`. JANGAN `items.meta.current_page` (undefined → crash render). Pakai `items.current_page`.
- **Semua URL di JS wajib pakai Ziggy `route()`** — app di subfolder `/sikarto/public`; URL hardcoded (`/auth/sso`, `/pending-role`, dll) jadi `http://localhost/...` → 404. Khusus link eksternal (redirect SSO) pakai `<a :href="route('sso.redirect')">`.
- AppLayout top bar minimal: judul halaman + back (sub-page) + search icon (list) + plus hijau (create) + logout icon `power`. Bottom nav & FAB HANYA di halaman dashboard. **Dashboard appbar = nama user yang login** (bukan brand).
- Pending-role (`/pending-role`): user baru pilih role `super_admin`/`admin`/`inspector`/`user` + factory. Footer login: "Departemen QA".
