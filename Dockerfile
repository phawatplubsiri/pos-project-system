FROM php:8.2-fpm-alpine

# ติดตั้ง Library ที่จำเป็น
RUN apk add --no-cache nginx wget icu-dev libpng-dev libxml2-dev libzip-dev oniguruma-dev $PHPIZE_DEPS
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# ติดตั้ง Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# ติดตั้ง PHP Dependencies แบบ Optimize
RUN composer install --no-dev --optimize-autoloader

# ตั้งค่าสิทธิ์ไฟล์
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# ใส่ไฟล์คอนฟิก Nginx ที่เพิ่งสร้าง
COPY docker/nginx/render.conf /etc/nginx/http.d/default.conf

EXPOSE 80

# คำสั่งรัน PHP และ Nginx พร้อมกันเมื่อเปิดเครื่อง
CMD php-fpm -D && nginx -g "daemon off;"