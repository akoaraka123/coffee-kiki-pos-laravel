# Khopi-Kiki POS System

A Point of Sale (POS) system for coffee shops, built with Laravel.

## Setup Instructions

### Initial Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd khopi-kiki
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure database in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=khopi_kiki
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations:
```bash
php artisan migrate
```

7. Build assets:
```bash
npm run build
```

8. Start development server:
```bash
php artisan serve
```

### Cloning to Another Computer

When cloning this project to another computer, follow these steps:

1. **Clone the repository:**
```bash
git clone <repository-url>
cd khopi-kiki
```

2. **Install dependencies:**
```bash
composer install
npm install
```

3. **Copy environment file:**
```bash
cp .env.example .env
```

4. **Configure database in `.env`:**
- Set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to match your MySQL database
- Set `APP_URL` to match your local URL (e.g., `http://localhost:8000`)

5. **Import the MySQL database:**
- Import your existing database backup (SQL file) using phpMyAdmin or command line
- **Important:** Product images are stored in the file system, not in the database

6. **Copy product images:**
- Product images are stored in `public/products/` directory
- These images are tracked by Git and should be included when cloning
- If images are missing after cloning, manually copy the `public/products/` folder from the original computer

7. **Generate application key:**
```bash
php artisan key:generate
```

8. **Clear caches:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

9. **Run storage link (only if using storage for images):**
```bash
php artisan storage:link
```

10. **Build assets:**
```bash
npm run build
```

11. **Start development server:**
```bash
php artisan serve
```

### Important Notes

- **Do NOT run `php artisan migrate` after importing a complete database** - this can cause conflicts. Only run migrations if new tables need to be created.
- **Product images** are stored in `public/products/` and are tracked by Git. They are not included in MySQL exports.
- If `public/products/` is missing, product images will not display even though the database contains the image paths.
- The storage link (`php artisan storage:link`) is only needed if you're using Laravel's storage filesystem for images. Current setup uses `public/products/` directly.

### Default Credentials

After initial setup, you'll need to create an admin account via the database or registration (if available).

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
