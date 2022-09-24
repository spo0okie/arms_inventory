<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */
/* @var $deleteable bool */

if (!isset($static_view)) $static_view=false;
?>

<h3>
	<?= $this->render('/lic-groups/item',['model'=>$model->licGroup,'static_view'=>$static_view]) ?>

	<?= $static_view?'<br /> <h4> Закупка: </h4>':'/' ?>

	<?= $this->render('/lic-items/item',['model'=>$model,'static_view'=>$static_view,'name'=>$model->descr]) ?>

	<?php if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить эту закупку лицензий? Это действие необратимо!',
			'method' => 'post',
		],
	]); else { ?>
		<span class="small">
			<span class="fas fa-lock"	title="Невозможно в данный момент удалить эту закупку лицензий, т.к. присутствуют привязанные объекты: документы или АРМы."></span>
		</span>
	<?php } ?>
</h3>
<hr/>
<?= $this->render('/lic-types/descr',['model'=>$model->licGroup->licType]) ?>
