<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\components\ShowArchivedWidget;
use app\helpers\StringHelper;
use yii\bootstrap5\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\ArmsModel */
/* @var $searchModel app\models\ArmsModel */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */

Url::remember();

$modelClass=get_class($model);
$searchClass=StringHelper::className($modelClass).'Search';
$classId=StringHelper::class2Id($modelClass);

if (!isset($additionalCreateButton)) $additionalCreateButton='';
if (!isset($additionalToolButton)) $additionalToolButton='';

$this->title = $modelClass::$titles??$modelClass::$title??'Список';
$this->params['breadcrumbs'][] = $this->title;


//если у нас есть разница от переключения флажка "архивные"
if (isset($switchArchivedCount)) {
	//считаем дельту
	$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
	if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;
} else {
	$switchArchivedDelta=null;
}

$filtered=false;
//если у нас есть фильтры, которые мы применили
if (isset(Yii::$app->request->get()[$searchClass])) {
	foreach (Yii::$app->request->get()[$searchClass] as $field) if ($field) $filtered=true;
}

?>
<div class="<?= $classId ?>-index">
	<?= DynaGridWidget::widget([
		'id' => $classId.'-index',
		'header' => Html::encode($this->title),
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/'.$classId.'/columns.php',
		'defaultOrder' => $modelClass::$defaultColumns??[],
		'createButton' => Html::a(
			$modelClass::$addButtonText??'Добавить',
			['create'],
			[
				'class' => 'btn btn-success',
				'title' => $modelClass::$addButtonHint??null,
			]
		).$additionalCreateButton,
		'hintButton' => HintIconWidget::widget([
			'model'=>$modelClass,
			'cssClass'=>'btn'
		]),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel??null,
		'toolButton'=> $additionalToolButton
			.(($model->hasAttribute('archived')&&is_object($searchModel??null))?	//если у нас есть атрибут "архивный" и фильтр
			'<span class="p-2">'. ShowArchivedWidget::widget([			//то отображаем виджет
				'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
				'labelBadge'=>$switchArchivedDelta,
				'state'=>$searchModel->archived
			]).'<span>':''),
	]) ?>
</div>
