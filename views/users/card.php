<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

if (!isset($static_view)) $static_view=false;
$deleteable=!(bool)(count($model->arms) || count($model->armsHead) || count($model->armsIt) || count($model->armsResponsible) || count($model->techs) || count($model->techsIt));
?>
    <h1>
		
		<?= Html::a($model->Ename,['/users/view', 'id' => $model->id]) ?>
        <?= Html::a('<span class="fas fa-pencil-alt"></span>', ['update', 'id' => $model->id]) ?>
        <?php if($deleteable) echo Html::a('<span class="fas fa-trash"></span>', ['users/delete', 'id' => $model->id], [
	        'data' => [
		        'confirm' => 'Удалить этого пользователя?',
		        'method' => 'post',
	        ],
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
	    <?= $model->org->name ?>
        //
        <?= $model->Doljnost ?>
        <br />
        <?= (is_object($model->orgStruct))?$model->orgStruct->name:'- отдел не найден -' ?>
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
        Внутренний: <?= $model->Phone ?><br />
		Сотовый: <?= $model->Mobile ?><br />
		<?= strlen($model->private_phone)?"Личный: {$model->private_phone} <br />":'' ?>
        Городской: <?= $model->work_phone ?><br />
    </p>

    <br />


    <?php if (!$static_view) {
		if (
				count($model->arms) ||
				count($model->techs)
		) { ?>
			<h4>Привязанное оборудование</h4>
			<?php if (count($model->arms)) { ?>
				Пользователь АРМ:
				<?php foreach ($model->arms as $arm) echo $this->render('/arms/item',['model'=>$arm]) ?>
				<br />
			<?php } ?>
			<?php if (count($model->techs)) { ?>
				Пользователь техники:
				<?php foreach ($model->techs as $tech) echo $this->render('/techs/item',['model'=>$tech]) ?>
				<br />
			<?php } ?>
		<?php }
		} ?>

	<?= $this->render('/comps/lics_list',['model'=>$model]); ?>

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
