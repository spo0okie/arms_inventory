<?php
/**
 * Превью скана
 */

use app\models\Scans;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Scans */

if (is_object($model) && $model->fileExists)
	echo Html::a(
		Html::img((strtolower($model->format) == 'pdf') ? (Scans::pdfThumb()) : $model->idxThumb),
		$model->fullFname,
		['class'=>'scans-ttip']
	);
