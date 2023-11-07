<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\components\ShowArchivedWidget;
use app\models\Users;
use yii\helpers\Html;
use yii\helpers\Url;


/* @var $this yii\web\View */
/* @var $searchModel app\models\UsersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */

Url::remember();

if (isset($switchArchivedCount)) {
	$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
	if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;
} else {
	$switchArchivedDelta=null;
}

$filtered=false;
if (isset(Yii::$app->request->get()['UsersSearch'])) {
	foreach (Yii::$app->request->get()['UsersSearch'] as $field) if ($field) $filtered=true;
}

$this->title = Users::$titles;
//$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="users-index">
	
	<?= DynaGridWidget::widget([
		'id' => 'users-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => ['employee_id','shortName','Doljnost','orgStruct_name','Login','Email','Phone','arms','Mobile'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\Users','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'toolButton'=> '<span class="p-2">'. ShowArchivedWidget::widget([
				'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
				'labelBadge'=>$switchArchivedDelta
			]).'<span>',
	]) ?>
	
	
</div>
