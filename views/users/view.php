<?php

/* @var $this yii\web\View */
/* @var $model app\models\Users */

use app\components\ListObjectsWidget;
use app\components\ModelFieldWidget;
use app\components\ShowArchivedWidget;
use app\models\Users;
use app\components\widgets\page\ModelWidget;
use yii\helpers\Url;

$this->title = $model->Ename;
//крошки собираются автоматически в layout (views/layouts/main.php)

Url::remember();

$static_view=false;

$services=$model->services;
//если сотрудник отвечает за сервисы, показываем и ОС этих сервисов (compsTotal)
$compsField=count($services)?'compsTotal':'comps';
if (!isset($show_archived)) $show_archived=Yii::$app->request->get('showArchived',false);

?>
<div class="users-view">
		<span class="float-end">
			<?= ShowArchivedWidget::widget(['reload'=>false]) ?>
		</span>
	<div class="row">
		<div class="col-md-5 ps-0">
			<?= ModelWidget::widget(['model'=>$model, 'view'=>'card', 'static_view'=>false]) ?>
		</div>
		<div class="col-md-4 ps-0">
			<?php
			//АРМ сотрудника (компьютеры из закреплённого оборудования); нет АРМ - нет и заголовка
			$workplaceArms=[];
			foreach ($model->techs as $arm) if ($arm->isComputer) $workplaceArms[]=$arm;
			if (count($workplaceArms)) {
				echo '<h2>Рабочее место</h2>';
				foreach ($workplaceArms as $arm)
					echo ModelWidget::widget(['model'=>$arm, 'view'=>'compact', 'no_users'=>true,'no_specs'=>true,'show_archived'=>$show_archived]);
			}
			?>
		</div>
		<div class="col-md-3 p-0">
			<br/>
			<?php

			echo ModelFieldWidget::widget([
				'model' => $model, 'field' => 'services',
				'label' => 'Ответственный за сервисы:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);

			echo ModelFieldWidget::widget([
				'model' => $model, 'field' => 'infrastructureServices',
				'label' => 'Ответственный за инфраструктуру:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);

			echo ModelFieldWidget::widget([
				'model' => $model, 'field' => $compsField,
				'label' => 'Ответственный за ОС:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);

			echo ModelFieldWidget::widget([
				'model' => $model, 'field' => 'adminComps',
				'label' => 'Выданы полномочия администратора:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);

			echo ModelFieldWidget::widget([
				'model' => $model, 'field' => 'techsHead',
				'label' => 'АРМ/оборудование числящиеся за подчиненными:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);

			echo ModelFieldWidget::widget([
				'model' => $model, 'field' => 'techsIt',
				'label' => 'Обслуживаемое сотрудником оборудование:',
				'item_options' => ['static_view' => $static_view, ],
				'card_options' => ['cardClass' => 'mb-3'],
				'lineBr'=> false,
			]);

			echo ModelFieldWidget::widget([
				'model' => $model, 'field' => 'techsResponsible',
				'label' => 'АРМ/оборудование в ответственности:',
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

			echo ModelFieldWidget::widget([
				'model' => $model, 'field' => 'contracts',
				'label' => 'Документы:',
				'item_options' => ['static_view' => $static_view, 'user'=>false ],
				'card_options' => ['cardClass' => 'mb-3'],
			]);

			?>

			<?= $this->render('/attaches/model-list',['model'=>$model, 'static_view'=>$static_view]) ?>

			<?= Users::isAdmin()?ModelWidget::widget(['model'=>$model, 'view'=>'roles', 'options'=>['static_view'=>false]]):'' ?>

		</div>
	</div>
</div>
