#!/bin/bash

echo "🚀 Starting POS Project in Docker..."

# 1. Start Docker containers
docker-compose up -d --build

# 2. Install PHP Dependencies
echo "📦 Installing Composer dependencies..."
docker-compose exec app composer install

# 3. Generate App Key if not exists
if [ ! -f .env ]; then
    echo "📄 Creating .env file from .env.example..."
    cp .env.example .env
fi
docker-compose exec app php artisan key:generate

# 4. Run Migrations and Seeders
echo "🗄️ Running migrations and seeding..."
docker-compose exec app php artisan migrate --seed

# 5. Install Node Dependencies
echo "📦 Installing Node dependencies..."
docker-compose exec node npm install

echo "✅ Project is ready! Access it at http://localhost:8000"
