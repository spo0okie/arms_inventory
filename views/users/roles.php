<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.01.2020
 * Time: 11:58
 */

use yii\helpers\Html;
use lo\widgets\modal\ModalAjax;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

?>
<h4>Доступ к инвентаризации</h4>
<?php
	yii\widgets\Pjax::begin(['id' => 'user-roles','timeout' => 5000,]);
	$roles=Yii::$app->authManager->getRolesByUser($model->id);
	if (count($roles)) {
		foreach ($roles as $role) {
			echo $role->name.'<br />';
		}
	} else {
		echo "Не задан<br />";
	}
	echo \yii\helpers\Html::a(
	'Изменить',
	['/rbac/assignment/assignment','id'=>$model->id],
	[
		'class' => 'open-in-modal-form',
		'data-reload-page-on-submit' => 1
	]);
	yii\widgets\Pjax::end();

	
?>
