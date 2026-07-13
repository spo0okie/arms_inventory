<?php

use app\components\DynaGridWidget;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use app\models\HistoryModel;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\web\View;

/** @var View $this */
/** @var ActiveDataProvider $dataProvider */
/** @var HistoryModel $instance */
/** @var \app\models\base\ArmsModel $master */
/** @var string $class */

$this->params['layout-container'] = 'container-fluid';
/** @var ArmsModel $masterClass */
$masterClass=get_class($master);
/** @noinspection PhpUndefinedFieldInspection */
$classTitle=$class::$title;
$classView=Inflector::camel2id(StringHelper::className($masterClass));

$this->params['breadcrumbs'][] = ['label' => $masterClass::$titles, 'url' => [$classView.'/index']];
$this->params['breadcrumbs'][] = ['label' => $master->name, 'url' => [$classView.'/view','id'=>$master->id]];
$this->params['breadcrumbs'][] = ['label' => 'История изменений'];

//режим отображения: карточки изменений (issue #194) либо прежний табличный
$viewMode=Yii::$app->request->get('viewMode','cards');

if ($viewMode=='table') {

	$columnsMode=Yii::$app->request->get('columnsMode','non-empty');

	$modesHints=[
		'changed'=>'Показаны только с изменениями',
		'non-empty'=>'Показаны только колонки со значениями',
		'all'=>'Показаны все колонки',
	];

	$modesLinks=[
		'changed'=>Html::a('Показать только измененные',['journal','columnsMode'=>'changed']+Yii::$app->request->get()),
		'non-empty'=>Html::a('Показать только не пустые',['journal','columnsMode'=>'non-empty']+Yii::$app->request->get()),
		'all'=>Html::a('Показать все',['journal','columnsMode'=>'all']+Yii::$app->request->get()),
	];

	unset($modesLinks[$columnsMode]);

	$modesLinks['cards']=Html::a('Карточки изменений',['journal','viewMode'=>'cards']+Yii::$app->request->get());

	echo DynaGridWidget::widget([
		'id'=> Inflector::camel2id(StringHelper::className($class)).'-journal',
		'header' => $classTitle,
		'dataProvider' => $dataProvider,
		'columns' => include 'columns.php',
		'model' => $instance,
		'createButton' => $modesHints[$columnsMode].': '.implode(' // ',$modesLinks),
	]);

	return;
}

/** @var HistoryModel[] $models записи журнала страницы (id DESC - новые сверху) */
$models=array_values($dataProvider->models);

//предыдущая запись каждой карточки - следующая в выборке;
//последней на странице достанется ленивый запрос в getPreviousRecord()
foreach ($models as $i=>$model) {
	if (isset($models[$i+1])) $model->setPreviousRecord($models[$i+1]);
}

?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h3 class="mb-0"><?= Html::encode($classTitle) ?>: история изменений</h3>
	<span><?= Html::a('Табличный вид',['journal','viewMode'=>'table']+Yii::$app->request->get()) ?></span>
</div>
<?php

if (!count($models)) {
	echo '<div class="alert alert-secondary">Записей в журнале нет</div>';
}

foreach ($models as $model) {
	echo $this->render('card',['model'=>$model]);
}

echo \yii\bootstrap5\LinkPager::widget(['pagination'=>$dataProvider->pagination]);
