<?php

use app\components\ListObjectsWidget;
use app\helpers\ArrayHelper;
use kartik\markdown\Markdown;

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
	Дата рождения: <?= $model->Bday ?> <br/>
    Табельный №
	<?= $model->employee_id ?> (<?= $model->Persg ?>)
    -
	<?php
        if ($model->Uvolen) {
            if (strlen($model->resign_date))
                echo 'Уволен с '.$model->resign_date;
            else
                echo 'Уволен';
	    } elseif (strlen($model->employ_date))
			echo 'Работает с '.$model->employ_date;
	    else
			echo 'Работает';
	?>
    <p>
	    <?= ArrayHelper::implode(' / ',[
			$this->render('/partners/item',['model'=>$model->org,'static_view'=>true]),
			$this->render('/org-struct/item',['model'=>$model->orgStruct,'chain'=>true]),
			$model->Doljnost
		]) ?>
		
    </p>

	<div class="flex-row d-flex flex-wrap pb-3">
		<span class="pe-4">
			<span class="h5">Логин в AD: </span><?= $model->Login ?>
		</span>
		<span>
			<span class="h5">E-Mail: </span><?= Yii::$app->formatter->asEmail($model->Email) ?>
		</span>
	</div>

    <h4>Телефоны</h4>
    <p class="pb-3">
        Внутренний: <?= $this->render('internal-phone',compact('model')) ?><br />
		Сотовый: <?= $this->render('mobile-phone',['phone'=>$model->Mobile,'static_view'=>$static_view]) ?><br />
		<?= strlen($model->private_phone)?("Личный: ".$this->render('mobile-phone',['phone'=>$model->private_phone,'static_view'=>$static_view])." <br />"):'' ?>
        Городской: <?= $model->work_phone ?><br />
    </p>

	<?php echo ListObjectsWidget::widget([
		'models' => $model->compsFromTechs,
		'title' => 'Привязанные ОС:',
		'item_options' => ['static_view' => true, 'class'=>'text-nowrap','rc'=>true],
		'card_options' => ['cardClass' => 'mb-3'],
		'lineBr'=> $static_view,
	]) ?>

	<?php echo ListObjectsWidget::widget([
		'models' => $model->netIps,
		'title' => 'Закрепленные IP:',
		'item_options' => ['static_view' => $static_view, 'class'=>'text-nowrap'],
		'card_options' => ['cardClass' => 'mb-3'],
		'lineBr'=> $static_view,
	]) ?>

    <?php if (!$static_view) echo ListObjectsWidget::widget([
		'models' => $model->techs,
		'title' => 'АРМ/Оборудование числящиеся за сотрудником:',
		'item_options' => ['static_view' => $static_view, ],
		'card_options' => ['cardClass' => 'mb-3'],
		'lineBr'=> false,
	]) ?>

	<?= $this->render('/comps/lics_list',['model'=>$model]); ?>

	<?= $this->render('/aces/list',['models'=>$model->aces]); ?>
	
	<h4>Входы в комп</h4>
    <?php if (is_array($model->lastThreeLogins)) foreach ($model->lastThreeLogins as $logon) { ?>
        <?= $this->render('/login-journal/item-comp',['model'=>$logon]); ?> <br />
    <?php } ?>

<?php if (strlen($model->notepad)) { ?>
	<h3>Записная книжка:</h3>
	<p>
		<?= Markdown::convert($model->notepad) ?>
	</p>
	<br />
<?php }
