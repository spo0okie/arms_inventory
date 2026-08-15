<?php

use app\components\ListObjectsWidget;

/* @var $this yii\web\View */
/* @var $models app\models\Acls[] */

//не полагаемся на ShowArchivedWidget::$defaultValue: тогглер (CornerWidget) на
//страницах карточек рендерится ПОЗЖЕ этого блока (см. aces/list.php)
if (!isset($show_archived)) $show_archived=(bool)Yii::$app->request->get('showArchived',false);

echo ListObjectsWidget::widget([
	'models' => $models,
	'itemViewPath'=>'/acls/list-item',
	'title' => 'Предоставлен доступ',
	'item_options' => ['static_view' => $static_view??false],
	'show_archived' => $show_archived,
	//lineBr дал бы карточке класс line-break, который CSS-ом (card.css) разворачивает
	//КАЖДЫЙ span.object-item внутри в блок — сводки ACE рассыпались бы в столбик;
	//строки и так разделены заголовками ACL и <br /> между ACE
	'lineBr' => false,
	'card_options' => ['cardClass' => 'mb-3'],
]);

