<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

if (!isset($static_view)) $static_view=false;
$deleteable=!count($model->licGroups);
?>
<h1>
	<?= $this->render('item',compact('model')) ?>

	<?php if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить эту схему лицензирования? Это действие необратимо!',
			'method' => 'post',
		],
	]); else { ?>
		<span class="small">
			<span class="glyphicon glyphicon-lock"	title="Невозможно в данный момент удалить эту схему лицензирования, т.к. присутствуют группы лицензий с этой схемой лицензирования."></span>
		</span>
	<?php } ?>
</h1>


<p>
	<?= Yii::$app->formatter->asNtext($model->comment) ?>
</p>

<br />

<p>
	<h4>Ссылки:</h4>
	<?= \app\components\UrlListWidget::Widget(['list'=>$model->links]) ?>
</p>

<br />

<p>
	<h4>Группы лицензий:</h4>
	<?php foreach ($model->licGroups as $licGroup) { ?>
		<?= $this->render('/lic-groups/item',['model'=>$licGroup]) ?> <br />
	<?php }	?>
</p>


