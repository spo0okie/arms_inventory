<?php

use app\components\DynaGridWidget;
use app\components\ShowArchivedWidget;
use yii\helpers\Html;


\yii\helpers\Url::remember();

/* @var $this yii\web\View */
/* @var $searchModel app\models\TechsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount int|null */
$renderer = $this;
$this->title = \app\models\Techs::$title;
//крошки собираются автоматически в layout (views/layouts/main.php)
$this->params['layout-container'] = 'container-fluid';

//признак того, что в форму поиска вбиты данные (для цвета бейджа с дельтой)
$filtered = false;
foreach ((array)Yii::$app->request->get('TechsSearch', []) as $field) if ($field) $filtered = true;

//насколько больше записей будет при включении архивных
$switchArchivedDelta = ($switchArchivedCount ?? $dataProvider->totalCount) - $dataProvider->totalCount;
if ($switchArchivedDelta > 0) $switchArchivedDelta = '+' . $switchArchivedDelta;

?>
<div class="techs-index">

	<?= DynaGridWidget::widget([
		'id' => 'techs-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => ['attach','num','model','sn','mac','ip','state','user','place','inv_num','comment'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'toolButton'=>
			'<span class="p-2">'. ShowArchivedWidget::widget([			//то отображаем виджет
				'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
				'labelBadge'=>$switchArchivedDelta,
				//'state'=>$searchModel->archived
			]).'<span>',

	]) ?>

</div>
