<?php

use app\components\Forms\ArmsForm;
use app\models\Users;
use app\modules\schedules\models\ScheduledAccess;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var ScheduledAccess $model новая (несохранённая) копия временного доступа */
/** @var app\modules\schedules\models\Schedules $source временный доступ-образец */
/** @var app\models\Aces $ace носитель поля подмены субъектов (Aces[users_ids]) */

$this->title='Копия временного доступа';
$this->params['breadcrumbs'][]=['label'=>ScheduledAccess::$titles,'url'=>['index']];
$this->params['breadcrumbs'][]=['label'=>$source->name,'url'=>['view','id'=>$source->id]];
$this->params['breadcrumbs'][]=$this->title;

$aclCount=count($source->acls);
$aceCount=0;
foreach ($source->acls as $acl) $aceCount+=count($acl->aces);
?>
<div class="scheduled-access-copy">

	<h1><?= Html::encode($this->title) ?></h1>

	<p>
		Будут скопированы все списки доступа образца
		«<?= Html::a(Html::encode($source->name),['view','id'=>$source->id]) ?>»:
		<?= $aclCount ?> ACL, <?= $aceCount ?> записей доступа.
		Периоды действия образца не копируются — задайте новые периоды
		на странице созданного доступа.
	</p>

	<?php $form=ArmsForm::begin(['model'=>$model]); ?>
	<div class="for-alert"></div>

	<div class="row">
		<div class="col-md-6">
			<div class="card bg-light mb-3">
				<div class="card-header">Новый временный доступ</div>
				<div class="card-body">
					<?= $form->field($model,'name') ?>
					<?= $form->field($model,'history')->text(['rows'=>5,'height'=>100]) ?>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card bg-light mb-3">
				<div class="card-header">Субъекты доступа</div>
				<div class="card-body">
					<?= $form->field($ace,'users_ids')->select2(['data'=>Users::fetchWorking()])
						->label('Заменить субъектов на')
						->hint('Если выбрать сотрудников, во всех скопированных записях доступа '
							.'субъекты-пользователи будут заменены на них (персональные IP образца '
							.'при этом не переносятся).<br>'
							.'Если оставить пустым — субъекты копируются как в образце.') ?>
				</div>
			</div>
		</div>
	</div>

	<?= Html::submitButton('Создать копию',['class'=>'btn btn-success']) ?>
	<?php ArmsForm::end(); ?>

</div>
