<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Manufacturers */

if (!isset($static_view)) $static_view=false;

if (!isset($static_limit)) $static_limit=$static_view?10:9999;

$soft=$model->soft;
$dict=$model->dict;
$techs=$model->techModels;

?>

<h1>
	<?= Html::encode($model->name) ?>
	<?php if (!$static_view) { ?>
		<?= Html::a('<span class="fas fa-pencil-alt" />',['update','id'=>$model->id]) ?>
		<?php if (!count($soft)&&!count($dict)&&!count($techs))
			echo Html::a('<span class="fas fa-trash" />',
				[
					'delete',
					'id'=>$model->id,
				],[
					'data'=>[
						'method'=>'post',
						'confirm'=>'Удалить этого производителя?',
					]
				]);
		else { ?>
			<span class="small">
				<span class="fas fa-lock"	title="Невозможно в данный момент удалить этого производителя, т.к. он привязан к софту, моделям оборудования или словарь написаний не пуст"></span>
			</span>
		<?php }
	} ?>
</h1>

<?= Html::encode($model->full_name) ?>

<p>
	<?= Html::encode($model->comment) ?>
</p>

<br />

<h4>Варианты написания производителя:</h4>
<p>
	<?php for ($i=0; $i<min(count($dict),$static_limit); $i++ ) { ?>
		<?= $this->render('/manufacturers-dict/item',['model'=>$dict[$i],'static_view'=>$static_view,'static_view'=>$static_view]) ?><br />
	<?php }
	if ($static_view && (count($dict)>$static_limit)) echo 'Еще '.($static_limit-count($dict)).' ...<br />';
	?>
	<?= Html::a('Добавить вариант написания', ['manufacturers-dict/create','manufacturers_id'=>$model->id]) ?>
</p>

<br />

<h4>Программные продукты:</h4>

<p>
	<?php for ($i=0; $i<min(count($soft),$static_limit); $i++ ) { ?>
		<?= $this->render('/soft/item',['model'=>$soft[$i],'static_view'=>$static_view]).'<br />' ?>
	<?php }
	if ($static_view && (count($soft)>$static_limit)) echo 'Еще '.(count($soft)-$static_limit).' ...<br />';
	?>
</p>

<br />

<h4>Модели оборудования:</h4>

<p>
	<?php for ($i=0; $i<min(count($techs),$static_limit); $i++ ) { ?>
		<?= $this->render('/tech-models/item',['model'=>$techs[$i],'static_view'=>$static_view]).'<br />' ?>
	<?php }
	if ($static_view && (count($techs)>$static_limit)) echo 'Еще '.(count($techs)-$static_limit).' ...<br />';
	?>
</p>
