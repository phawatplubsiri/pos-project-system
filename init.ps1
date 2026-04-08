Write-Host "Starting POS Project in Docker (Windows)..." -ForegroundColor Cyan

# 1. Start Docker containers
docker-compose up -d --build

# 2. Install PHP Dependencies
Write-Host "Installing Composer dependencies..." -ForegroundColor Yellow
docker-compose exec app composer install

# 3. Generate App Key if not exists
if (!(Test-Path .env)) {
    Write-Host "Creating .env file from .env.example..." -ForegroundColor Yellow
    Copy-Item .env.example .env
}
docker-compose exec app php artisan key:generate

# 4. Run Migrations and Seeders
Write-Host "Running migrations and seeding..." -ForegroundColor Yellow
docker-compose exec app php artisan migrate --seed

# 5. Install Node Dependencies
Write-Host "Installing Node dependencies..." -ForegroundColor Yellow
docker-compose exec node npm install

Write-Host "Project is ready! Access it at http://localhost:8000" -ForegroundColor Green
