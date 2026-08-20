#!/bin/bash

#ждем БД
while ! mysqladmin ping -h"arms-db" --skip-ssl --silent; do
	echo "waiting for mysql"
    sleep 1
done

#переходим в рабочую папку
cd /var/www/arms

#выполняем миграцию БД
chmod 555 ./yii
php ./yii migrate --migrationPath=@yii/rbac/migrations/ --interactive=0
php ./yii migrate --interactive=0

#намеренно и обязательно: миграции выше идут от root и создают runtime/logs, runtime/cache
#и т.п. от root, а apache работает от www-data. Без смены владельца веб-процесс не может
#писать в runtime - и ошибки самого веба уходят в никуда, диагностировать нечем.
chown -R www-data:www-data /var/www/arms/runtime

#запускаем веб сервис
apache2 -D FOREGROUND -f /etc/apache2/apache2.conf