# PLP Clinic Appointment System

A web-based clinic management application built for **Pamantasan ng Lungsod ng Pasig (PLP)**. It digitizes how the university health clinic schedules, manages, and tracks medical appointments.

**Screenshots and project details:** [soriato.github.io/portfolio/project/clinic-appointment-system](https://soriato.github.io/portfolio/project/clinic-appointment-system)

## Features

**Clinic staff (admin)**
- Appointment management with capacity control
- Bulk appointment creation and cancellation per course and section
- Clinic schedule, availability, and time-block management
- Clinic services and staff account management
- Exportable reports (Excel and PDF)
- Database backup and restore
- System settings

**Patients (students, faculty, and staff)**
- Sign up with university ID verification
- Book appointments and track their status
- View appointment and medical history
- Manage personal information

**Security**
- Login attempt locking against brute-force attacks
- Role-based access control
- Password reset with an emailed verification code

## Tech Stack

- **Backend:** PHP (vanilla)
- **Frontend:** HTML, CSS, JavaScript (vanilla)
- **Database:** MySQL / MariaDB (XAMPP)
- **Libraries:** [PHPMailer](https://github.com/PHPMailer/PHPMailer) (email), [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) (Excel export), [FPDF](http://www.fpdf.org/) (PDF export)

## Running Locally

1. Install [XAMPP](https://www.apachefriends.org/) (PHP 8.1 or newer) and [Composer](https://getcomposer.org/).
2. Clone this repository into `xampp/htdocs`:
   ```bash
   git clone https://github.com/soriaToV2/clinic-appointment-system.git
   ```
3. Install the PHP dependencies:
   ```bash
   cd clinic-appointment-system
   composer install
   ```
4. Start Apache and MySQL in XAMPP, then create a database named `finals` in phpMyAdmin and import the database schema.
   Database credentials are set in `connection.php`.
5. Copy `mail_config.example.php` to `mail_config.php` and add your Gmail address and a [Gmail App Password](https://myaccount.google.com/apppasswords). This is used to email password reset codes.
6. Open `http://localhost/clinic-appointment-system` in your browser.

## Author

**Marc Angelo Soria** · [Portfolio](https://soriato.github.io/portfolio/) · [GitHub](https://github.com/soriaToV2)
