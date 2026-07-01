<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $anchor app\models\Acls эталонный ACL группы */
/* @var $ace app\models\Aces редактируемый/добавляемый ACE */
/* @var $members app\models\Acls[] все ACL группы */
/* @var $title string заголовок операции */

$this->title=$title;

$this->render('breadcrumbs',['model'=>$anchor,'show_item'=>false]);
$this->params['breadcrumbs'][]=$title;
YiiAsset::register($this);
?>
<div class="acls-group-ace">

	<h1><?= Html::encode($this->title) ?></h1>

	<div class="alert alert-warning">
		<span class="fas fa-exclamation-triangle"></span>
		Операция применится к <b>всем <?= count($members) ?></b> ACL группы:
		<ul class="mb-0">
			<?php foreach ($members as $member) { ?>
				<li><?= Html::encode($member->sname) ?></li>
			<?php } ?>
		</ul>
	</div>

	<div class="acls-form">
		<?php $form = ArmsForm::begin([
			'model'=>$ace,
			'id' => 'group-ace-form',
		]); ?>

			<?= $this->render('@app/views/aces/_form_layout',['model'=>$ace,'form'=>$form]) ?>

			<?= Html::submitButton('Сохранить для всей группы', ['class' => 'btn btn-success']) ?>

		<?php ArmsForm::end(); ?>
	</div>

</div>
