FROM php:8.2-fpm-alpine

# ติดตั้ง System Dependencies ที่จำเป็น
RUN apk add --no-cache \
    nginx \
    wget \
    icu-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    $PHPIZE_DEPS

# ติดตั้ง PHP Extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# ติดตั้ง Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ตั้งค่า Working Directory
WORKDIR /var/www

# ก๊อปปี้ไฟล์โปรเจกต์
COPY . .

# ติดตั้ง Dependencies แบบ Optimize
RUN composer install --no-dev --optimize-autoloader

# ตั้งค่าสิทธิ์ไฟล์
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# ก๊อปปี้ไฟล์คอนฟิก Nginx (เดี๋ยวเราจะสร้างในขั้นตอนถัดไป)
COPY docker/nginx/render.conf /etc/nginx/http.d/default.conf

# เปิดพอร์ต 80
EXPOSE 80

# คำสั่งรัน: สั่ง Migrate อัตโนมัติ แล้วค่อยเปิด PHP-FPM และ Nginx
CMD php artisan migrate --force && php-fpm -D && nginx -g "daemon off;"