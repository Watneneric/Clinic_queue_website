# Clinic Appointment and Queue Management (PHP + MySQL)

 This is local prototype using PHP, MySQL, HTML and CSS. Under my course-unit, CS Research 1, 
 Name: Watnen Eric Oywak
 REG NO: M23B23/040
 Access No: B21257

## Features
- Patient registration
- Appointment booking
- Prevents a duplicate active appointment at the same date/time
- Appointment listing and cancellation
- Add appointments to today's queue
- Queue status updates
- PDO prepared statements, basic CSRF tokens, output escaping

## Testing Requirements
- XAMPP (Apache + PHP + MySQL/MariaDB)
- A browser

## Install on Windows with XAMPP
1. Install and open XAMPP.
2. Start **Apache** and **MySQL**.
3. Copy the `clinic_queue_php` folder into `C:\xampp\htdocs\`.
4. Open `http://localhost/phpmyadmin/`.
5. Import `database/schema.sql` using the Import tab.
6. Check `config/database.php`. The default XAMPP MySQL settings are usually user `root` and an empty password.
7. Open `http://localhost/clinic_queue_php/`.

## Test workflow
1. Register two fictional patients(dummy data).
2. Book an appointment for today.
3. Open the dashboard and add that appointment to today's queue.
4. Change the queue status from Waiting to Called, In consultation, and Completed.
5. Try to book two active appointments for the same date/time. The app blocks the duplicate slot.

## Important notes
- This is a local learning prototype, not production-ready healthcare software.
- Im using fictional/dummy data only while developing.
- Before real clinic use, add staff authentication and roles, audit logs, backups, secure deployment/HTTPS, stricter scheduling rules, and a reviewed privacy/consent process.
- SMS is not integrated yet.
- The sample database config is for local development only. Do not expose it on a public server.
