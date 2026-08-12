FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM composer:2 AS dependencies
WORKDIR /app
RUN docker-php-ext-install bcmath
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress \
    --optimize-autoloader

FROM php:8.2-cli-alpine
WORKDIR /app
RUN apk add --no-cache libzip postgresql-libs \
    && apk add --no-cache --virtual .build-deps libzip-dev postgresql-dev \
    && docker-php-ext-install bcmath pdo_pgsql zip \
    && apk del .build-deps
COPY . .
COPY --from=dependencies /app/vendor ./vendor
COPY --from=dependencies /app/bootstrap/cache ./bootstrap/cache
COPY --from=frontend /app/public/build ./public/build
RUN mkdir -p storage/framework/cache storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && chmod +x docker/start.sh
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr \
    SESSION_DRIVER=database CACHE_STORE=database QUEUE_CONNECTION=sync
EXPOSE 8080
CMD ["./docker/start.sh"]
