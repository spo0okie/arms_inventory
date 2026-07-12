<?php

use app\components\ModelFieldWidget;
use app\helpers\ArrayHelper;
use kartik\markdown\Markdown;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Users */

if (!isset($static_view)) $static_view=false;

?>
    <h1>
		<?= app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'static'=>$static_view,
			'confirmMessage' => 'Действительно удалить этого пользователя?',
			'undeletableMessage'=>'Нельзя удалить этого пользователя,'.
				'<br> т.к. к нему привязаны другие объекты',
		]) ?>
    </h1>
	<?= $model->nosync?'<span class="fas fa-lock" title="Синхронизация с внешней БД сотрудников отключена"></span>':'' ?>
	<?= ModelFieldWidget::renderFieldTitle($model,'Bday',null,'span','Дата рождения') ?>: <?= ModelFieldWidget::renderFieldValue($model,'Bday') ?> <br/>
    <?= \app\components\ModelFieldWidget::renderCompositeTitle($model,['employee_id','Persg'],'Табельный №','span') ?>
	<?= $model->employee_id ?> (<?= \app\models\Users::$WTypes[$model->Persg][1] ?? $model->Persg ?>)
    -
	<?php
        if ($model->Uvolen) {
            if (strlen($model->resign_date??''))
                echo 'Уволен с '.$model->resign_date;
            else
                echo 'Уволен';
	    } elseif (strlen($model->employ_date??''))
			echo 'Работает с '.$model->employ_date;
	    else
			echo 'Работает';
	?>
    <p>
	    <?= \app\components\ChainWidget::widget(['segments'=>[
			['model'=>$model,'field'=>'org'],
			['model'=>$model,'field'=>'orgStruct','chain'=>true],
			['model'=>$model,'field'=>'Doljnost'],
		]]) ?>
    </p>
	<?php if (is_object($model->manager)) { ?>
		<p>
			<?= ModelFieldWidget::renderFieldTitle($model,'manager_id',null,'span') ?>:
			<?= $model->manager->renderItem($this,['static_view'=>$static_view]) ?>
		</p>
	<?php } ?>

	<div class="flex-row d-flex flex-wrap pb-3">
		<span class="pe-4">
			<span class="h5"><?= ModelFieldWidget::renderFieldTitle($model,'Login',null,'span','Логин в AD') ?>: </span><?= ModelFieldWidget::renderFieldValue($model,'Login') ?>
		</span>
		<span>
			<span class="h5"><?= ModelFieldWidget::renderFieldTitle($model,'Email',null,'span','E-Mail') ?>: </span><?= Yii::$app->formatter->asEmail($model->Email) ?>
		</span>
	</div>

    <?= ModelFieldWidget::renderCompositeTitle($model,['Phone','Mobile','private_phone','work_phone'],'Телефоны') ?>
    <p class="pb-3">
        Внутренний: <?= $this->render('internal-phone',compact('model')) ?><br />
		Сотовый: <?= $this->render('mobile-phone',['phone'=>$model->Mobile,'static_view'=>$static_view]) ?><br />
		<?= strlen($model->private_phone??'')?("Личный: ".$this->render('mobile-phone',['phone'=>$model->private_phone,'static_view'=>$static_view])." <br />"):'' ?>
        <?= strlen($model->work_phone??'')?('Городской: '.ModelFieldWidget::renderFieldValue($model,'work_phone').'<br />'):'' ?>
    </p>

	<?php echo ModelFieldWidget::widget([
		'model' => $model, 'field' => 'compsFromTechs',
		'label' => 'Привязанные ОС:',
		'item_options' => ['static_view' => true, 'class'=>'text-nowrap','rc'=>true],
		'card_options' => ['cardClass' => 'mb-3'],
		'lineBr'=> $static_view,
	]) ?>

	<?php echo ModelFieldWidget::widget([
		'model' => $model, 'field' => 'netIps',
		'label' => 'Закрепленные IP:',
		'item_options' => ['static_view' => $static_view, 'class'=>'text-nowrap'],
		'card_options' => ['cardClass' => 'mb-3'],
		'lineBr'=> $static_view,
	]) ?>

    <?php if (!$static_view) echo ModelFieldWidget::widget([
		'model' => $model, 'field' => 'techs',
		'label' => 'АРМ/Оборудование числящиеся за сотрудником:',
		'item_options' => ['static_view' => $static_view, ],
		'card_options' => ['cardClass' => 'mb-3'],
		'lineBr'=> false,
	]) ?>

	<?= $this->render('/comps/lics_list',['model'=>$model]); ?>

	<?= $this->render('/aces/list',['models'=>$model->aces,'hintModel'=>$model]); ?>

	<?php
	$lastLogins=$model->lastThreeLogins;
	if (is_array($lastLogins) && count($lastLogins)) {
		echo ModelFieldWidget::renderFieldTitle($model,'lastThreeLogins',null,'h4','Входы в комп');
		foreach ($lastLogins as $logon)
			echo $this->render('/login-journal/item-comp',['model'=>$logon]).' <br />';
	}
	?>

<?php if (strlen($model->notepad??'')) { ?>
	<h3>Записная книжка:</h3>
	<p>
		<?= \app\components\ModelFieldWidget::renderFieldValue($model,'notepad') ?>
	</p>
	<br />
<?php }


