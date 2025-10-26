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

#запускаем веб сервис
apache2 -D FOREGROUND -f /etc/apache2/apache2.conf