# CGS
CGS –Clearance for Graduating Students: is a centralized digital platform designed to streamline and manage the clearance process for graduating students. It enables final-year undergraduate and postgraduate students to complete academic, financial, and administrative clearance requirements electronically before graduation.

Digital clearance for UG/PG students. Built with PHP, MySQL, HTML5/CSS3/JS. Mobile‑first.

## Features
- Self‑registration; admin assigns roles (Admin, Student, Signatory, Academic Admin)
- UG & PG workflows mapped to real signatories/departments
- Progress tracker & audit logging
- Web & CLI interactive installer (no manual config edits)

## Local Dev (XAMPP)
1. Copy repo folder to `C:\xampp\htdocs\CGS` (Windows) or `/Applications/XAMPP/htdocs/CGS` (macOS).
2. Start Apache & MySQL in XAMPP.
3. Visit `http://localhost/CGS/install/install.php` and complete the wizard.
4. Log in with the admin you created.

## GitHub
```bash
# one‑time
mkdir CGS && cd CGS
# add the files from this repo layout
git init
git add .
git commit -m "chore: bootstrap CGS"
git branch -M main
git remote add origin https://github.com/rwabig/CGS.git
git push -u origin main
```

> Do **not** commit `.env`. Credentials remain local.

## Notes
- Set `APP_URL` in installer if using a subfolder.
- For pretty URLs beyond this starter, add a front controller and router.
- Add SMTP in `src/Mailer.php` if you want email notifications.
