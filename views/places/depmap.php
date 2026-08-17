<?php

use yii\helpers\Html;
use kartik\grid\GridView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

\yii\helpers\Url::remember();
$this->title = \app\models\Departments::$title;
$this->params['breadcrumbs'][] = $this->title;




$renderer=$this;

//формируем список столбцов для рендера
//первый стольец - площадка
$render_columns=[
	'place'=>[
		'header'=>'Площадка',
		'format'=>'raw',
		'value' => function ($data) use ($renderer) {
			return ModelWidget::widget(['model'=>$data->top,'options'=>['full' => 1]]);
		}
	]
];

$departments=[];


//перебираем подразделения
foreach ($dataProvider->models as $place) {
	//раскладываем АРМы по помещениям/подразделениям
	/*foreach ($place->top->armsRecursive as $arm) {
		if (!$arm->departments_id) continue;
		$dep = $arm->departments_id;
		if (!isset($departments[$dep]))	$departments[$dep] = ['name'=>$arm->department->name];
		if (!isset($departments[$dep][$place->id]))	$departments[$dep][$place->id] = ['arms'=>[],'techs'=>[]];
		$departments[$dep][$place->id]['arms'][] = $arm;
	}*/
	
	foreach ($place->top->techsRecursive as $tech) {
		if (!is_object($tech->department)) continue;
		$dep = $tech->department->id;
		if (!isset($departments[$dep]))	$departments[$dep] = ['name'=>$tech->department->name];
		if (!isset($departments[$dep][$place->id]))	$departments[$dep][$place->id] = ['arms'=>[],'techs'=>[]];
		$departments[$dep][$place->id]['techs'][] = $tech;
	}
}

foreach ($departments as $id=>$dep) {
	$render_columns[] = [
		//'attribute' => 'num',
		'format' => 'raw',
		'header'=>$dep['name'],
		'value' => function ($data) use ($renderer,$dep,$id) {
			return $renderer->render('/places/depitem', ['models' => isset($dep[$data->id])?$dep[$data->id]:['arms'=>[],'techs'=>[]]]);
		}
	];
}
?>

<div class="places-index">
	
	<?= GridView::widget([
		'dataProvider' => $dataProvider,
		'persistResize' => false,
		'hover'=>true,
		'layout' => '{items}',
		'columns' => $render_columns,
		'toolbar' => [
			'{export}'
		],
		'export' => [
			'fontAwesome' => true
		],
		//дефолтный kartik-конфиг HTML-экспорта тянет bootstrap и FontAwesome
		//с CDN; выгруженный файл открывается вне приложения, поэтому ссылки
		//на стили абсолютные — на локальные копии на ЭТОМ сервере (custom.css
		//и есть наш bootstrap): инсталляция не зависит от интернета.
		//Перечисление типов здесь же ограничивает меню экспорта — состав
		//сохранён дефолтный, кроме настроенного HTML
		'exportConfig' => [
			GridView::HTML => ['config' => ['cssFile' => [
				\yii\helpers\Url::base(true).'/css/custom.css',
				\yii\helpers\Url::base(true).'/fontawesome/css/all.min.css',
			]]],
			GridView::CSV => [],
			GridView::TEXT => [],
			GridView::EXCEL => [],
			GridView::JSON => [],
			GridView::PDF => [],
		],
		'panel' => [
			'type' => GridView::TYPE_PRIMARY,
		]
	]); ?>

</div>


