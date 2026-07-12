<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel app\models\LicKeysSearch */

$this->title = \app\models\LicKeys::$title;
//крошки собираются автоматически в layout (views/layouts/main.php)

$renderer=$this;
?>
<div class="lic-keys-index">
	
	<?= DynaGridWidget::widget([
		'id' => 'lic-keys',
		'header' => Html::encode($this->title),
		'columns' => include 'columns.php',
		//кнопки «Добавить» здесь осознанно нет: ключ создаётся из карточки закупки лицензий
		'createButton' => '<div class="alert alert-info py-1 px-2 mb-0 d-inline-block">'
			.'<span class="fas fa-info-circle"></span> '
			.'Ключи добавляются с карточки '
			.Html::a('закупки лицензий',['/lic-items/index'])
			.' (кнопка «Добавить ключ»)'
			.'</div>',
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
