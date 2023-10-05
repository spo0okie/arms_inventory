<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

//\yii\helpers\Url::remember();

$this->title = $model->name;

$this->render('breadcrumbs',['partner'=>$model->partner,'model'=>$model]);

\yii\web\YiiAsset::register($this);

?>
<div class="org-struct-view">
	<h1>
		<?= \app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'deleteUrl'=>['/org-struct/delete','id'=>$model->id,'org_id'=>$model->org_id],
			'updateUrl'=>['/org-struct/update','id'=>$model->id,'org_id'=>$model->org_id],
			'static'=>false,
			//'confirm' => 'Удалить этот сервис? Это действие необратимо!',
			'hideUndeletable'=>false
		]) ?>	</h1>

	
	<?= DynaGridWidget::widget([
		'id' => 'org-struct-users-index',
		'columns' => require __DIR__.'/../users/columns.php',
		'header' => 'Пользователи подразделения',
		'defaultOrder' => ['employee_id','shortName','Doljnost','Login','Email','Phone','arms','Mobile'],
		//'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		//'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Users','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
