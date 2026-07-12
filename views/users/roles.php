<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.01.2020
 * Time: 11:58
 */

use app\components\AttributeTooltip;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$roles= Yii::$app->authManager->getRolesByUser($model->id);
if ($model->Login || count($roles)) {
	$hintIcon=AttributeTooltip::icon([
		'title'=>'Доступ к инвентаризации',
		'body'=>'Роли доступа к самому приложению инвентаризации (RBAC): определяют, '
			.'что пользователь может видеть и редактировать здесь.<br>'
			.'Это не доменные/сетевые доступы — те перечислены в блоке «Имеет доступ к».<hr/>'
			.'«Сбросить пароль» задаёт локальный пароль приложения: при входе он проверяется '
			.'в первую очередь, и только если локальный пароль не задан — выполняется проверка через LDAP.',
	]);
	?>
	<h4>Доступ к инвентаризации<?= $hintIcon?' '.$hintIcon:'' ?></h4>
	<?php
	if ($model->Login && (
					Yii::$app->params['useRBAC']
					||
					count($roles)
					||
					(!Yii::$app->params['useRBAC'] && !Yii::$app->params['authorizedView'])
			)) {
		yii\widgets\Pjax::begin(['id' => 'user-roles', 'timeout' => 5000,]);

		if (count($roles)) {
			foreach ($roles as $role) {echo $role->name . '<br />';}
		} else {
			echo "Не задан<br />";
		}

		echo Html::a(
				'Изменить',
				['/rbac/assignment/assignment', 'id' => $model->id],
				[
						'class' => 'open-in-modal-form',
						'data-reload-page-on-submit' => 1
				]);
		yii\widgets\Pjax::end();
	}

	if ($model->Login && Yii::$app->params['localAuth']) {
		echo Html::a('Сбросить пароль',['site/password-set','id'=>$model->id]);
	}
}?>
