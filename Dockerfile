# ==============================================================================
# Dockerfile - Laravel 13 + Inertia Vue (PHP 8.4 FPM + Node 22 + Composer)
# ==============================================================================
FROM php:8.4-fpm-alpine

# Arguments
ARG USER_ID=1000
ARG GROUP_ID=1000

# Install system dependencies & build tools
RUN apk update && apk add --no-cache \
    curl \
    git \
    bash \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    shadow \
    nodejs \
    npm

# Configure & install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create system user to match host user UID/GID
RUN groupmod -g ${GROUP_ID} www-data || groupadd -g ${GROUP_ID} www-data || true \
    && usermod -u ${USER_ID} -g ${GROUP_ID} www-data || useradd -u ${USER_ID} -g ${GROUP_ID} -m www-data || true

# Setup directory permissions
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

# Switch to non-root user
USER www-data

# Expose PHP-FPM port
EXPOSE 9000

CMD ["php-fpm"]
