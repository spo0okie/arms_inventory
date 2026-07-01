<?php

use app\models\Acls;
use yii\helpers\Html;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */
/* @var $ace app\models\Aces */
/* @var $schedule app\modules\schedules\models\Schedules|null расписание при создании нового временного доступа */

if (!isset($schedule)) $schedule=null;

$this->title = $schedule? "Новый ".mb_strtolower(Acls::$scheduleTitle) : "Новый ".Acls::$title;

$this->render('breadcrumbs',['model'=>$model,'show_item'=>false]);
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);


?>
<div class="acls-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form2', [
        'model' => $model,
		'ace' => $ace,
		'schedule' => $schedule,
    ]) ?>

</div>
