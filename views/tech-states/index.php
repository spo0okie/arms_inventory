<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\TechStates::$title;
$this->params['breadcrumbs'][] = $this->title;

$renderer=$this;
?>
<div class="tech-states-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Новое состояние', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'code',
            [
				'attribute'=>'name',
				'format'=>'raw',
				'value'=>function($data) use ($renderer) {
    				return $renderer->render('/tech-states/item',['model'=>$data]).
						\app\components\LinkObjectWidget::widget([
							'model'=>$data,
							'name'=>false,
							'modal'=>true,
							'hideUndeletable'=>false,
						]);
				}
			],
			[
				'attribute'=>'archived',
				'format'=>'raw',
				'value'=>function($data) use ($renderer) {
					return '<input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" disabled '.($data->archived?'checked':'').'>';
				}
			],
            'descr:ntext',
        ],
    ]); ?>
</div>
