<?php

//Подгружаем наше Yii2 приложение для тестов
new yii\web\Application(require __DIR__ . '/../../config/test.php');
//загружаем в него демо БД
$sql = file_get_contents(__DIR__ . '/../_data/arms_demo.sql');
Yii::$app->db->createCommand($sql)->execute();