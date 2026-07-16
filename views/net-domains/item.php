<?php

use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		//легаси CSS-класс по имени — fallback пока домену не назначен маркер
		//(?? false: у History-модели связи marker нет — падаем в легаси-класс)
		'item_class'=>'net-domains-item text-monospace '.(($model->marker??false)?'':'net-domain-'.$model->name),
		'ttipUrl'=>Url::to(['net-domains/ttip','id'=>$model->id]),
		'updateUrl'=>['net-domains/update','id'=>$model->id,'return'=>'previous'],
	]);
}
