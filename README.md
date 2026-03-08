# Nale Laundry Management System (Laravel 12)

Sistem laundry lengkap berbasis Laravel 12 + SQL Server untuk operasional laundry dari penerimaan order sampai pelaporan laba/HPP.

## Fitur Utama

- Tracking status order pelanggan:
  `received -> washing -> drying -> ironing -> packing -> ready_for_pickup -> picked_up`
- Halaman publik untuk pelanggan cek status order (nomor order + nomor HP)
- Dashboard admin operasional
- CRUD pelanggan
- CRUD paket layanan + resep material per paket
- Perhitungan HPP otomatis per item dan per order (material + labor + overhead)
- Manajemen inventory
- Mutasi stok (opening, purchase, adjustment in/out)
- Stok opname (draft -> input aktual -> posting)
- Pembayaran order (unpaid/partial/paid)
- **Invoice PDF** per order
- **Struk kecil format BON (thermal)**:
  - Struk customer
  - Label internal laundry (dengan barcode untuk scan station)
- **Notifikasi WhatsApp otomatis** ketika status order berubah + log notifikasi
- **Scan Station**:
  - Scanner USB (keyboard wedge)
  - Kamera HP (BarcodeDetector + fallback Quagga2)
  - Update status manual setelah scan nomor order
- **Role access**: owner, admin, kasir, operator
- **Laporan bulanan**: omzet, HPP, laba kotor, margin, breakdown paket, trend 6 bulan

## 1) Prasyarat

- PHP 8.2+
- Composer
- SQL Server + driver `pdo_sqlsrv`
- Node.js (opsional, jika ingin compile asset Vite)

## 2) Konfigurasi

Copy env:

```bash
cp .env.example .env
```

### SQL Server (SQL Login)

```env
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=nale_laundry
DB_USERNAME=sa
DB_PASSWORD=yourStrong(!)Password
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true
```

### SQL Server (Windows Authentication)

```env
DB_CONNECTION=sqlsrv
DB_HOST=localhost
DB_PORT=
DB_DATABASE=nale_laundry
DB_USERNAME=
DB_PASSWORD=
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true
```

### Konfigurasi WhatsApp Gateway (opsional)

```env
WHATSAPP_ENABLED=true
WHATSAPP_API_URL=https://your-gateway-endpoint/send
WHATSAPP_TOKEN=your_token
WHATSAPP_TIMEOUT=10
WHATSAPP_FIELD_PHONE=target
WHATSAPP_FIELD_MESSAGE=message
WHATSAPP_FIELD_TOKEN=token
```

Catatan:
- Payload dikirim sebagai `application/x-www-form-urlencoded`.
- Mapping field (`target/message/token`) bisa diubah menyesuaikan gateway yang dipakai.

## 3) Setup Database

### Opsi A (direkomendasikan): migration Laravel

```bash
php artisan migrate:fresh --seed
```

### Opsi B: copy DDL SQL Server manual

1. Jalankan file: `database/sqlserver_ddl.sql` di SQL Server.
2. Jalankan seeder data awal:

```bash
php artisan db:seed
```

## 4) Jalankan Aplikasi

```bash
php artisan serve
```

- Halaman cek status pelanggan: `http://127.0.0.1:8000/`
- Login backoffice: `http://127.0.0.1:8000/login`
- Scan station: `http://127.0.0.1:8000/admin/scan`

## 5) Akun Seeder

Semua password: `password`

- `owner@nale-laundry.test` (owner)
- `admin@nale-laundry.test` (admin)
- `kasir@nale-laundry.test` (kasir)
- `operator@nale-laundry.test` (operator)

## 6) Matriks Akses Role

- `owner/admin`: full access
- `kasir`: dashboard, pelanggan, order, pembayaran, laporan bulanan
- `operator`: dashboard, order (update status), inventory, mutasi stok, stok opname

## 7) Struktur Domain Inti

- `customers`
- `service_packages`
- `service_package_materials`
- `inventory_items`
- `laundry_orders`
- `laundry_order_items`
- `order_status_histories`
- `payments`
- `stock_movements`
- `stock_opnames`
- `stock_opname_items`
- `whatsapp_notifications`

## Catatan Teknis

- HPP dihitung dari konsumsi material berdasarkan resep per paket + biaya labor + overhead.
- Saat order dibuat, pemakaian material otomatis dibukukan ke `stock_movements`.
- Saat stok opname diposting, penyesuaian stok otomatis dibukukan ke `stock_movements`.
- Saat status order diubah, sistem mencoba mengirim WhatsApp dan menyimpan log hasil kirim.
