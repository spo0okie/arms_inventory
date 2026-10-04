<?php
/**
 * Состояние входа рядом с именем в блоках «Входы пользователей» / «Входы на ПК»:
 *  - открытая сессия — отметка «сейчас» и сколько она длится;
 *  - открытая сессия на ОС, которая давно не выходит на связь, — «неизвестно» и кнопка ручного закрытия;
 *  - завершённый вход — давность входа, как и раньше.
 * Подробности (точное время, тип входа и окончания, отметки о неточном времени) — в подсказке.
 */

use app\models\LoginJournal;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\LoginJournal $model */
/** @var bool $static_view не показывать элементы управления */

if (!isset($static_view)) $static_view=false;

$datetime=static function ($dbTime) {
	return Yii::$app->formatter->asDatetime(LoginJournal::utcToTime($dbTime),'php:d.m.Y H:i');
};

$details=['Вход: '.$datetime($model->calc_time ?: $model->time).' ('.(LoginJournal::$types[$model->type] ?? '?').')'];
if (!empty($model->end_time)) {
	$details[]='Выход: '.$datetime($model->end_time).' ('.$model->endTypeName.')';
}
foreach ($model->flagsDescr as $flag) $details[]=$flag;
$title=implode('; ',$details);

if (!$model->isOpen) {
	echo ' '.Html::tag('span','('.$model->age.')',['title'=>$title]);
	return;
}

if (!$model->isStale) {
	echo ' '.Html::tag('span','сейчас',['class'=>'badge bg-success','title'=>$title])
		.' '.Html::tag('span','(с '.$model->age.')',['class'=>'text-muted','title'=>$title]);
	return;
}

//ОС молчит: сессия числится открытой, но подтвердить это некому
$comp=$model->comp;
$since=is_object($comp) && strlen((string)$comp->updated_at)
	? 'Компьютер не выходит на связь с '.$datetime($comp->updated_at)
	: 'Компьютер не опознан';
echo ' '.Html::tag('span','неизвестно',[
	'class'=>'badge bg-warning text-dark',
	'title'=>$since.' — что с сессией на самом деле, неизвестно. '.$title,
]);
if (!$static_view) {
	echo ' '.Html::a('<i class="fas fa-times"></i>',['/login-journal/close','id'=>$model->id],[
		'title'=>'Закрыть сессию вручную',
		'class'=>'text-danger',
		'data'=>['confirm'=>'Закрыть сессию вручную? Если компьютер вернётся на связь, он сообщит о сессии сам.'],
	]);
}
