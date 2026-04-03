FROM dunglas/frankenphp:1-php8.4-bookworm

RUN apt update && apt install -y \
    git \
    unzip \
    acl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

RUN php bin/console asset-map:compile \
 && php bin/console cache:clear --env=prod \
 && php bin/console cache:warmup --env=prod

COPY docker/Caddyfile /etc/caddy/Caddyfile

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
