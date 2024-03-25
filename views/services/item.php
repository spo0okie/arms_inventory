<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
/* @var $crop_site boolean Обрезать имя площадки из имени */
/* @var $crop_parent boolean Обрезать имя родителя из имени */

if (!isset($static_view)) $static_view=false;
if (!isset($show_archived)) $show_archived=Yii::$app->request->get('showArchived',true);
if (!isset($noDelete)) $noDelete=false;


if (is_object($model)) {
	if (!isset($display)) {
		$display=($model->archived&&!$show_archived)?'style="display:none"':'';
	}
	
	if (!isset($archClass)) {
		$archClass=$model->archived?'text-muted text-decoration-line-through archived-item':'';
	}
	
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
	
	if (!empty($crop_parent)) {
		$dividers=['-',':','::','/','\\','>','->'];
		if (is_object($model->parentService)){
			//разбиваем имя на слова
			$tokens= StringHelper::explode($name," ",true,true);
			foreach ($model->parentService->getAliases() as $alias) {
				//разбиваем альяс на слова
				$aliasTokens=StringHelper::explode($alias," ",true,true);
				//если слова альяса это первые слова имени
				if (array_intersect_assoc($aliasTokens,$tokens) == $aliasTokens) {
					//собственно нашли совпадение
					
					//убираем альяс из начала имени (откусываем в качестве имени правый набор слов после альяса)
					$tokens=array_slice($tokens,count($aliasTokens));
					
					//если теперь в начале имени стоит разделитель, его бы убрать
					if (array_search($tokens[0],$dividers)!==false)
						array_shift($tokens);
					
					$name=implode(' ',$tokens);
					break;
				}
			}
		}
		
	}
	
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'static'=>$static_view,
			'noDelete'=>$noDelete,
			'name'=>$icon.$name,
		])
	]);
}