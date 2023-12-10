<?php

use app\components\WikiPageWidget;
use kartik\markdown\Markdown;
use yii\bootstrap5\Tabs;
use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $ips app\models\NetIps[] */
Url::remember();

$this->title = $model->sname;
$this->params['breadcrumbs'][] = ['label' => app\models\Networks::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

$showEmpty= Yii::$app->request->get('showEmpty',false);

//var_dump($index);

//var_dump($model->ipsByAddr);
?>
<div class="networks-view">
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('card',['model'=>$model]) ?>
		</div>
		<div class="col-md-6">
			<?= $this->render('calc',['model'=>$model]) ?>
		</div>
	</div>
<?php

$cookieTabName='networks-view-tab-'.$model->id;
$cookieTab=$_COOKIE[$cookieTabName]??'ip-table';


$tabs=[];

$tabId='ip-table';
$tabs[]=[
	'label'=>'Адреса',
	'active'=>$cookieTab==$tabId,
	'headerOptions'=>['onClick'=>'document.cookie = "'.$cookieTabName.'='.$tabId.'"'],
	'content'=>$this->render('ip-table',['model'=>$model]),
];

$tabId='net-description';
$tabs[]=[
	'label'=>'Описание сети',
	'active'=>$cookieTab==$tabId,
	'headerOptions'=>['onClick'=>'document.cookie = "'.$cookieTabName.'='.$tabId.'"'],
	'content'=>Markdown::convert($model->notepad),
];

if (is_object($model->segment)) {
	$tabId='segment-description';
	$tabs[]=[
		'label'=>'Описание сегмента',
		'active'=>$cookieTab==$tabId,
		'headerOptions'=>['onClick'=>'document.cookie = "'.$cookieTabName.'='.$tabId.'"'],
		'content'=>Markdown::convert($model->segment->history),
	];
}

$tabNumber=0;
$wikiLinks= WikiPageWidget::getLinks($model->links);
foreach ($wikiLinks as $name=>$url) {
	$tabId='wiki'.$tabNumber;
	$tabs[]=[
		'label'=>($name==$url)?'Wiki':$name,
		'active'=>$cookieTab==$tabId,
		'content'=> WikiPageWidget::Widget(['list'=>$model->links,'item'=>$name]),
		'headerOptions'=>['onClick'=>'document.cookie = "'.$cookieTabName.'='.$tabId.'"'],
	];
	$tabNumber++;
}

echo Tabs::widget([
	'items'=>$tabs,
	'options'=>[
		'class'=>'nav-pills',
	]
]);

?>
</div>
