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
RUN chown -R www-data:www-data /var/www/html