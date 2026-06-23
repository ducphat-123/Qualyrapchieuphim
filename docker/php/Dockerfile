FROM php:8.2-apache

# Cài extension PHP cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        gd \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Bật mod_rewrite cho Apache (cần cho routing)
RUN a2enmod rewrite

# Set DocumentRoot và working dir
WORKDIR /var/www/html

# Copy toàn bộ project vào container
COPY . .

# Cài PHPMailer qua Composer (nếu composer.json có khai báo)
RUN if [ -f composer.json ] && [ -s composer.json ]; then \
        composer install --no-interaction --no-dev --optimize-autoloader; \
    fi

# Phân quyền
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80