# ระบบจุดขายสินค้า (POS System)

## ภาพรวมโปรเจค

ระบบจุดขายที่ทันสมัยและใช้งานง่าย ออกแบบมาเพื่อการจัดการการขายและสินค้าแบบเรียลไทม์ โปรเจคนี้สร้างด้วย Laravel สำหรับส่วนหลังบ้าน และ Vue 3 สำหรับส่วนหน้าบ้าน

---

## คุณสมบัติหลัก

### การจัดการสินค้า
- เพิ่ม แก้ไข และลบสินค้า
- จัดเรียงสินค้าตามประเภท
- ติดตามปริมาณสินค้า
- อัปโหลดรูปภาพสินค้า

### การจัดการการขาย
- สร้างใบเสร็จขาย
- ค้นหาและเพิ่มสินค้าลงในตะกร้า
- คำนวณราคารวม ภาษี และส่วนลด
- พิมพ์ใบเสร็จ

### การจัดการโต๊ะ
- จัดการโต๊ะอาหารหลายโต๊ะ
- ติดตามสถานะการใช้งาน
- บันทึกออร์เดอร์ต่อเนื่อง

### อื่น ๆ
- ระบบตรวจสอบสิทธิ์ (Authentication)
- จัดการผู้ใช้งานและบทบาท
- ติดตามเซสชันผู้ใช้
- บันทึกการตั้งค่าระบบ

---

## เทคโนโลยีที่ใช้

### ส่วนหลังบ้าน
- Laravel 8
- PHP 8.2 ขึ้นไป
- MySQL
- Laravel Sanctum

### ส่วนหน้าบ้าน
- Vue 3
- Tailwind CSS
- DataTables
- QR Code

### ไลบรารี่เพิ่มเติม
- Axios
- SweetAlert2
- Cloudinary
- jQuery

---

## โครงสร้างโปรเจค

```
pos-project-app/
├── app/                 # ไฟล์โค้ด Laravel
│   ├── Http/            # Controllers, Middleware
│   ├── Models/          # Database Models
│   └── Console/         # Artisan Commands
├── database/            # Migrations และ Seeders
├── resources/           # ทรัพยากรส่วนหน้าบ้าน
│   ├── js/              # Vue Components
│   ├── css/             # Stylesheets
│   └── views/           # Blade Templates
├── routes/              # นิยามเส้นทาง
├── config/              # ไฟล์การตั้งค่า
├── public/              # Web Root
├── storage/             # ที่เก็บไฟล์
├── tests/               # ทดสอบ Unit และ Feature
└── vendor/              # Dependencies
```

---

## ตัวแบบข้อมูล (Models)

- **User** - ผู้ใช้งานระบบ
- **Product** - สินค้า
- **Category** - ประเภทสินค้า
- **Order** - ใบเสร็จการขาย
- **OrderDetail** - รายการสินค้าในใบเสร็จ
- **Table** - โต๊ะอาหาร
- **Session** - เซสชันผู้ใช้
- **Setting** - ค่าตั้งค่าระบบ

---

## วิธีการติดตั้ง

### ข้อกำหนดเบื้องต้น
- PHP 8.2 ขึ้นไป
- Composer
- Node.js และ npm
- MySQL / PostgreSQL หรือฐานข้อมูลอื่น

### ขั้นตอนการติดตั้ง

1. ดาวน์โหลดหรือ clone โปรเจค
2. ติดตั้ง PHP Dependencies:
   ```bash
   composer install
   ```

3. ติดตั้ง Frontend Dependencies:
   ```bash
   npm install
   ```

4. สร้างไฟล์ .env:
   ```bash
   cp .env.example .env
   ```

5. สร้าง Application Key:
   ```bash
   php artisan key:generate
   ```

6. ตั้งค่าฐานข้อมูล ใน .env และ Migrate:
   ```bash
   php artisan migrate
   ```

7. Seed ข้อมูลตัวอย่าง (ถ้ามี):
   ```bash
   php artisan db:seed
   ```

---

## วิธีการใช้งาน

### รัน Development Server

เปิด Terminal 2 หน้าต่าง:

**Terminal 1 - Backend (Laravel):**
```bash
php artisan serve
```
เข้าที่ http://localhost:8000

**Terminal 2 - Frontend (Vite):**
```bash
npm run dev
```

### Build สำหรับ Production
```bash
npm run build
```
