<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\NetDomains::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="net-domains-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
