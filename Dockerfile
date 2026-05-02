<<<<<<< HEAD
# ใช้ PHP 8.2 พร้อม Apache
FROM php:8.2-apache

# ติดตั้งส่วนเสริมที่จำเป็น (mysqli สำหรับฐานข้อมูล และ gd/zip สำหรับ mPDF)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install mysqli gd zip

# เปิดใช้งาน mod_rewrite ของ Apache (จำเป็นสำหรับบางระบบ)
RUN a2enmod rewrite

# คัดลอกโค้ดทั้งหมดในเครื่องเราเข้าไปใน Container
COPY . /var/www/html/

# กำหนดสิทธิ์ให้ Apache เข้าถึงไฟล์ได้
=======
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite

COPY . /var/www/html/

WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader

>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
RUN chown -R www-data:www-data /var/www/html