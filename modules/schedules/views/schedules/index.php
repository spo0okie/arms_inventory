<?php

use app\components\DynaGridWidget;
use app\components\UrlParamSwitcherWidget;
use app\modules\schedules\models\Schedules;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\schedules\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Schedules::$titles;
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="schedules-index">

    <?= DynaGridWidget::widget([
		'id'=>'schedules-index',
        'header' => $this->title,
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'createButton' => Html::a('Новое', ['create'], ['class' => 'btn btn-success'])
			//служебный режим: индивидуальные (безымянные) расписания обычно смотрят
			//со страницы владельца, тут - чтобы увидеть их все одним списком (issue #139)
			.' // '.UrlParamSwitcherWidget::widget([
				'param'=>'showIndividual',
				'label'=>'Индивидуальные',
				'hintOn'=>'Показать индивидуальные расписания (без названия, принадлежащие одному объекту)',
				'hintOff'=>'Скрыть индивидуальные расписания',
			]),
        'columns' => require __DIR__.'/columns.php',
    ]); ?>


</div>
