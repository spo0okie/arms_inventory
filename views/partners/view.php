 <?php

 use app\components\DynaGridWidget;
 use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Partners */
 /* @var $dataProvider yii\data\ActiveDataProvider */

if (!isset($contracts)) $contracts=$model->docs;

$this->title = $model->uname;
$this->params['breadcrumbs'][] = ['label' => 'Контрагенты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="partners-view">
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('card',['model'=>$model]) ?>
		</div>
		<div class="col-md-6">
			<?php if (count($model->services)) { ?>
				<h4><?= \app\models\Services::$titles ?></h4>
				<p>
					<?php
					$items=[];
					foreach ($model->services as $service)
						$items[]=$this->render('/services/item',['model'=>$service]);
					echo implode('<br />',$items);
					?>
				</p>
			<?php } ?>

			<?php if (count($model->docs)) { ?>
				<h4><?= \app\models\Contracts::$titles ?></h4>
				<p>
					<?php
					$items=[];
					foreach ($contracts as $contract)
						$items[]=$this->render('/contracts/item',['model'=>$contract,'partner'=>false,'show_payment'=>true]);
					echo implode('<br />',$items);
					?>
				</p>
			<?php } ?>
		</div>
	</div>
	<div class="my-3">
		<?= Html::a(\app\models\OrgStruct::$title,['org-struct/index','org_id'=>$model->id]) ?>
		//
		<?= Html::a('Новый '.\app\models\Users::$title,['users/create','Users[org_id]'=>$model->id],[
			'class'=>'open-in-modal-form',
			'data-reload-page-on-submit'=>1
		]) ?>
	</div>
	<?= $dataProvider->totalCount?DynaGridWidget::widget([
		'id' => 'org-struct-users-index',
		'columns' => require __DIR__.'/../users/columns.php',
		'header' => 'Пользователи организации',
		'defaultOrder' => ['employee_id','shortName','Doljnost','orgStruct_name','Login','Email','Phone','arms','Mobile'],
		//'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		//'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Users','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]):'' ?>
</div>
