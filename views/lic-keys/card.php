<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LicKeys */
/* @var $linksData \yii\data\ArrayDataProvider */

if (!isset($static_view)) $static_view=false;
$renderer=$this;
$arms=$model->arms;
$deleteable=!count($arms);

?>

<h1>
    Ключ <?= $this->render('/lic-keys/item',['model'=>$model,'static_view'=>$static_view]) ?>

    <?php if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['delete', 'id' => $model->id], [
        'data' => [
            'confirm' => 'Удалить этот лицензионный ключ? Убедись, что его есть где потом искать!',
            'method' => 'post',
        ],
    ]); else { ?>
		<span class="small">
			<span class="fas fa-lock"	title="Невозможно в данный момент удалить этот лицензионный ключ, т.к. он привязан к АРМам."></span>
		</span>
	<?php } ?>
</h1>
		<h4>Группа лицензий:</h4>
		<?= $this->render('/lic-groups/item',['model'=>$model->licItem->licGroup,'static_view'=>$static_view]) ?>
		<br />

		<h4>Закупка:</h4>
		<?= $this->render('/lic-items/item',['model'=>$model->licItem,'static_view'=>$static_view,'name'=>$model->licItem->descr]) ?>
		<br />

		<h4>Ключ:</h4>
		<?= $static_view?$model->keyShort:$model->key_text ?>
		<br />

		<h4>Комментарий:</h4>
		<?= Yii::$app->formatter->asNtext($model->comment) ?>
