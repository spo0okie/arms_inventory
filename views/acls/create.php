<?php

use app\models\Acls;
use yii\helpers\Html;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */
/* @var $ace app\models\Aces */

$this->title = "Новый ". Acls::$title;

$this->render('breadcrumbs',['model'=>$model,'show_item'=>false]);
$this->params['breadcrumbs'][] = 'Новый ACL';
YiiAsset::register($this);


?>
<div class="acls-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form2', [
        'model' => $model,
		'ace' => $ace,
    ]) ?>

</div>
