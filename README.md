# CSC540 Workout Log

A web app for logging workouts and tracking training history, built for CSC 540. Users log in, land on a personal dashboard, and (once those pages are built) can record workouts and look back through past sessions.

It runs on a local XAMPP stack: PHP, MariaDB, and Apache, with Bootstrap 5.3 for the front end and Chart.js for the history charts.

## Project Status

| Feature | Status |
|---|---|
| Login, logout, and account roles (admin, user, guest) | Done |
| User dashboard | Done (layout and placeholders) |
| Log a workout (create with your own type and bodyweight, add sets, start from a past workout) | Done |
| Workout history (charts of your training, then every workout newest first) | Done |
| Weekly stats on the dashboard | Waiting on workout tables |
| Forgot password | Not started (form points to a page that doesn't exist yet) |

## Setup

1. **Install XAMPP** from [apachefriends.org](https://www.apachefriends.org/index.html).
2. **Clone the repo into XAMPP's web folder** so the folder name stays `CSC540-Workout_Log`:
   ```bash
   cd /opt/lampp/htdocs          # Windows: C:\xampp\htdocs
   git clone https://github.com/joshkukl/CSC540-Workout_Log.git
   ```
   On Linux, don't clone with `sudo`, or the files will be owned by root and git will refuse to work in the folder. If `htdocs` isn't writable, create the empty folder with `sudo mkdir`, `sudo chown` it to yourself, then clone into it.
3. **Start Apache and MySQL** from the XAMPP control panel, or on Linux:
   ```bash
   sudo /opt/lampp/lampp start
   ```
4. **Create the database** by visiting:
   http://localhost/CSC540-Workout_Log/php/start.php

   This creates a database called `login_db` and fills it with the test accounts below.
   **Running it again deletes every table in `login_db` and starts over**, so only run it for a fresh install.
5. **Open the app** at http://localhost/CSC540-Workout_Log/

If your MySQL root user has a password, set it in both `php/config.php` and `php/start.php`.

## Test Accounts

| Username | Password | Role | Lands on |
|---|---|---|---|
| `johndoe` | `SCSU2024` | Administrator | `/admin` (list of all users) |
| `janesmith` | `SCSU2025` | User | `/user` (workout dashboard) |
| `charliebrown` | `SCSU2026` | Guest | `/guest` |

Use `janesmith` to see the workout dashboard.

## Project Structure

```
CSC540-Workout_Log/
├── index.php            Login page
├── create_account.php   Sign-up form
├── forgot_pass.php      Account recovery form (not working yet)
├── failed.html          Shown when a session is missing, then redirects to login
├── template.php         Blank starting point for new pages
├── admin/               Administrator dashboard
├── user/                Workout dashboard for regular users
├── guest/               Guest landing page
├── include/             Shared page parts: head, nav bar, footer
├── php/                 Back end: config, login, sessions, role checks, DB setup
├── css/style.css        App styles and colours
├── js/main.js           App JavaScript
└── img/                 Images and favicon
```

## Adding a Page

Copy the structure of `user/index.php`:

1. Include `php/session.php` and `php/path.php` at the top.
2. Include the role check for who should see the page, e.g. `php/check_user.php`.
3. Set `$page_name` and `$page_title`.
4. Use `include/head.php`, `include/header.php`, and `include/footer.php` for the shared layout.

Use `BASE_URL` (or `SRC_PATH` for files in `php/`) when linking to other pages or files, so links work from any folder.

If you rename the project folder, update `$directory` in `php/path.php` to match.

## Configuration

- **Database connection:** `php/config.php` (and `php/start.php` for the setup script)
- **Project folder name:** `php/path.php`
- **Timezone:** `php/config.php`, currently `America/New_York`
- **Colours:** the variables at the top of `css/style.css`

## Credits

Built on a login template by Joshua Connor (connorj4@southernct.edu) for Southern Connecticut State University coursework. The template is meant as a learning tool for school projects only, not for production use.
