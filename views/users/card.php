<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
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
	    } else {
	        if (strlen($model->employ_date))
		        echo 'Работает с '.$model->employ_date;
	        else
		        echo 'Работает';
	    };
	?>
    <p>
	    <?= $this->render('/partners/item',['model'=>$model->org,'static_view'=>true]) ?>
		<?= $model->Doljnost?(' // '.$model->Doljnost):'' ?>
		<br>
		<?= $this->render('/org-struct/item',['model'=>$model->orgStruct,'chain'=>true]) ?>
		
    </p>

    <br />

    <h4>Логин в AD</h4>
    <p>
        <?= $model->Login ?>
    </p>

    <br />

    <h4>E-Mail</h4>
    <p>
        <?= Yii::$app->formatter->asEmail($model->Email) ?>
    </p>

    <br />

    <h4>Телефоны</h4>
    <p>
        Внутренний: <?= $this->render('internal-phone',compact('model')) ?><br />
		Сотовый: <?= $model->Mobile ?><br />
		<?= strlen($model->private_phone)?"Личный: {$model->private_phone} <br />":'' ?>
        Городской: <?= $model->work_phone ?><br />
    </p>

    <br />


	<?php echo \app\components\ListObjectWidget::widget([
		'models' => $model->netIps,
		'title' => 'Закрепленные IP:',
		'item_options' => ['static_view' => $static_view, 'class'=>'text-nowrap'],
		'card_options' => ['cardClass' => 'mb-3'],
		'lineBr'=> false,
	]) ?>

    <?php if (!$static_view) echo \app\components\ListObjectWidget::widget([
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
