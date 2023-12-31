<?php

/* @var $this yii\web\View */
/* @var $model app\models\Users */

use app\components\ListObjectsWidget;
use app\components\ShowArchivedWidget;
use app\models\Users;
use yii\helpers\Url;

$this->title = $model->Ename;
$this->params['breadcrumbs'][] = ['label' => Users::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

Url::remember();

$static_view=false;

$services=$model->services;
$comps=count($services)?$model->compsTotal:$model->comps;
if (!isset($show_archived)) $show_archived=Yii::$app->request->get('showArchived',false);

?>
<div class="users-view">
		<span class="float-end">
			<?= ShowArchivedWidget::widget(['reload'=>false]) ?>
		</span>
	<div class="row">
		<div class="col-md-4">
			<?= $this->render('card',['model'=>$model,'static_view'=>false]) ?>
		</div>
		<div class="col-md-4">
			<h2>Рабочее место</h2>
			<?php foreach ($model->techs as $arm) if ($arm->isComputer)
				echo $this->render('/techs/compact',['model'=>$arm,'no_users'=>true,'no_specs'=>true,'show_archived'=>$show_archived])?>
		</div>
		<div class="col-md-4">
			<br/>
			<?php
			
			echo ListObjectsWidget::widget([
				'models' => $model->services,
				'title' => 'Ответственный за сервисы:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);

			echo ListObjectsWidget::widget([
				'models' => $model->infrastructureServices,
				'title' => 'Ответственный за инфраструктуру:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);
			
			echo ListObjectsWidget::widget([
				'models' => $comps,
				'title' => 'Ответственный за ОС:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);
			
			echo ListObjectsWidget::widget([
				'models' => $model->techsHead,
				'title' => 'АРМ/оборудование числящиеся за подчиненными:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);
			
			echo ListObjectsWidget::widget([
				'models' => $model->techsIt,
				'title' => 'Обслуживаемое сотрудником оборудование:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);
			
			echo ListObjectsWidget::widget([
				'models' => $model->techsResponsible,
				'title' => 'АРМ/оборудование в ответственности:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);
			
			$materials=[];
			foreach ($model->materials as $material) if ($material->rest>0) $materials[]=$material;
			
			echo ListObjectsWidget::widget([
				'models' => $materials,
				'title' => 'Ответственный за материалы:',
				'item_options' => [
					'static_view' => $static_view,
					'responsible'=>false,
					'from'=>true,
					'rest'=>true,
				],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> true,
			]);
			
			echo ListObjectsWidget::widget([
				'models' => $model->contracts,
				'title' => 'Документы:',
				'item_options' => ['static_view' => $static_view, 'user'=>false ],
				'card_options' => ['cardClass' => 'mb-3'],
			]);
			
			?>
			
			<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>

			<?= Users::isAdmin()?$this->render('roles',['model'=>$model,'static_view'=>false]):'' ?>

		</div>
	</div>
</div>
