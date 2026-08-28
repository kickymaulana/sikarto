# PRD — SI KARTO
**Sistem Kalibrasi Toleransi Operasional Alat Ukur Rutin Bulanan**

Versi: 1.0 | Status: Draft | Tech Stack: Laravel 13, Inertia 3, Vue 3, TypeScript, Varlet UI

---

## 1. Executive Summary & Objectives

### 1.1 Latar Belakang
Proses pengecekan dan kalibrasi rutin bulanan alat ukur di pabrik saat ini masih manual berbasis formulir kertas. Risiko: alat terlewat jadwal, data sulit ditelusuri saat audit, perhitungan koreksi dan toleransi rawan salah hitung manual.

### 1.2 Tujuan
1. Digitalisasi paperless proses pengecekan/kalibrasi alat ukur bulanan.
2. Memastikan seluruh alat ukur di semua departemen dan pabrik teruji tepat waktu (scheduling otomatis).
3. Otomatisasi perhitungan koreksi dan penentuan status kelayakan alat (PASS/FAIL) berdasarkan acceptable limit.
4. Menyediakan rekapitulasi data untuk audit ISO/internal (export Excel/PDF).

### 1.3 Scope
- **In scope:** master data CRUD, entry pengujian, dashboard & scheduling, laporan.
- **Out of scope (fase awal):** integrasi perangkat timbangan otomatis, e-signature, workflow approval berjenjang.

### 1.4 Definisi Istilah
| Istilah | Arti |
|---|---|
| Penunjukan | Nilai yang ditunjukkan alat ukur saat diuji |
| Standar | Nilai acuan dari template pengujian (contoh: 500 gr) |
| Correction | Penunjukan − Standar |
| Acceptable Limit | Batas toleransi koreksi (±5 gr) |
| PASS / OK | Semua titik koreksi dalam batas toleransi |
| REJECT / NOT OK | Minimal 1 titik koreksi melewati toleransi |

---

## 2. User Roles & Permissions

Manajemen role & permission memakai **`spatie/laravel-permission`**. Roles: `super_admin`, `admin`, `inspector`, `user`.

| Fitur | super_admin | admin | inspector | user |
|---|---|---|---|---|
| Login/Logout | ✅ | ✅ | ✅ | ✅ |
| CRUD semua master (Factory–Alat Ukur) | ✅ | ✅ | ✅ | — |
| Kelola Pengguna | ✅ | — | — | — |
| Entry Pengujian | ✅ | — | ✅ | — |
| Lihat Riwayat Pengujian | ✅ | ✅ | ✅ | ✅ |
| Dashboard & Scheduling | ✅ | ✅ | ✅ | ✅ |
| Laporan & Export Excel/PDF | ✅ | ✅ | — | — |
| Lihat Laporan (read-only) | ✅ | ✅ | — | ✅

**Aturan:**
- `inspector` input pengujian + CRUD master data, tidak bisa kelola user/laporan.
- `admin` bisa CRUD master + lihat laporan/export, tidak bisa entry pengujian dan tidak bisa kelola user.
- `user` read-only: lihat dashboard, riwayat, dan laporan (tanpa export).
- `super_admin` full control: master, pengguna, pengujian, laporan.
- Semua perubahan master data dan pengujian tercatat (audit log: user, timestamp).
- Alat ukur bisa dinonaktifkan (soft delete) tanpa menghapus riwayat.

### Permission (Spatie)
| Permission | super_admin | admin | inspector | user |
|---|---|---|---|---|
| `master.create` | ✅ | ✅ | ✅ | — |
| `master.read` | ✅ | ✅ | ✅ | ✅ |
| `master.update` | ✅ | ✅ | ✅ | — |
| `master.delete` | ✅ | ✅ | ✅ | — |
| `user.manage` | ✅ | — | — | — |
| `test.create` | ✅ | — | ✅ | — |
| `test.read` | ✅ | ✅ | ✅ | ✅ |
| `report.read` | ✅ | ✅ | — | ✅ |
| `report.export` | ✅ | ✅ | — | — |

---

## 3. Database Schema & Data Requirements

### 3.1 Entitas & Relasi

```
users (id, name, email, password, factory_id*, ...)
  └─ role via spatie (bukan kolom role)

roles (id, name, guard_name)
permissions (id, name, guard_name)
model_has_roles, model_has_permissions, role_has_permissions  (tabel pivot spatie)

factories (id, code, name)
  └─ contoh: KIM, Dalu 1, Dalu 2

departments (id, factory_id → factories, code, name)
  └─ contoh: Filling, Packing, Maintenance

instrument_types (id, name)
  └─ contoh: Timbangan, Caliper, Rol Siku, Thermo Hunter, Stopwatch, Laser

brands (id, name)
  └─ contoh: DICSON, Mitutoyo

capacities (id, name, value, unit)
  └─ contoh: 3 KG, 500 KG, 150 MM

acceptable_limits (id, name, min_correction, max_correction, unit)
  └─ contoh: ±5 gr → min=-5, max=5, unit=gr; ±2 kg → min=-2000, max=2000, unit=gr

instruments (id, code [unique], factory_id, department_id, instrument_type_id,
            brand_id, capacity_id, acceptable_limit_id, is_active, notes)
  └─ contoh code: W.FL.5

standard_templates (id, instrument_type_id, standard_value, sort_order)
  └─ titik pengujian standar per jenis alat (500 gr, 700 gr, 800 gr)

calibration_tests (id, instrument_id, test_date, next_test_date,
                   tester_id → users, status [PASS|FAIL], notes)

calibration_test_items (id, calibration_test_id, standard_value,
                        reading_value, correction, is_within_limit)
```

### 3.2 Aturan Data
- `instruments.code` UNIQUE — kode alat (contoh `W.FL.5`), validasi duplikat.
- `acceptable_limits` pakai `min_correction`/`max_correction` numerik + `unit` (bukan string "±5 gr" mentah) agar validasi PASS/FAIL bisa dihitung mesin.
- `standard_templates` per jenis alat — template titik uji (bisa diubah per jenis, tidak per alat).
- Unit konsisten dalam satu alat: kapasitas & toleransi memakai unit sama (gr untuk berat, mm untuk panjang).
- Semua tabel master + `instruments` soft delete.
- `calibration_tests` & items immutable setelah disimpan (revisi hanya oleh pemegang hak + tercatat audit).

### 3.3 CRUD Matrix
| Master | Create | Read | Update | Delete |
|---|---|---|---|---|
| Factory | ✅ | ✅ | ✅ | ✅ (jika tak dipakai alat) |
| Departemen | ✅ | ✅ | ✅ | ✅ (jika tak dipakai alat) |
| Jenis Alat | ✅ | ✅ | ✅ | ✅ (jika tak dipakai alat) |
| Merk | ✅ | ✅ | ✅ | ✅ |
| Kapasitas | ✅ | ✅ | ✅ | ✅ |
| Acceptable Limit | ✅ | ✅ | ✅ | ✅ |
| Alat Ukur | ✅ | ✅ | ✅ | Soft delete |

---

## 4. Functional Requirements & User Flow

### FR-1 Dashboard & Scheduling
- **FR-1.1** Statistik ringkas: total alat, alat perlu uji bulan ini, alat terlambat (overdue), rasio PASS/FAIL bulan berjalan.
- **FR-1.2** Matriks/kalender status uji per alat per bulan (Jan–Des): warna status (hijau = pass, merah = fail/overdue, abu = belum ada uji).
- **FR-1.3** Daftar alat yang jatuh tempo bulan ini (berdasarkan `next_test_date`), satu klik → langsung ke form entry.
- **FR-1.4** Filter dashboard per factory & departemen.

**Flow:** Login → Dashboard → lihat matriks → klik alat jatuh tempo → masuk form pengujian.

### FR-2 Form Entry Pengujian (mobile/web friendly)
- **FR-2.1** Input/pilih `Kode Alat` → sistem auto-fill: Factory, Departemen, Jenis, Merk, Kapasitas, Toleransi.
- **FR-2.2** Tabel titik uji otomatis dari `standard_templates` jenis alat (contoh: 500 gr, 700 gr, 800 gr).
- **FR-2.3** QC input kolom **Penunjukan** per baris.
- **FR-2.4** Perhitungan real-time: `Correction = Penunjukan − Standar`, kolom status tiap baris (OK/NOK), total status alat (PASS/FAIL).
- **FR-2.5** Save → simpan test + items → `next_test_date = test_date + 1 bulan` (auto).
- **FR-2.6** Validasi sebelum simpan: semua baris terisi; konfirmasi bila status FAIL.
- **FR-2.7** Riwayat pengujian per alat (list + detail + status tiap titik).

**Flow:**
1. Pilih/scan kode alat → autofill data alat.
2. Sistem render tabel titik standar.
3. QC isi penunjukan → koreksi & status terhitung otomatis.
4. Simpan → status alat ditentukan → jadwal bulan depan dibuat.

### FR-3 Master Data
- **FR-3.1** CRUD terpisah per master (Factory, Departemen, Jenis Alat, Merk, Kapasitas, Acceptable Limit, Alat Ukur).
- **FR-3.2** Setiap form master memakai dropdown referensi master lain (konsistensi input).
- **FR-3.3** Form alat ukur: pilih Factory → pilih Departemen (difilter per factory) → Jenis → Merk → Kapasitas → Toleransi → isi Kode Alat unik.
- **FR-3.4** Form Acceptable Limit: input nama (contoh: "±5 gr"), nilai min, nilai max, unit.
- **FR-3.5** Pencarian, paginasi, sorting di semua list.

### FR-4 Laporan & Rekapitulasi
- **FR-4.1** Rekap pengujian per periode (bulan/tahun), filter factory/departemen/jenis/status.
- **FR-4.2** Riwayat kalibrasi per alat (untuk audit ISO).
- **FR-4.3** Export Excel & PDF dari tiap laporan.
- **FR-4.4** Header laporan: nama pabrik, periode, kolom tanda tangan QC & Supervisor (kosong untuk cetak).

---

## 5. Business Logic & Validation Rules

### 5.1 Rumus Koreksi
```
Correction = Penunjukan − Standar
```
- `Penunjukan` = nilai input QC; `Standar` = nilai dari template.
- Nilai numerik desimal; unit mengikuti kapasitas/toleransi alat.

### 5.2 Aturan Pass/Fail
```
Per baris:  is_within_limit = (min_correction ≤ correction ≤ max_correction)
Status alat: PASS  jika SEMUA baris is_within_limit = true
             FAIL  jika minimal 1 baris is_within_limit = false
```
- Pengujian status FAIL tetap **tersimpan** (bukan ditolak) — riwayat wajib lengkap untuk audit.
- Status dihitung otomatis saat input, dikunci saat save.

### 5.3 Scheduling
- `next_test_date = test_date + 1 bulan` (tanggal uji aktual, bukan bulan kalender).
- Alat masuk "jatuh tempo" bila `next_test_date ≤ hari ini` dan belum ada uji baru.
- Matriks bulanan = status dari `calibration_tests` terakhir per alat.

### 5.4 Validasi Input
| Aturan | Ketentuan |
|---|---|
| Kode Alat | Wajib, unik, format bebas (contoh W.FL.5) |
| Penunjukan | Wajib numerik, wajib semua baris terisi sebelum save |
| Tanggal uji | Tidak boleh di masa depan; boleh backdate (uji susulan) |
| Master referensi | Tidak bisa dihapus jika masih dipakai (foreign key) |
| Dropdown | Nilai selalu dari master, tidak ada free-text |

---

## 6. Non-Functional Requirements

### 6.1 UI/UX
- SPA dengan Inertia **v3** + Vue 3 + TypeScript; komponen UI memakai **Varlet UI** (mobile-first, cocok dipakai QC via HP di lapangan).
- Bahasa Indonesia seluruh antarmuka.
- Loading state, empty state, error state pada setiap halaman.
- Pesan validasi inline.

#### Design System
Referensi desain: aplikasi **SUKIRMAN** (`D:\Apache24\htdocs\sukirman`). Adopsi pola "android-layout" mobile-first + Varlet MD3 light.

**Tema:**
- Primary: **orange** `#FB8C00` (ORANGE_600) — app bar, tombol utama, ikon.
- Accent fill: **peach** `#FDF0EA` — input & kartu.
- Background: `#f8fafc`; kartu putih radius 16, border `#f1f5f9`, shadow halus.
- Status chip semantic: hijau PASS, merah FAIL/NOK, kuning/amber overdue, `var-chip` round.
- Font: **Inter** (`@fontsource/inter` 400–800), fallback Roboto.

**Framework setup:**
- `StyleProvider(Themes.md3Light)` dari `@varlet/ui`.
- `@varlet/touch-emulator` (kontrol layar sentuh).
- Transisi antar halaman: slide (translateX 30px → 0) via `Transition`.
- Bottom navigation `var-bottom-navigation` + FAB.

**Layout ("android-layout"):**
- Container: `100vh` flex column, `overflow: hidden`.
- Top bar: putih sticky, avatar + greeting + nama user.
- Content: `flex:1` scroll, padding 16–20px, bottom padding ±80px (ruang bottom nav).
- Bottom nav: Beranda / Pengujian / Laporan (sesuai permission) + FAB untuk entry pengujian.

**Komponen:**
- Welcome card: gradient orange → deep orange, radius 20, teks putih.
- Stat grid: 2 kolom, ikon pastel + angka tebal.
- Feature/quick-link card: bg pastel + border berwarna + ikon + chevron.
- Request-card list: kode alat monospace bold + chip status + meta (tanggal/tester).
- Master CRUD: **halaman terpisah** `/masters/{entity}/create` & `/{entity}/{id}/edit` (bukan inline form/dialog). List = row-card + klik-card → halaman edit; tombol Edit/Hapus tidak di list, Hapus di halaman edit. Contoh: `Masters/Form.vue`, `Instruments/Form.vue`.
- **Master menu** `/masters`: landing card grid semua entity (Factory/Departemen/Jenis Alat/Merk/Kapasitas/Acceptable Limit/Alat Ukur) + jumlah data.
- List master & alat ukur = android pattern: **icon search di app bar** → toggle kotak pencarian (shared `searchState` via `composables/search.ts`), `var-pull-refresh` + `var-list` infinite scroll (load-more via `router.get` + `only: [...]` + merge props).
- **AppBar satu baris**: judul halaman (dari `pageTitle` map di `AppLayout.vue`) + back (sub-page) + icon search (list) + icon `power` logout. Bottom nav & FAB hanya di halaman dashboard. **Dashboard appbar = nama user yang login** (bukan brand). AppBar sticky lengket ke browser (`html, body { margin:0 }` di `app.css`).
- **Semua URL di JS wajib Ziggy `route()`** — app di subfolder `/sikarto/public`; URL hardcoded → 404. Link eksternal SSO pakai `<a :href="route('sso.redirect')">`.
- **Satu warna orange**: appbar gradient `#fb8c00→#f57c00` (teks/ikon putih) = FAB = tombol primary. `StyleProvider` override `'color-primary': '#fb8c00'` di `app.ts` (wajib — `Themes.md3Light` default primary ungu `#6750A4`). Search mode appbar putih.
- **Tombol Tambah = FAB** (bukan icon app bar) di semua CRUD list — AppLayout `showAdd` untuk master/instruments, FAB sendiri di `Tests/Index.vue`.
- Tombol: pill (border-radius penuh), gradient orange untuk tombol utama.
- Login: bg `#f8fafc`, ikon/logo, judul bold orange, benefit box, tombol pill gradient, footer.

**Varlet Usage Rules (wajib — cek `node_modules/@varlet/ui/types/` sebelum pakai komponen):**
- `var-form` pakai prop `:onsubmit="fn"` (handler terima `valid: boolean`, guard `if (!valid) return`). JANGAN `@submit.prevent` → crash `e.preventDefault is not a function`.
- `InputType` HANYA `text|password|number|tel|email`. Textarea = `:textarea="true"`. Tanggal = native `<input type="date">`.
- `var-avatar`: tidak ada `text-color`. `var-input`: tidak ada `focus`, pakai `autofocus`.
- **Icon font kustom** (bukan MDI): daftar ikon di `node_modules/@varlet/ui/es/icon/icon.css`. `logout`, `pencil`, `gauge`, `close` dll TIDAK ADA — aksi pakai tombol teks.
- **Konfirmasi wajib pakai `Dialog` bawaan Varlet** (import `{ Dialog } from '@varlet/ui'`) — JANGAN `confirm()`. Tombol dialog bahasa Indonesia: `confirmButtonText: 'Ya, Hapus'`, `cancelButtonText: 'Batal'`. Contoh: `Masters/Form.vue`, `Instruments/Form.vue`.
- Cek komponen lain via `node_modules/@varlet/ui/types/<component>.d.ts` (registry MCP parsial, tidak mencakup semua komponen).



### 6.2 Security
- **Autentikasi wajib SSO** (OAuth2 Authorization Code) — BUKAN email/password. SSO server `sekali_login` lokal (`http://localhost/sekali_login/public`), prod: `sekalilogin.gotechdynamics.com`. Client SI KARTO terdaftar (client_id `299dec87-8cd6-4882-b525-faa5ae86d853`, redirect `http://localhost/sikarto/public/callback`). Panduan: `public/SSO-Integrasi-Laravel-Manual-Provisioning.md`.
- **Manual Provisioning**: user dicocokkan via `nik` (unique). NIK baru → auto-create `is_approved=false` → `/pending-role` (pilih role `super_admin`/`admin`/`inspector`/`user` + factory) → Admin setujui di `/users` (assign Spatie role + `is_approved=true`). User belum disetujui TIDAK bisa login.
- Autentikasi Laravel (session-based), CSRF aktif.
- Otorisasi berbasis role & permission via **`spatie/laravel-permission`** (middleware/policy) — tiap endpoint cek permission.
- Validasi input server-side (bukan hanya client) — jangan percaya data dari browser.
- Jangan expose data yang tidak perlu via Inertia shared props.
- Log aktivitas penting (create/update/delete master & pengujian).
- Env & secret via `.env`, tidak pernah di commit.

### 6.3 Performance
- Paginasi semua list master & riwayat.
- Eager loading relasi pada query dashboard/report.
- Index pada kolom: `instruments.code`, `calibration_tests.next_test_date`, `calibration_tests.instrument_id`, foreign key.
- Rendering matriks Jan–Des: batch query status terakhir per alat, bukan query per-alat.
- Export Excel/PDF di queue bila data besar (fase lanjut).

### 6.4 Tech Stack & Setup
| Lapisan | Teknologi |
|---|---|
| Backend | Laravel **13** (terbaru), PHP |
| Frontend | Inertia **3**, Vue 3 Composition API, TypeScript |
| UI Library | **Varlet UI** + `@varlet/import-resolver` |
| Routing JS | **`tightenco/ziggy`** |
| Otorisasi | **`spatie/laravel-permission`** |
| Build | Vite |
| Database | MySQL/MariaDB |
| Report | Excel (maatwebsite/excel atau sejenisnya) + PDF |

**Setup:** `laravel new` **tanpa starter kit** → pasang Inertia 3 server + client (`@inertiajs/vue3`), Vue 3 + TS, Varlet + import resolver, Ziggy, Spatie.

### 6.5 Deliverables Awal (Fase 1)
1. Setup Laravel 13 + Inertia 3 + Vue 3 + TS + Varlet + Ziggy + Spatie.
2. Migrasi & seeder seluruh master + roles/permissions + user admin awal.
3. Halaman login + otorisasi role/permission.
4. CRUD 7 master data.
5. Form entry pengujian (autofill + hitung otomatis + PASS/FAIL + next date).
6. Dashboard & matriks scheduling.
7. Laporan + export Excel/PDF.
