# สรุปโปรเจค Fisherman

ระบบเว็บแอปพลิเคชันสำหรับจัดการและแสดงผลการแข่งขันตกปลา พัฒนาด้วย **PHP + MySQL/MariaDB (PDO)** เน้นการใช้งานฝั่ง Admin เป็นหลัก ไม่มีระบบสมัครสมาชิกสำหรับผู้เล่นทั่วไป

## เทคโนโลยีที่ใช้
- **Backend:** PHP 8.4 (Native, ไม่มี Framework)
- **Database:** MySQL/MariaDB ผ่าน PDO (ใช้ Prepared Statements ป้องกัน SQL Injection)
- **Frontend:** HTML5 + Vanilla CSS + JavaScript (ไม่มี Framework)
- **Auth:** PHP Native Session

## โครงสร้างไฟล์หลัก
| ไฟล์ | หน้าที่ |
|---|---|
| `index.php` | จุดเริ่มต้น redirect ไปหน้า login |
| `sign.php` | หน้า Login ตรวจสอบ username/password กับตาราง `admin_user` |
| `auth_check.php` | Guard ตรวจสอบ session, ใช้ `require_once` ในทุกหน้าที่ต้อง login |
| `logout.php` | ทำลาย session และ redirect กลับหน้า login |
| `db_connect.php` | เชื่อมต่อฐานข้อมูลผ่าน PDO |
| `home_page.php` | Dashboard หลัก แสดงรายการแมตช์ทั้งหมด + สร้างแมตช์ใหม่ |
| `race_page.php` | หน้าจัดการแมตช์รายตัว แบ่งเป็น 4 แท็บ: dashboard (ตารางคะแนน), categories (ชนิดปลา), teams (ทีม/นักกีฬา), logs (บันทึกน้ำหนักปลา) |
| `dashboard.php` | หน้าสรุปผล Top 5 แยกตามประเภทการแข่งขัน เชื่อม DB แล้ว (อัปเดต 11 ก.ค. 2026) |
| `database.sql` | Schema: `matches`, `categories`, `teams`, `catch_logs`, `admin_user` |
| `Strugture.md` | เอกสารออกแบบ UX/Flow ทั้งระบบ (ภาษาไทย) |
| `next_session_context.md` | บันทึกความคืบหน้าของ session ก่อนหน้า |

## ฟังก์ชันที่ทำงานได้แล้ว
- Login/Logout พร้อม session guard กันเข้าหน้าอื่นตรงๆ
- สร้างแมตช์ใหม่ (status: pending → live → stopped) พร้อมแก้ไข/ลบแมตช์ได้จากหน้า home
- เริ่ม/จบ/รีสตาร์ทการแข่งขัน (start_race / stop_race / re-start) — ล็อกฟอร์มเพิ่ม-แก้ไข-ลบทั้งหมดอัตโนมัติเมื่อแมตช์ stopped
- เพิ่ม/แก้ไข/ลบ ชนิดปลา-กติกา (min weight, prize quota)
- เพิ่ม/แก้ไข/ลบ ทีม/นักกีฬา
- บันทึก/แก้ไข/ลบ น้ำหนักปลาที่จับได้ (catch logs)
- คำนวณ Leaderboard แบบ Real-time ต่อหมวดปลา: กรองด้วย min weight, จัดอันดับตามน้ำหนักสูงสุด, เรียงตามเวลาจับก่อนหลังเมื่อคะแนนเท่ากัน, จำกัดจำนวนตาม prize quota
- หน้า `dashboard.php` แสดง Top 5 อันดับแรกแยกตามประเภทการแข่งขัน เชื่อมกับ `race_page.php` ผ่านปุ่มกลับ/ปุ่ม dashboard

## อัปเดตล่าสุด (11 ก.ค. 2026)
- **Merge งานของเพื่อนร่วมทีม (Teelemon)** ที่ push เข้า branch `feature/back-end,strug` เดียวกัน (commit UI-race_page) — แก้ conflict ใน `race_page.php` โดยรวม UI แบบ modal ของเพื่อนเข้ากับ logic stop_race/re-start ที่มีอยู่เดิม
- **เชื่อม `dashboard.php` เข้ากับฐานข้อมูลจริง** จากที่เดิมเป็น static placeholder — ดึงรายชื่อประเภทการแข่งขันของแมตช์ แล้วแสดง Top 5 ทีมแยกทีละประเภท (จัดอันดับด้วยน้ำหนักสูงสุด, ผ่านเกณฑ์ min_weight, tie-break ด้วยเวลาจับก่อนหลัง) พร้อมปุ่มกลับไป `race_page.php`
- **เพิ่มปุ่มแก้ไข/ลบ** ให้ครบทุกตารางที่ยังไม่มี: แมตช์ (`home_page.php`), ประเภทการแข่งขัน/ทีม/บันทึกจับปลา (`race_page.php`) — ทุกฟอร์มลบมี confirm dialog และถูกล็อกอัตโนมัติเมื่อแมตช์ stopped
- **แก้บั๊ก layout** ใน `style/dashboard.css`: `.container` เดิมใช้ `position: fixed; top: 0` ทำให้เนื้อหาที่ล้นจอ (เช่นมีหลายประเภทการแข่งขัน) เลื่อนดูไม่ได้เลย เปลี่ยนเป็น normal flow (`margin: auto`)
- **ปรับสไตล์ปุ่มให้เข้ากับธีมเว็บ**: เพิ่ม class `.row-actions`/`.edit-btn`/`.delete-btn` ใน `race_pageas.css` และ `home_pageoo.css`, ปรับปุ่มกลับใน `dashboard.css` ให้เป็นสีน้ำเงินปุ่มหลักตรงกับปุ่ม primary อื่นๆ ในเว็บ (#2563eb)
- Commit: `a532b57` (delete match + status labels), `8d05cd4` (merge เพื่อน), `3541961` (edit/delete ทุกตาราง + UI). Push ขึ้น `origin/feature/back-end,strug` เรียบร้อยแล้ว

## สิ่งที่ยังไม่เสร็จ
1. หน้า Podium สรุปผลอันดับ 1-3 แบบไฮไลต์ (เหรียญทอง/เงิน/ทองแดง) ตาม `Strugture.md` — ตอนนี้ `dashboard.php` แสดงเป็นตารางธรรมดา ยังไม่ใช่ podium
2. ฟีเจอร์ Export/Print ผลการแข่งขันเป็น PDF
3. ข้อความแจ้งเตือน success/error เมื่อ submit ฟอร์ม และปรับ mobile responsive
4. ยกระดับความปลอดภัย: รหัสผ่านใน `admin_user` ยังเก็บเป็น plain text ต้องเปลี่ยนไปใช้ `password_hash()`/`password_verify()`, และ DB credentials ใน `db_connect.php` ยัง hardcode อยู่

## ข้อสังเกตด้านความปลอดภัย (ควรแก้ก่อนใช้งานจริง)
- **รหัสผ่าน plain-text**: `sign.php` เทียบรหัสผ่านด้วย `===` ตรงๆ กับค่าที่เก็บใน DB (`admin/admin`) — ควรใช้ password hashing
- **DB credentials hardcode**: `db_connect.php` ฝัง user/pass ตรงในโค้ด ควรย้ายไป environment variable
- ส่วนอื่น (query, output) ใช้ prepared statements และ `htmlspecialchars` ครบถ้วนดีแล้ว ป้องกัน SQL Injection และ XSS เบื้องต้นได้
