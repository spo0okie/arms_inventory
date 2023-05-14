<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 03.10.2019
 * Time: 23:09
 */

/** @var yii\web\View $this */
/** @var \app\models\Techs $model */

if (!isset($static_view)) $static_view=false;
echo $this->render('/attaches/model-list',[
	'model'=>$model,
	'link'=>'techs_id',
	'static_view'=>$static_view
]);
