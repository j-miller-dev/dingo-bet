# Dingo Bet - Sports Betting App

A modern sports betting application built with Laravel 11, Vue.js 3, TypeScript, and Inertia.js.

## 🚀 Tech Stack

- **Backend:** Laravel 11, PHP 8.4
- **Frontend:** Vue.js 3, TypeScript, Inertia.js
- **Styling:** Tailwind CSS
- **Database:** PostgreSQL (Railway)
- **Build Tool:** Vite
- **Authentication:** Laravel Breeze
- **Hosting:** Railway

## 📋 Prerequisites (EndeavourOS/Arch Linux)

### Install Required Packages

```bash
# Update system
sudo pacman -Syu

# Install PHP and required extensions
sudo pacman -S php php-intl php-sqlite php-gd php-zip php-curl php-mbstring php-xml php-tokenizer php-pgsql php-bcmath

# Install Composer
sudo pacman -S composer

# Install Node.js and npm
sudo pacman -S nodejs npm

# Install Git (if not already installed)
sudo pacman -S git
```

### Optional Development Tools

```bash
# Install Cursor editor (via AUR)
yay -S cursor-bin

# Or VS Code
sudo pacman -S code

# Database GUI (optional)
yay -S dbeaver

# Better terminal (optional)
sudo pacman -S zsh
```

## 🛠 Project Setup

### 1. Clone Repository

```bash
# Navigate to your development directory
cd ~/Documents  # or your preferred location

# Clone the repository
git clone https://github.com/yourusername/dingo-bet.git
cd dingo-bet
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Set proper permissions for Laravel
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database (.env)

**Important:** Use the shared Railway PostgreSQL database for seamless pair programming.

```env
APP_NAME="Dingo Bet"
APP_ENV=local
APP_KEY=base64:your_generated_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

# Railway PostgreSQL Database (Shared)
DB_CONNECTION=pgsql
DB_HOST=caboose.proxy.rlwy.net
DB_PORT=30207
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=MEANBURDGWZOxUwuapwlKiqoMBqSYXHu

# Local Development Settings
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
QUEUE_CONNECTION=sync

# Mail Settings (Development)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@dingo-bet.local"
MAIL_FROM_NAME="${APP_NAME}"

# Vite Configuration
VITE_APP_NAME="${APP_NAME}"
```

### 5. Verify Database Connection

```bash
# Test database connection
php artisan migrate:status

# Should show existing migrations from shared database
```

### 6. Build Frontend Assets

```bash
# Build assets for development
npm run dev
```

## 🏃‍♂️ Running the Application

### Start Development Servers

```bash
# Terminal 1: Laravel backend
php artisan serve
# Accessible at: http://localhost:8000

# Terminal 2: Vite frontend (hot reload)
npm run dev
# Vite server runs on: http://localhost:5173
```

### Verify Setup

1. Visit `http://localhost:8000`
2. Test user registration
3. Verify data appears in shared database

## 🔄 Pair Programming Workflow

### Git-Based Collaboration

```bash
# Before starting work
git pull origin main

# After making changes
git add .
git commit -m "Descriptive commit message"
git push origin main

# On other machine
git pull origin main
npm run dev  # Rebuild if frontend changes
```

### Shared Database Benefits

- ✅ **Same data** across all development environments
- ✅ **Test users** persist between machines
- ✅ **Real-time collaboration** with shared state
- ✅ **Production parity** - same database as live site

## 🐛 Troubleshooting

### Permission Issues

```bash
# Fix Laravel permissions
sudo chown -R $USER:$USER ~/Documents/dingo-bet
chmod -R 755 ~/Documents/dingo-bet
chmod -R 775 storage bootstrap/cache
```

### PHP Extensions Missing

```bash
# Check installed extensions
php -m | grep pgsql
php -m | grep mbstring

# Install missing extensions
sudo pacman -S php-{extension-name}

# Restart PHP-FPM (if using)
sudo systemctl restart php-fpm
```

### Database Connection Issues

```bash
# Test connection via tinker
php artisan tinker
DB::connection()->getPdo()
exit

# Check PHP PostgreSQL support
php -m | grep pdo_pgsql
```

### Vite Build Issues

```bash
# Clear npm cache
npm cache clean --force

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install

# Check Node.js version
node --version  # Should be >= 18
```

### Common EndeavourOS Issues

```bash
# If composer is slow
composer config --global repo.packagist composer https://packagist.org

# If npm permissions are wrong
sudo chown -R $USER:$USER ~/.npm

# If PHP version conflicts
sudo pacman -S p