# 🚀 RAVON RESTAURANT POS - INSTALLATION GUIDE

## Quick Installation Steps

### Step 1: Install Laravel Framework
```powershell
# Navigate to project directory
cd "c:\Users\Campus\Desktop\Ravon Resturent"

# Create Laravel project in temp folder
composer create-project laravel/laravel temp "11.*"

# Move Laravel files to current directory
Move-Item -Path temp\* -Destination . -Force
Remove-Item -Recurse -Force temp

# Our custom files (models, services, migrations, seeders) are already in place
```

### Step 2: Install Required Packages
```powershell
# Install Spatie Permission for roles & permissions
composer require spatie/laravel-permission

# Install Laravel Sanctum for API authentication
composer require laravel/sanctum

# Install Pusher SDK for broadcasting
composer require pusher/pusher-php-server

# Install all dependencies
composer install
```

### Step 3: Environment Configuration
```powershell
# Copy environment file
Copy-Item .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Configure .env File
Open `.env` and update:

```env
APP_NAME="Ravon Restaurant POS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ravon_pos
DB_USERNAME=root
DB_PASSWORD=your_password

# Broadcasting (Pusher or Soketi)
BROADCAST_DRIVER=pusher

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Pusher Configuration (or use Soketi as free alternative)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

# Soketi Alternative (Open Source Pusher)
# PUSHER_APP_ID=app-id
# PUSHER_APP_KEY=app-key
# PUSHER_APP_SECRET=app-secret
# PUSHER_HOST=127.0.0.1
# PUSHER_PORT=6001
# PUSHER_SCHEME=http

# Tax & Service Charge
TAX_PERCENTAGE=10
SERVICE_CHARGE_PERCENTAGE=5

# Printer Settings
PRINTER_ENABLED=true
PRINTER_TYPE=thermal
PRINTER_WIDTH=48
```

### Step 5: Create Database
```powershell
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE ravon_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Step 6: Publish Vendor Assets
```powershell
# Publish Sanctum config
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Publish Spatie Permission config & migrations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### Step 7: Run Migrations & Seeders
```powershell
# Run all migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed

# Or run both together (fresh install)
php artisan migrate:fresh --seed
```

### Step 8: Configure Broadcasting
```powershell
# Uncomment BroadcastServiceProvider in config/app.php
# Edit config/app.php and uncomment:
# App\Providers\BroadcastServiceProvider::class,
```

### Step 9: Install Frontend Dependencies (Optional)
```powershell
# Install Node.js packages
npm install

# Install Laravel Echo & Pusher
npm install --save laravel-echo pusher-js

# Build assets
npm run dev
```

### Step 10: Start Development Server
```powershell
# Start Laravel server
php artisan serve

# In separate terminal, start queue worker
php artisan queue:work

# In separate terminal, start WebSocket server (Soketi)
# Install Soketi globally first: npm install -g @soketi/soketi
soketi start
```

---

## 🎉 Default Login Credentials

After seeding, you can login with:

| Role | Email | Password | Employee ID |
|------|-------|----------|-------------|
| **Admin** | admin@ravon.com | password | EMP001 |
| **Cashier** | cashier@ravon.com | password | EMP002 |
| **Waiter** | waiter@ravon.com | password | EMP003 |
| **Kitchen** | kitchen@ravon.com | password | EMP004 |

---

## 📋 Verification Steps

### Test API Endpoints
```powershell
# Login and get token
curl -X POST http://localhost:8000/api/auth/login `
  -H "Content-Type: application/json" `
  -d '{"email":"admin@ravon.com","password":"password"}'

# Get floors with tables
curl http://localhost:8000/api/tables `
  -H "Authorization: Bearer YOUR_TOKEN"

# Get menu categories
curl http://localhost:8000/api/menu/categories `
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Check Database
```powershell
php artisan tinker

# Check users
>>> User::count()
# Should return: 4

# Check tables
>>> Table::count()
# Should return: 48

# Check items
>>> Item::count()
# Should return: 20+

# Check roles
>>> \Spatie\Permission\Models\Role::pluck('name')
# Should return: admin, cashier, waiter, kitchen
```

---

## 🔧 Troubleshooting

### Issue: "Class not found" errors
```powershell
composer dump-autoload
php artisan optimize:clear
```

### Issue: Database connection failed
- Check MySQL is running: `mysql --version`
- Verify credentials in `.env`
- Test connection: `php artisan migrate:status`

### Issue: Permission denied errors
```powershell
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Issue: Broadcasting not working
- Ensure Redis is running: `redis-cli ping` (should return PONG)
- Check Soketi/Pusher is running
- Verify `.env` broadcast settings
- Uncomment `BroadcastServiceProvider` in `config/app.php`

### Issue: Queue jobs not processing
```powershell
# Start queue worker
php artisan queue:work --tries=3

# For development, use:
php artisan queue:listen
```

---

## 🌐 WebSocket Server Setup (Soketi)

### Install Soketi (Free Pusher Alternative)
```powershell
# Install globally
npm install -g @soketi/soketi

# Create soketi.json config
```

### soketi.json Configuration
Create `soketi.json` in project root:

```json
{
  "debug": true,
  "host": "0.0.0.0",
  "port": 6001,
  "appManager.array.apps": [
    {
      "id": "app-id",
      "key": "app-key",
      "secret": "app-secret",
      "maxConnections": 100,
      "enableClientMessages": true,
      "enabled": true,
      "maxBackendEventsPerSecond": 100,
      "maxClientEventsPerSecond": 100,
      "maxReadRequestsPerSecond": 100
    }
  ]
}
```

### Start Soketi
```powershell
soketi start --config=soketi.json
```

---

## 📦 Production Deployment Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Setup SSL certificate (Let's Encrypt)
- [ ] Configure queue worker with Supervisor
- [ ] Setup daily backup cron job
- [ ] Configure firewall (UFW)
- [ ] Install fail2ban for security
- [ ] Setup monitoring (Laravel Telescope)

See `docs/DEPLOYMENT.md` for full production guide.

---

## 🎯 Next Steps

1. **Test API Endpoints** - Use Postman or Thunder Client
2. **Build Frontend UI** - Choose Livewire or Vue.js
3. **Integrate Printer** - Setup QZ Tray for thermal printing
4. **Customize Settings** - Adjust tax rates, printer templates
5. **Add Sample Data** - Create more menu items and categories

---

## 📞 Support

For issues or questions:
- Check `docs/` folder for detailed documentation
- Review Laravel 11 documentation: https://laravel.com/docs/11.x
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Laravel Echo: https://laravel.com/docs/11.x/broadcasting

**Happy Coding! 🚀**
