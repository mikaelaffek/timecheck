FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer.json and composer.lock first to leverage Docker cache
COPY composer.json composer.lock* ./

# Create Laravel artisan file if it doesn't exist
RUN if [ ! -f artisan ]; then \
    echo '#!/usr/bin/env php' > artisan && \
    echo '<?php' >> artisan && \
    echo '' >> artisan && \
    echo 'define("LARAVEL_START", microtime(true));' >> artisan && \
    echo '' >> artisan && \
    echo 'require __DIR__."/vendor/autoload.php";' >> artisan && \
    echo '' >> artisan && \
    echo '$app = require_once __DIR__."/bootstrap/app.php";' >> artisan && \
    echo '' >> artisan && \
    echo '$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);' >> artisan && \
    echo '' >> artisan && \
    echo '$status = $kernel->handle(' >> artisan && \
    echo '    $input = new Symfony\\Component\\Console\\Input\\ArgvInput,' >> artisan && \
    echo '    new Symfony\\Component\\Console\\Output\\ConsoleOutput' >> artisan && \
    echo ');' >> artisan && \
    echo '' >> artisan && \
    echo '$kernel->terminate($input, $status);' >> artisan && \
    echo '' >> artisan && \
    echo 'exit($status);' >> artisan && \
    chmod +x artisan; \
    fi

# Install dependencies
RUN composer install --no-scripts

# Copy the rest of the application
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --optimize && \
    php artisan key:generate --force

# Set permissions
RUN chmod -R 777 storage bootstrap/cache

# Expose port 8000 and start php server
EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
