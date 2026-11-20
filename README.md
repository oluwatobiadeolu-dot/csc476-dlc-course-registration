# DLC Course Registration Prototype (CSC 476)

Standalone prototype of an enhanced course registration system for the Distance Learning Centre, University of Ibadan.
It uses made-up data and does not connect to the University's portal.

**Project Team**
- Adeolu, oluwatobi Inioluwa (E050797): Project coordination, proposal and questionnaire preparation, prototype testing, and preparation and integration of the final writeup.
- Olaolu, Hephzibah Samuel (E050613): Design and implementation of the web application, including student and administrator functions and user-interface improvements.
- Obarisiagbon, Stephenie (E050793): Drafting of Chapters One to Three and the reference list.

Built with PHP, MySQL, HTML, CSS, JavaScript, Bootstrap 5 and Chart.js (Bootstrap and Chart.js load from a CDN).

## Setup (XAMPP)
1. Start Apache and MySQL in the XAMPP control panel.
2. Put this folder in `C:\xampp\htdocs\` so it is `C:\xampp\htdocs\dlc-registration`.
3. In phpMyAdmin, create a database named `dlc_registration`, select it, then import `database/schema.sql`.
4. Already imported an earlier schema? Import `database/migration_optional_features.sql` once instead.
5. Optional: import `database/sample_data.sql` to fill the dashboard with test data.
6. Open http://localhost/dlc-registration/
7. If your folder name, database name or MySQL password is different, edit `config/config.php`.

On free hosting (for example InfinityFree), create the database in the control panel, select it in phpMyAdmin, import the same files, and put the host, database name, user and password from the panel into `config/config.php`. If the site is not in a subfolder, set `BASE_URL` to an empty string.

## Logins
- Admin: `admin@dlc.test` / `Admin@12345`
- Sample student: `ada@dlc.test` / `Student@123` (needs sample_data.sql)
- New students sign up on the register page.

## Features
- Student sign-up and login, admin login (hashed passwords)
- Admin: add and edit courses, save as draft, review and publish
- Student: search courses (carryovers from lower levels included), cart, register, registration slip, no duplicate registration
- Deadlines set by the admin, banner for students, late registration and drops are refused
- Course reports from students, answered by the admin
- Course drop with a confirmation step
- Admin dashboard: registrations by course, students by level, students with no registrations
- Compulsory and elective labels, unit calculator and admin-set unit limits

Out of scope: fee payment, exams, results, transcripts, email or SMS, connection to the real portal.

Test steps are in `docs/TEST_GUIDE.md`.
