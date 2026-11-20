FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js tailwind.config.js ./
RUN npm run build

FROM php:8.4-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends unzip git libonig-dev libxml2-dev libwebp-dev libpng-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo_mysql mbstring dom xmlwriter \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 APP_ENV=testing
WORKDIR /app
COPY artisan composer.json composer.lock phpunit.xml ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY tests ./tests
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p storage/app/public storage/logs bootstrap/cache \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
    && composer install --no-interaction --prefer-dist
CMD ["sh", "-ec", "export APP_KEY=base64:$(php -r 'echo base64_encode(random_bytes(32));'); exec php vendor/bin/phpunit"]
