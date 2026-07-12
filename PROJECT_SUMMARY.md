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
| `dashboard.php` | หน้าสรุปผล Top 5 แยกตามประเภทการแข่งขัน เชื่อม DB แล้ว, ตาราง responsive (อัปเดต 12 ก.ค. 2026) |
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
- UI ทุกตาราง (home_page, race_page ทั้ง 4 แท็บ, dashboard) เป็น **responsive** ครบ: มือถือ/แท็บเล็ต/PC/จอใหญ่ 4K-TV, ขนาดคอลัมน์ตารางปรับตามความยาวข้อความจริงแทนสัดส่วนตายตัว

## อัปเดตล่าสุด (12 ก.ค. 2026)
- **แก้สัดส่วนคอลัมน์ตาราง** ทุกหน้า (`home_page.php`, `race_page.php` 4 ตาราง, `dashboard.php`) — เดิมใช้ % ตายตัว/`table-layout:fixed`/inline padding hack (เช่น `padding-left:80px`) ทำให้คอลัมน์สั้นๆ (วันที่/สถานะ/ID) เหลือพื้นที่ว่างเยอะ ส่วนคอลัมน์ข้อความยาว (ชื่อแมตช์/ทีม) ตัดบรรทัด — เปลี่ยนเป็น class `.col-fit` (หดตามเนื้อหา, `width:1%; white-space:nowrap`) กับ `.col-fluid` (รับพื้นที่ที่เหลือ) ในทุก stylesheet
- **ทำ Responsive UI เต็มรูปแบบ** เพิ่ม media query 4 ระดับ (mobile ≤600-700px / tablet ≤900px / desktop / จอใหญ่-TV ≥1600px) ในทั้ง 3 stylesheet โดยไม่แตะสี/theme เดิม — ทดสอบจริงด้วย headless browser (Playwright + chromium) ครอบคลุม 13 ขนาดจอตั้งแต่ Galaxy Fold (280px) ถึง 4K TV (3840px)
- **แก้บั๊กตารางบนมือถือจริง**: เจอว่าที่ความกว้าง ~320-375px คอลัมน์ "Action" เลื่อนลับออกนอกจอโดยไม่มีสัญญาณว่าเลื่อนดูได้ (เหมือนปุ่มแก้ไข/ลบหายไป) — แก้โดยเปลี่ยนตารางบนมือถือทุกตารางจาก "เลื่อนแนวนอน" เป็น **การ์ดแนวตั้งแบบมีป้ายกำกับ** (label: value ต่อแถว) ผ่าน `data-label` attribute บนทุก `<td>` + CSS `content: attr(data-label)` — ยืนยันแล้วว่าที่ 280px ก็เห็นครบทุกคอลัมน์โดยไม่ต้องเลื่อน
- **เพิ่มขนาด font ทั่วเว็บ** ให้อ่านง่ายขึ้น (ไม่ใช่แค่ breakpoint TV เดิม) — ตั้ง `body{font-size:16px; line-height:1.6}` เป็น baseline ทุกหน้า (เดิมปุ่มต่างๆ ไม่ได้สืบทอด font-size จาก body ตาม CSS spec เลยเล็กเป็นพิเศษ ~13px) และเพิ่ม breakpoint ใหม่ `≥2200px` แยกจาก TV/monitor ทั่วไป เพราะจอ 4K จริง (3840px) ยังโดน container ครอบเท่าจอ 1600px เดิม ทำให้เนื้อหาดูเล็ก/หลงอยู่กลางจอเปล่าๆ
- **ปรับสมดุล UI `home_page.php`**: ย้ายปุ่ม "+" จากลอยมุมซ้ายเดี่ยวๆ (มีพื้นที่ว่างขวามหาศาล) ไปมุมขวาบนเหนือคอลัมน์ ACTION ให้ตรงกับรูปแบบปุ่มเพิ่มใน `race_page.php`, ลด `.border{min-height}` จาก 600px เหลือ 200px (การ์ดตารางไม่เหลือพื้นที่ว่างเยอะเกินเมื่อมีแมตช์น้อย), เปลี่ยน `margin-top` ของหัวข้อจาก % เป็นค่าคงที่ให้สม่ำเสมอทุกขนาดจอ
- ไฟล์ stylesheet จริงตอนนี้ชื่อ `style/home_pagena.css`, `style/race_pageasak.css`, `style/dashboardedS.css` (คนละชื่อกับที่เคยบันทึกไว้ก่อนหน้า เช่น `home_pageoo.css`/`race_pageas.css`/`dashboard.css` — อาจถูกเพื่อนร่วมทีม rename ไปแล้ว)
- **เพิ่มขนาด/น้ำหนัก font อีกรอบ** (ต่อจากบรรทัดข้างบน ผู้ใช้ขอเพิ่มอีกในวันเดียวกัน): บั๊มปี base `body{font-size}` จาก 16px → **18px** ทุกหน้า (`line-height` 1.6 → 1.65), เพิ่ม `font-weight:500` ให้ตัวเลข/ข้อความในทุก `<td>` ให้อ่านง่ายขึ้นโดยไม่ต้อง bold เต็ม, บั๊มขนาดหัวข้อ/ปุ่ม/label เกือบทุกจุดอีก ~1-3px, และ **เปลี่ยน font stack** จาก `Arial, Helvetica, sans-serif` (รองรับ glyph ภาษาไทยได้ไม่ดีบนหลาย OS) เป็น `"Noto Sans Thai", "Leelawadee UI", "Segoe UI", Tahoma, Arial, sans-serif` ให้ตรงกันทั้ง 3 stylesheet — ทดสอบ Playwright ซ้ำทุกขนาดจอ (280px-3840px) ยืนยันไม่มี overflow/wrap ผิดปกติเพิ่มจากขนาด font ที่ใหญ่ขึ้น (หมายเหตุ: sandbox ที่ทดสอบไม่มีฟอนต์ไทยติดตั้งจริง จึงตรวจสอบได้แค่ layout ไม่ใช่ความสวยงามของตัวอักษรไทย — อุปกรณ์จริงของผู้ใช้ทั่วไป (Windows/Android/iOS) มีฟอนต์เหล่านี้หรือใกล้เคียงติดตั้งมาให้แล้ว)
- ⚠️ **ยังไม่ได้ commit** — การแก้ไขทั้งหมดข้างบนอยู่ใน working tree ของ branch `feature/back-end,strug` เท่านั้น (`git status` ตอนจบ session: `dashboard.php`, `home_page.php`, `race_page.php`, `style/dashboardedS.css`, `style/home_pagena.css`, `style/race_pageasak.css` ทั้งหมด modified แต่ยัง unstaged)

## อัปเดตก่อนหน้า (11 ก.ค. 2026)
- **Merge งานของเพื่อนร่วมทีม (Teelemon)** ที่ push เข้า branch `feature/back-end,strug` เดียวกัน (commit UI-race_page) — แก้ conflict ใน `race_page.php` โดยรวม UI แบบ modal ของเพื่อนเข้ากับ logic stop_race/re-start ที่มีอยู่เดิม
- **เชื่อม `dashboard.php` เข้ากับฐานข้อมูลจริง** จากที่เดิมเป็น static placeholder — ดึงรายชื่อประเภทการแข่งขันของแมตช์ แล้วแสดง Top 5 ทีมแยกทีละประเภท (จัดอันดับด้วยน้ำหนักสูงสุด, ผ่านเกณฑ์ min_weight, tie-break ด้วยเวลาจับก่อนหลัง) พร้อมปุ่มกลับไป `race_page.php`
- **เพิ่มปุ่มแก้ไข/ลบ** ให้ครบทุกตารางที่ยังไม่มี: แมตช์ (`home_page.php`), ประเภทการแข่งขัน/ทีม/บันทึกจับปลา (`race_page.php`) — ทุกฟอร์มลบมี confirm dialog และถูกล็อกอัตโนมัติเมื่อแมตช์ stopped
- **แก้บั๊ก layout** ใน `style/dashboard.css`: `.container` เดิมใช้ `position: fixed; top: 0` ทำให้เนื้อหาที่ล้นจอ (เช่นมีหลายประเภทการแข่งขัน) เลื่อนดูไม่ได้เลย เปลี่ยนเป็น normal flow (`margin: auto`)
- **ปรับสไตล์ปุ่มให้เข้ากับธีมเว็บ**: เพิ่ม class `.row-actions`/`.edit-btn`/`.delete-btn` ใน `race_pageas.css` และ `home_pageoo.css`, ปรับปุ่มกลับใน `dashboard.css` ให้เป็นสีน้ำเงินปุ่มหลักตรงกับปุ่ม primary อื่นๆ ในเว็บ (#2563eb)
- Commit: `a532b57` (delete match + status labels), `8d05cd4` (merge เพื่อน), `3541961` (edit/delete ทุกตาราง + UI). Push ขึ้น `origin/feature/back-end,strug` เรียบร้อยแล้ว

## สิ่งที่ยังไม่เสร็จ
1. หน้า Podium สรุปผลอันดับ 1-3 แบบไฮไลต์ (เหรียญทอง/เงิน/ทองแดง) ตาม `Strugture.md` — ตอนนี้ `dashboard.php` แสดงเป็นตารางธรรมดา ยังไม่ใช่ podium
2. ฟีเจอร์ Export/Print ผลการแข่งขันเป็น PDF
3. ข้อความแจ้งเตือน success/error เมื่อ submit ฟอร์ม
4. ยกระดับความปลอดภัย: รหัสผ่านใน `admin_user` ยังเก็บเป็น plain text ต้องเปลี่ยนไปใช้ `password_hash()`/`password_verify()`, และ DB credentials ใน `db_connect.php` ยัง hardcode อยู่
5. **Commit + push** งาน responsive/font/table-proportion ของ 12 ก.ค. 2026 (ดูหัวข้อ ⚠️ ด้านบน — ยังอยู่ใน working tree เท่านั้น)

## ข้อสังเกตด้านความปลอดภัย (ควรแก้ก่อนใช้งานจริง)
- **รหัสผ่าน plain-text**: `sign.php` เทียบรหัสผ่านด้วย `===` ตรงๆ กับค่าที่เก็บใน DB (`admin/admin`) — ควรใช้ password hashing
- **DB credentials hardcode**: `db_connect.php` ฝัง user/pass ตรงในโค้ด ควรย้ายไป environment variable
- ส่วนอื่น (query, output) ใช้ prepared statements และ `htmlspecialchars` ครบถ้วนดีแล้ว ป้องกัน SQL Injection และ XSS เบื้องต้นได้
