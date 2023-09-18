<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;
if (!isset($show_archived)) $show_archived=Yii::$app->request->get('showArchived',true);
if (!isset($noDelete)) $noDelete=false;


if (is_object($model)) {
	if (!isset($display)) {
		$display=($model->archived&&!$show_archived)?'style="display:none"':'';
	};
	
	if (!isset($archClass)) {
		$archClass=$model->archived?'text-muted text-decoration-line-through archived-item':'';
	};

	//выбираем иконку
	$icon=$this->render('icon',compact('model'));
	
	$name=$model->name;
	//если в имени сервиса есть имя сайта (Телефония - челябинск)
	//и у сервиса всего один сайт и как раз этот
	//убираем имя сайта из имени сервиса
	if (!empty($crop_site)) {
		// вырезаем имя площадки из имени
		if (count($model->sites)==1) {
			$site=array_values($model->sites)[0];
			$cropped=false;
			//ищем полное имя
			$pos_full=mb_strpos($name,$site->name);
			$pos_short=mb_strpos($name,$site->short);
			
			if ($pos_full===0) {
				$name=mb_substr($name,mb_strlen($site->name));
				//$name='{'.$name;
				$cropped=true;
			} elseif ($pos_full===(mb_strlen($name)-mb_strlen($site->name))) {
				$name=mb_substr($name,0,mb_strlen($name)-mb_strlen($site->name));
				//$name=$name.'}';
				$cropped=true;
			} elseif ($pos_short===0) {
				$name=mb_substr($name,mb_strlen($site->short));
				//$name='<'.$name;
				$cropped=true;
			} elseif ($pos_short===(mb_strlen($name)-mb_strlen($site->short))) {
				$name=mb_substr($name,0,mb_strlen($name)-mb_strlen($site->short));
				//$name=$name.'>';
				$cropped=true;
			}
			
			if ($cropped) {
				$name=trim($name," \t:-/\\");
			}
		}
	}
	
	echo \app\components\ItemObjectWidget::widget([
		'model'=>$model,
		'link'=>\app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'static'=>$static_view,
			'noDelete'=>$noDelete,
			'name'=>$icon.$name,
		])
	]);
}