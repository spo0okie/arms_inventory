<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\ProvTel */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\ProvTel::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$org_phones=$model->orgPhones;
$org_inet=$model->orgInet;
$deleteable=!(count($org_inet)||count($org_phones));
?>
<div class="prov-tel-view">

    <h1>
        <?= Html::encode($model->name) ?>
	    <?= Html::a('<span class="glyphicon glyphicon-pencil"/>', ['update', 'id' => $model->id]) ?>
	    <?php if($deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['delete', 'id' => $model->id], [
		    'data' => [
			    'confirm' => 'Удалить этого оператора связи? Это действие необратимо!',
			    'method' => 'post',
		    ],
	    ]) ?>    </h1>

	<?= !$deleteable?'<p><span class="glyphicon glyphicon-warning-sign"></span> Невозможно удалить этого оператора, т.к. есть привязанные к нему объекты </p>':'' ?>

    <p>
        Добавить :
        <?= Html::a('Подключение интернет',['/org-inet/create','prov-tel'=>$model->id])?>
        /
	    <?= Html::a('Городской тел. номер',['/org-phones/create','prov-tel'=>$model->id])?>
    </p>

	<?php if (count($org_phones)) { ?>
        <h4>Городские телефоны</h4>
        <p>
			<?php foreach ($org_phones as $phone) {
				echo $this->render('/org-phones/item',['model'=>$phone]).'<br/>';
			} ?>
        </p>
	<?php } ?>

	<?php if (count($org_inet)) { ?>
        <h4>Подключения интернет</h4>
        <p>
			<?php foreach ($org_inet as $inet) {
				echo $this->render('/org-inet/item',['model'=>$inet]).'<br/>';
			} ?>
        </p>
	<?php } ?>

    <?= $this->render('card',['model'=>$model]) ?>


</div>
