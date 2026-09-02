FROM php:8.3-cli
WORKDIR /app
COPY backend/composer.json backend/composer.lock backend/
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev unzip default-mysql-client && docker-php-ext-install pdo_mysql && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php --install-dir=/usr/local/bin --filename=composer && rm composer-setup.php && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY backend/ backend/
EXPOSE 8080
CMD ["sh", "-c", "php think run -H 0.0.0.0 -p ${PORT:-8080}"]
