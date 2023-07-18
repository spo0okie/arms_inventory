<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

//\yii\helpers\Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\OrgStruct::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="org-struct-view">
	<div class="mb-3">
		<?= $this->render('card',['model'=>$model]) ?>
	</div>
	
	<?= DynaGridWidget::widget([
		'id' => 'org-struct-users-index',
		'columns' => require __DIR__.'\..\users\columns.php',
		'header' => 'Пользователи подразделения',
		'defaultOrder' => ['employee_id','shortName','Doljnost','Login','Email','Phone','arms','Mobile'],
		//'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		//'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Users','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
