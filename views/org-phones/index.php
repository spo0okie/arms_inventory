<?php

use yii\helpers\Html;
use kartik\grid\GridView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\OrgPhones::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;

?>
<div class="org-phones-index">
	
	<?= \app\components\DynaGridWidget::widget([
		'id' => 'org-phones-index',
		'dataProvider' => $dataProvider,
		'model' => new \app\models\OrgPhones(),
		'columns' => [
			'fullNum'=>[
				'value' => function ($data) use ($renderer) {
					return $renderer->render('item', ['model' => $data, 'href'=>true,'static_view'=>false]);
				}
			],
			'comment',
			'places_id' => [
				'value' => function ($data) use ($renderer) {
					return ModelWidget::widget(['model'=>$data->place,'options'=>['static_view'=>true]]);
				}
			],
			'services_id' => [
				'value' => function ($data) use ($renderer) {
					return ModelWidget::widget(['model'=>$data->service,'options'=>['href'=>true]]);
				}
			],
			'account',
			'cost',
			'charge',
		],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'toolButton'=> '<span class="p-2">'.\app\components\ShowArchivedWidget::widget().'<span>',
		'header'=>$this->title,
	]); ?>
</div>


