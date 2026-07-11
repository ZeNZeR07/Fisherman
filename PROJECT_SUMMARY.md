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
| `dashboard.php` | หน้าสรุปผล (ยังเป็น static/placeholder ไม่ได้เชื่อม DB) |
| `database.sql` | Schema: `matches`, `categories`, `teams`, `catch_logs`, `admin_user` |
| `Strugture.md` | เอกสารออกแบบ UX/Flow ทั้งระบบ (ภาษาไทย) |
| `next_session_context.md` | บันทึกความคืบหน้าของ session ก่อนหน้า |

## ฟังก์ชันที่ทำงานได้แล้ว
- Login/Logout พร้อม session guard กันเข้าหน้าอื่นตรงๆ
- สร้างแมตช์ใหม่ (status: pending → live → stopped)
- เริ่มการแข่งขัน (start_race)
- เพิ่มชนิดปลา/กติกา (min weight, prize quota)
- เพิ่มทีม/นักกีฬา
- บันทึกน้ำหนักปลาที่จับได้ (catch logs)
- คำนวณ Leaderboard แบบ Real-time ต่อหมวดปลา: กรองด้วย min weight, จัดอันดับตามน้ำหนักสูงสุด, เรียงตามเวลาจับก่อนหลังเมื่อคะแนนเท่ากัน, จำกัดจำนวนตาม prize quota

## สิ่งที่ยังไม่เสร็จ (ตาม `next_session_context.md`)
1. ปุ่ม "จบการแข่งขัน" (End Match) ยังไม่ implement — ต้องล็อกการกรอกคะแนนและเปลี่ยนสถานะเป็น `stopped`
2. หน้า Podium สรุปผลอันดับ 1-3 (Page 3B) ยังไม่ได้ทำ, `dashboard.php` ยังเป็น static
3. ฟีเจอร์ Export/Print ผลการแข่งขันเป็น PDF
4. ข้อความแจ้งเตือน success/error เมื่อ submit ฟอร์ม และปรับ mobile responsive
5. ยกระดับความปลอดภัย: รหัสผ่านใน `admin_user` ยังเก็บเป็น plain text ต้องเปลี่ยนไปใช้ `password_hash()`/`password_verify()`

## ข้อสังเกตด้านความปลอดภัย (ควรแก้ก่อนใช้งานจริง)
- **รหัสผ่าน plain-text**: `sign.php` เทียบรหัสผ่านด้วย `===` ตรงๆ กับค่าที่เก็บใน DB (`admin/admin`) — ควรใช้ password hashing
- **DB credentials hardcode**: `db_connect.php` ฝัง user/pass ตรงในโค้ด ควรย้ายไป environment variable
- ส่วนอื่น (query, output) ใช้ prepared statements และ `htmlspecialchars` ครบถ้วนดีแล้ว ป้องกัน SQL Injection และ XSS เบื้องต้นได้
