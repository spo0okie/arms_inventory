# syntax=docker/dockerfile:1
FROM php:8.4.28-apache-trixie

ENV TZ=Europe/Moscow

# Токен GitHub для composer пробрасывается BuildKit-секретом composer_auth
# (см. шаг установки зависимостей ниже), чтобы не оседать в истории образа.

# Установка всех зависимостей в одном слое + очистка кэша
# git для composer
# mysql для ожидания старта БД
# apache2 ставим из Debian, чтобы подтянуть обновление безопасности даже при сборке со старого базового образа.
# PDO, mbstring, xml и curl уже встроены в PHP официального образа.
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    git \
    default-mysql-client \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libmagickwand-7.q16-dev \
    libgmp-dev \
    libldap-dev \
    && dpkg --compare-versions "$(dpkg-query -W -f='${Version}' apache2-bin)" ge '2.4.71-1~deb13u1' \
    && docker-php-ext-install -j4 \
        intl pdo_mysql zip gd bcmath gmp ldap \
    && pear config-set http_proxy "$HTTP_PROXY" \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Настройка Apache
RUN rm -f /etc/apache2/apache2.conf && rm -Rf /etc/apache2/sites-enabled/*
COPY docker/apache/apache.conf /etc/apache2/apache2.conf
COPY docker/apache/site.conf /etc/apache2/sites-enabled/site.conf
COPY docker/etc/php-custom.ini /usr/local/etc/php/conf.d/99-custom.ini

# Политика Imagick для ImageMagick 7 из Debian trixie.
COPY docker/etc/imagick-policy.xml /etc/ImageMagick-7/policy.xml

# Добавляем приложение (см. .dockerignore)
#RUN mkdir /var/www/.composer && chown -R www-data:www-data /var/www
COPY --chown=www-data:www-data . /var/www/arms
RUN rm -f /var/www/arms/web/index*
COPY docker/prod/index.php /var/www/arms/web/index.php


# Установка зависимостей
WORKDIR /var/www/arms
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/var/www/arms --filename=composer.phar
# composer ходит в GitHub API за vcs-пакетами (в т.ч. форками dev-master). Без токена это
# упирается в rate-limit и падает с "Could not authenticate against github.com".
# Токен берём из BuildKit-секрета composer_auth
# (содержимое = auth.json), кладём в writable COMPOSER_HOME (домашний /var/www у www-data
# не записываемый -> иначе "Cannot create cache directory").
RUN --mount=type=secret,id=composer_auth \
    export COMPOSER_HOME=/tmp/.composer; \
    mkdir -p "$COMPOSER_HOME"; \
    if [ -s /run/secrets/composer_auth ]; then cp /run/secrets/composer_auth "$COMPOSER_HOME/auth.json"; fi; \
    chown -R www-data:www-data "$COMPOSER_HOME"; \
    su -s /bin/sh www-data -c "COMPOSER_HOME=$COMPOSER_HOME php composer.phar install --no-dev --optimize-autoloader -n" && \
    rm -rf "$COMPOSER_HOME"

# Скрипт запуска может быть выгружен с CRLF в рабочей копии Windows.
COPY docker/script/run.sh /bin/arms-run.sh
RUN sed -i 's/\r$//' /bin/arms-run.sh && chmod 555 /bin/arms-run.sh

CMD ["arms-run.sh"]
