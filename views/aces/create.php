<?php

use app\models\Aces;
use app\models\Acls;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$this->title = "Новый ". Aces::$title;

//если есть ACL, то отплясываем от него
if (is_object($model->acl)) {
	if($model->acl->schedules_id) {
		$this->params['breadcrumbs'][] = ['label' => Acls::$scheduleTitles, 'url' => ['scheduled-access/index']];
	 	$this->params['breadcrumbs'][] = ['label' => $model->acl->schedule->name, 'url' => ['schedules/view','id'=>$model->acl->schedules_id]];
 	} else {
	 	$this->params['breadcrumbs'][] = ['label' => Acls::$titles, 'url' => ['acls/index']];
 	}
	$this->params['breadcrumbs'][] = ['label'=>$model->acl->sname,'url' => ['acls/view','id'=>$model->acls_id]];
} else { //если нет, то отплясываем от ACE
	$this->params['breadcrumbs'][] = ['label' => Aces::$titles, 'url' => ['aces/index']];
}
?>
<div class="aces-create">

    <h1><?= Html::encode($this->title) ?></h1>

	<?php //форма только самой ACE: ACL-контекст гарантирован контроллером
		//(создание ACE без ACL уходит в acls/create) ?>
    <?= $this->render('_form', ['model' => $model]) ?>

</div>
