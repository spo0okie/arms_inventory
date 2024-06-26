<?php

use app\models\Aces;
use app\models\Acls;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $acl app\models\Acls */

$this->title = "Новый ". Aces::$title;

//если есть ACL то отплясываем от него
if (is_object($model->acl)) {
	if($model->acl->schedules_id) {
		$this->params['breadcrumbs'][] = ['label' => Acls::$scheduleTitles, 'url' => ['schedules/index-acl']];
	 	$this->params['breadcrumbs'][] = ['label' => $model->acl->schedule->name, 'url' => ['schedules/view','id'=>$model->acl->schedules_id]];
 	} else {
	 	$this->params['breadcrumbs'][] = ['label' => Acls::$titles, 'url' => ['acls/index']];
 	}
	$this->params['breadcrumbs'][] = ['label'=>$model->acl->sname,'url' => ['acls/view','id'=>$model->acls_id]];
} else { //если нет, то отпрлясываем от ACE
	$this->params['breadcrumbs'][] = ['label' => Aces::$titles, 'url' => ['aces/index']];
}
?>
<div class="aces-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= is_object($model->acl)?
		$this->render('_form', [
        'model' => $model,
    	]):
		$this->render('/acls/_form2', [
			'model' => $acl,'ace'=>$model
		])
	?>

</div>
