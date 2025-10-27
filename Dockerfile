# syntax=docker/dockerfile:1
FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    sqlite3 \
    libsqlite3-dev \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install pdo pdo_sqlite

WORKDIR /var/www/html
COPY . /var/www/html

EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
