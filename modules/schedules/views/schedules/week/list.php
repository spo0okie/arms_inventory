<?php

use yii\data\ArrayDataProvider;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $model app\modules\schedules\models\Schedules */

if (!isset($static_view)) $static_view=$model->isNewRecord;
$renderer=$this;
?>
<h2>Расписание на неделю</h2>
<div class="mb-3">

<?php
//основное расписание показываем всегда
echo $this->render('item',['model'=>$model]);

//перекрытия (изменения на период: отпуска и т.п.) могут быть многочисленными,
//поэтому выводим их с пагинацией, как исключения на 1 день (issue #192),
//а не одним длинным expandable-блоком
$periods=$model->overrides;
if (count($periods)) {
	echo ListView::widget([
		'dataProvider'=>new ArrayDataProvider([
			'allModels'=>$periods,
			'pagination'=>[
				'pageSize'=>5,
				//свой параметр, чтобы пагинация перекрытий не конфликтовала
				//с пагинацией исключений (та использует стандартный "page")
				'pageParam'=>'week-page',
			],
		]),
		//itemView 'item' резолвится относительно этого файла => week/item.php
		'itemView'=>'item',
		'itemOptions'=>['tag'=>false],
		'layout'=>"{items}\n{pager}",
	]);
}
?>
</div>
