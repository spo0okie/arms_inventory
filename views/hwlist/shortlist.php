<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 07.03.2018
 * Time: 23:02
 * @var \app\models\HwList $model отображаемый элемент
 */
use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
//echo '<pre>'; var_dump($item); echo '</pre>'; die(0);

if (!isset($vm)) $vm=false;


$tokens=$vm?[
	$model->getCPUCount(),
	$model->getRAMShort(),
	$model->getHDDShort(),
]:[
	$model->getCPUShort(),
	$model->getRAMShort(),
	$model->getHDDShort(),
];

if (isset($arm_id)) {
	$options=[
		'qtip_ajxhrf'=>\yii\helpers\Url::to(['/arms/ttip-hw','id'=>$arm_id])
	];
} else $options=[];

foreach ($tokens as $i=>$val) if (!strlen($val)) unset ($tokens[$i]);

echo Html::tag('span',implode(' / ',$tokens),$options);
