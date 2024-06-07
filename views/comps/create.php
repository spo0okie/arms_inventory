<?php

use app\models\Comps;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Comps */

if (!isset($modalParent)) $modalParent=null;
$this->title = 'Добавление операционной системы';
$this->params['breadcrumbs'][] = ['label' => Comps::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="comps-create">

    <h1><?= Html::encode($this->title) ?></h1>
	<?php if (!$model->sandbox_id) {?>
		<div class="alert alert-danger" role="alert">
		Внимание! Создание описания операционной системы вручную - исключительная ситуация.
		Правильный путь появления новых ОС - создание их скриптами инвентаризации
		</div>
	<?php } ?>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
	]) ?>

</div>
