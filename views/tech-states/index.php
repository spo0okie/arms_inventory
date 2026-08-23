<?php

use yii\helpers\Html;
use kartik\grid\GridView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\TechStates::$title;
//крошки собираются автоматически в layout (views/layouts/main.php)

$renderer=$this;

//колонка-флажок: только отображение, без id (иначе дубли id на нескольких колонках)
$checkboxColumn=function($attribute) {
	return [
		'attribute'=>$attribute,
		'format'=>'raw',
		'value'=>function($data) use ($attribute) {
			return '<input class="form-check-input" type="checkbox" disabled '.($data->$attribute?'checked':'').'>';
		}
	];
};
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
    				return ModelWidget::widget(['model'=>$data]).
						\app\components\LinkObjectWidget::widget([
							'model'=>$data,
							'name'=>false,
							'modal'=>true,
							'hideUndeletable'=>false,
						]);
				}
			],
			$checkboxColumn('archived'),
			$checkboxColumn('operating'),
            'descr:ntext',
        ],
    ]); ?>
</div>


