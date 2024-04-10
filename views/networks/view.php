<?php

use app\components\DynaGridWidget;
use app\components\TabsWidget;
use kartik\markdown\Markdown;
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


$notepadRender='';
$notepadLines=0;
$notepad=strlen(trim($model->notepad));
if ($notepad) {
	$notepadRender=Markdown::convert($model->notepad);
	$notepadLines=count(explode("\n",trim($model->notepad)));
}
$notepadCompact=$notepadLines<=Yii::$app->params['networkInlineDescriptionLimit'];

$segmentRender='';
$segmentLines=0;
if (is_object($model->segment) && trim($model->segment->history)) {
	$segmentRender=Markdown::convert($model->segment->history);
	$segmentLines=count(explode("\n",trim($model->segment->history)));
}
$segmentCompact=$segmentLines<=Yii::$app->params['networkInlineDescriptionLimit'];

$showSegment=(
	Yii::$app->params['networkDescribeSegment']===true
	||
	Yii::$app->params['networkDescribeSegment']==='auto' && !$notepad
);


//формируем заголовок странички
$this->params['headerContent'] =
	'<div class="d-flex flex-row">'
		.'<div class="pe-5 flex-fill">'
			.$this->render('card',['model'=>$model])
			.(($notepad&&$notepadCompact)?$notepadRender:'');
	
			if (!$notepad&&$showSegment&&$segmentCompact) {
				$this->params['headerContent'] .= $segmentRender;
				$showSegment=false;
			}

			$this->params['headerContent']
			.= '</div>'
		.'<div class="pe-0 text-nowrap">'
			.$this->render('calc',['model'=>$model])
		.'</div>'
	.'</div>';

$cookieTabName='networks-view-tab-'.$model->id;


$tabs=[];

$tabs[]=[
	'id'=>'ip-table',
	'label'=>'Адреса',
	'content'=>$this->render('ip-table',['model'=>$model]),
];

DynaGridWidget::handleSave('service-connections-list');

$tabs[]=[
	'id'=>'incomingConnections',
	'label'=>'Вх. соединения <i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#service-connections-list-grid-modal" class="small fas fa-wrench fa-fw"></i>',
	'content'=><<<HTML
		<div id="incomingConnectionsList">
		
			<div class="spinner-border" role="status">
				<span class="visually-hidden">Loading...</span>
			</div>
		</div>
		<script>
			$(document).ready(function() {
				$.get("/web/networks/incoming-connections-list?id={$model->id}", function(data) {
				    jQuery("#incomingConnectionsList").hide().html(data);
				    setTimeout(function (){jQuery("#incomingConnectionsList").fadeToggle();ExpandableCardInitAll();},500)
				})
			})
		</script>
HTML,
];

if ($notepad&&!$notepadCompact) {
	$tabs[]=[
		'id'=>'net-description',
		'label'=>'Описание сети',
		'content'=>$notepadRender,
	];
}


if ($showSegment) {
	$tabs[]=[
		'id'=>'segment-description',
		'label'=>'Описание сегмента',
		'content'=>Markdown::convert($model->segment->history),
	];
}

$nonWikiTabs=count($tabs);							//запоминаем сколько было вкладок
TabsWidget::addWikiLinks($tabs,$model->links);	//добавляем из вики
$addedWikiTabs=count($tabs)-$nonWikiTabs;			//считаем сколько добавили

if (
	Yii::$app->params['networkDescribeSegment']===true							//если нужно добавлять из сегмента
	||
	Yii::$app->params['networkDescribeSegment']==='auto' && !$addedWikiTabs		//или это авто и мы ничего не добавили из сети
) {
	if (is_object($model->segment)) TabsWidget::addWikiLinks($tabs,$model->segment->links);
}

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'networks-view-tab-'.$model->id,
];
/*echo Tabs::widget([
	'items'=>$tabs,
	'options'=>[
		'class'=>'nav-pills',
	]
]);*/

