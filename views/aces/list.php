<?php

/* @var $this yii\web\View */
/* @var $models app\models\Aces[] */
/* @var $hintModel \app\models\base\ArmsModel модель-владелец списка: даёт тултип-«?» заголовка (атрибут acls) */
/* @var $static_view bool */

use app\components\ExpandableCardWidget;
use app\components\ItemObjectWidget;
use app\components\ListObjectsWidget;
use app\components\ModelFieldWidget;
use app\components\ShowArchivedWidget;
use app\components\widgets\page\ModelWidget;
use app\helpers\HtmlHelper;
use yii\helpers\Html;

if (!isset($static_view)) $static_view=false;
//не полагаемся на ShowArchivedWidget::$defaultValue: тогглер (CornerWidget) на
//страницах карточек рендерится ПОЗЖЕ этого блока, и дефолт еще не переключен
//в «скрывать» — архивные строки вместо скрытия оставались бы зачеркнутыми
if (!isset($show_archived)) $show_archived=(bool)Yii::$app->request->get('showArchived',false);

if (is_array($models) && count($models)) {

	//группируем по расписанию ACL: имя временного доступа (обычно длинное) выводится
	//один раз заголовком группы, а не префиксом каждой строки — доступ к нескольким
	//ресурсам (группа ACL) не повторяет его на каждый ресурс; записи без расписания
	//собираются под «Постоянный доступ» (зеркально стороне ресурса, acls/list-item.php)
	$groups=[];
	foreach ($models as $ace) {
		$sid=is_object($ace->acl)?(int)$ace->acl->schedules_id:0;
		if (!isset($groups[$sid])) $groups[$sid]=[
			'schedule'=>$sid?$ace->acl->schedule:null,
			'aces'=>[],
		];
		$groups[$sid]['aces'][]=$ace;
	}

	$content='';
	$allArchived=true;
	foreach ($groups as $group) {
		//группа скрывается/зачеркивается целиком (вместе с заголовком),
		//когда все её записи архивные
		$groupArchived=true;
		foreach ($group['aces'] as $ace) $groupArchived=$groupArchived&&$ace->archived;
		$allArchived=$allArchived&&$groupArchived;

		$header=is_object($group['schedule'])?
			ModelWidget::widget(['model'=>$group['schedule'],'options'=>['static_view'=>true]]):
			'Постоянный доступ';

		//сводки «куда и какой доступ»: ресурс - типы доступа: пояснение
		//(см. aces/summary.php); архивные строки скрываются поэлементно
		$content.=ItemObjectWidget::widget([
			'link'=>$header.':<div class="px-2">'
				.ListObjectsWidget::widget([
					'models'=>$group['aces'],
					'itemViewPath'=>'/aces/summary',
					'item_options'=>['show'=>'resource','show_schedule'=>false,'static_view'=>$static_view],
					'show_archived'=>$show_archived,
					'title'=>false,
					'card'=>false,
					'lineBr'=>false,
					'glue'=>'<br />',
				])
				.'</div>',
			'archived'=>$groupArchived,
			'show_archived'=>$show_archived,
		]);
	}

	[$title]=isset($hintModel)&&is_object($hintModel)?
		ModelFieldWidget::fieldTitle($hintModel,'acls',null,'Имеет доступ к:'):
		['Имеет доступ к:'];

	echo ExpandableCardWidget::widget([
		'cardClass'=>'mb-3 line-nobr',
		'content'=>Html::tag('h4',$title,[
				'class'=>$allArchived?ShowArchivedWidget::$itemClass:'',
				'style'=>HtmlHelper::ArchivedDisplay($allArchived,$show_archived),
			]).$content,
	]);
}
