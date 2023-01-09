<?php

use yii\helpers\Html;
use kartik\grid\GridView;

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
			return $renderer->render('/places/item', ['model' => $data->top, 'full' => 1]);
		}
	]
];

$departments=[];


//перебираем подразделения
foreach ($dataProvider->models as $place) {
	//раскладываем АРМы по помещениям/подразделениям
	foreach ($place->top->armsRecursive as $arm) {
		if (!$arm->departments_id) continue;
		$dep = $arm->departments_id;
		if (!isset($departments[$dep]))	$departments[$dep] = ['name'=>$arm->department->name];
		if (!isset($departments[$dep][$place->id]))	$departments[$dep][$place->id] = ['arms'=>[],'techs'=>[]];
		$departments[$dep][$place->id]['arms'][] = $arm;
	}
	
	foreach ($place->top->techsRecursive as $tech) {
		if (!is_object($tech->effectiveDepartment)) continue;
		$dep = $tech->effectiveDepartment->id;
		if (!isset($departments[$dep]))	$departments[$dep] = ['name'=>$tech->effectiveDepartment->name];
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
$isFa=true;
// $isFa below determines if export['fontAwesome'] property is set to true.
$defaultExportConfig = [
	GridView::HTML => [
		'label' => Yii::t('kvgrid', 'HTML'),
		'icon' => $isFa ? 'file-text' : 'floppy-saved',
		'iconOptions' => ['class' => 'text-info'],
		'showHeader' => true,
		'showPageSummary' => true,
		'showFooter' => true,
		'showCaption' => true,
		'filename' => Yii::t('kvgrid', 'grid-export'),
		'alertMsg' => Yii::t('kvgrid', 'The HTML export file will be generated for download.'),
		'options' => ['title' => Yii::t('kvgrid', 'Hyper Text Markup Language')],
		'mime' => 'text/html',
		'config' => [
			'cssFile' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css'
		]
	],
	GridView::EXCEL => [
		'label' => Yii::t('kvgrid', 'Excel'),
		'icon' => $isFa ? 'file-excel-o' : 'floppy-remove',
		'iconOptions' => ['class' => 'text-success'],
		'showHeader' => true,
		'showPageSummary' => true,
		'showFooter' => true,
		'showCaption' => true,
		'filename' => Yii::t('kvgrid', 'grid-export'),
		'alertMsg' => Yii::t('kvgrid', 'The EXCEL export file will be generated for download.'),
		'options' => ['title' => Yii::t('kvgrid', 'Microsoft Excel 95+')],
		'mime' => 'application/vnd.ms-excel',
		'config' => [
			'worksheet' => Yii::t('kvgrid', 'ExportWorksheet'),
			'cssFile' => ''
		]
	],
	GridView::PDF => [
		'label' => Yii::t('kvgrid', 'PDF'),
		'icon' => $isFa ? 'file-pdf-o' : 'floppy-disk',
		'iconOptions' => ['class' => 'text-danger'],
		'showHeader' => true,
		'showPageSummary' => true,
		'showFooter' => true,
		'showCaption' => true,
		'filename' => Yii::t('kvgrid', 'grid-export'),
		'alertMsg' => Yii::t('kvgrid', 'The PDF export file will be generated for download.'),
		'options' => ['title' => Yii::t('kvgrid', 'Portable Document Format')],
		'mime' => 'application/pdf',
		'config' => [
			'mode' => 'c',
			'format' => 'A4-L',
			'destination' => 'D',
			'marginTop' => 20,
			'marginBottom' => 20,
			'cssInline' => '.kv-wrap{padding:20px;}' .
				'.kv-align-center{text-align:center;}' .
				'.kv-align-left{text-align:left;}' .
				'.kv-align-right{text-align:right;}' .
				'.kv-align-top{vertical-align:top!important;}' .
				'.kv-align-bottom{vertical-align:bottom!important;}' .
				'.kv-align-middle{vertical-align:middle!important;}' .
				'.kv-page-summary{border-top:4px double #ddd;font-weight: bold;}' .
				'.kv-table-footer{border-top:4px double #ddd;font-weight: bold;}' .
				'.kv-table-caption{font-size:1.5em;padding:8px;border:1px solid #ddd;border-bottom:none;}',
			'methods' => [
				'SetHeader' => [
					//['odd' => $pdfHeader, 'even' => $pdfHeader]
				],
				'SetFooter' => [
					//['odd' => $pdfFooter, 'even' => $pdfFooter]
				],
			],
			'options' => [
				//'title' => $title,
				'subject' => Yii::t('kvgrid', 'PDF export generated by kartik-v/yii2-grid extension'),
				'keywords' => Yii::t('kvgrid', 'krajee, grid, export, yii2-grid, pdf')
			],
			'contentBefore'=>'',
			'contentAfter'=>''
		]
	],
];
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
		//'exportConfig' => $defaultExportConfig,
		'panel' => [
			'type' => GridView::TYPE_PRIMARY,
		]
	]); ?>

</div>
