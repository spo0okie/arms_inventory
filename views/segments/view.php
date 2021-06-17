<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */
/* @var $searchModel app\models\NetworksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


//\yii\helpers\Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Segments::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="segments-view">
	<?= $this->render('card',['model'=>$model]) ?>
	<h2>Сети входящие в этот сегмент</h2>
	<?= $this->render('/networks/table',[
		'dataProvider'=>$dataProvider,
		'searchModel'=>$searchModel,
		'columns'=>['name','comment','vlan','domain','usage']
	]) ?>
</div>
