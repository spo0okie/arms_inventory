<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ArmsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
\yii\helpers\Url::remember();

$this->title = 'АРМы';
$this->params['breadcrumbs'][] = $this->title;
$renderer = $this;
?>
<div class="arms-index">

    <h1>
        <?= Html::encode($this->title) ?>
        <?= \app\components\HintIconWidget::widget(['object_id'=>'help_block','cssClass'=>'pull-right']) ?>
    </h1>
    Автоматизированные Рабочие Места. Оборудование и программное обеспечение, сгрупированные в один объект.



    <p>
        <?= Html::a('Создать АРМ', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

	<?= $this->render('/arms/table', [
		'searchModel' => $searchModel,
		'dataProvider' => $dataProvider,
		'columns'   => ['attach','num','model','comp_id','comp_ip','sn','mac','state','user_id','user_position','departments_id','places_id'],
	]) ?>

</div>
