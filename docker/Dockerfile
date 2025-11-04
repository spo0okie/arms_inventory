FROM php:8.2-apache

ENV TZ=Europe/Moscow

# передаем токен github в виде аргумента CLI
ARG COMPOSER_GITHUB_TOKEN
ENV COMPOSER_AUTH='{"github-oauth":{"github.com":"${COMPOSER_GITHUB_TOKEN}"}}'

# Установка всех зависимостей в одном слое + очистка кэша
# git для composer
# mysql для ожидания старта БД
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    default-mysql-client \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libmagickwand-dev \
    libcurl4-openssl-dev \
    libgmp-dev \
    libldap2-dev \
    && docker-php-ext-install \
        intl pdo pdo_mysql zip gd mbstring xml bcmath gmp ldap curl \
    && pear config-set http_proxy "$HTTP_PROXY" \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Настройка Apache
RUN rm -f /etc/apache2/apache2.conf && rm -Rf /etc/apache2/sites-enabled/*
COPY apache/apache.conf /etc/apache2/apache2.conf
COPY apache/site.conf /etc/apache2/sites-enabled/site.conf
COPY etc/php-custom.ini /usr/local/etc/php/conf.d/99-custom.ini

# Imagick политика
COPY etc/imagick-policy.xml /etc/ImageMagick-6/policy.xml

# Добавляем приложение (см. .dockerignore)
#RUN mkdir /var/www/.composer && chown -R www-data:www-data /var/www
COPY --chown=www-data:www-data .. /var/www/arms
RUN rm -f /var/www/arms/web/index*
COPY prod/index.php /var/www/arms/web/index.php


# Установка зависимостей
WORKDIR /var/www/arms
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/var/www/arms --filename=composer.phar
RUN su -s /bin/sh www-data -c "php composer.phar install --no-dev --optimize-autoloader -n" && \
    rm -rf /var/www/.composer

# Скрипт запуска
COPY script/run.sh /bin/arms-run.sh
RUN chmod 555 /bin/arms-run.sh

CMD ["arms-run.sh"]