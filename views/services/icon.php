<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;


if (is_object($model)) {
	//выбираем иконку
	if ($model->is_service) {
		$icon=$model->is_end_user?
			'<span class="fas fa-user service-icon" title="'.\app\models\Services::$user_service_title.'"></span>':
			'<span class="fas fa-cog service-icon" title="'.\app\models\Services::$tech_service_title.'"></span>';
	} else {
		if (count($model->orgInets))
			$icon='<span class="fas fa-network-wired service-icon" title="'.\app\models\Services::$user_job_title.'"></span>';
		elseif (count($model->orgPhones))
			$icon='<span class="fas fa-phone-alt service-icon" title="'.\app\models\Services::$user_job_title.'"></span>';
		else $icon=$model->is_end_user?
			'<span class="fas fa-broom service-icon" title="'.\app\models\Services::$user_job_title.'"></span>':
			'<span class="fas fa-screwdriver service-icon" title="'.\app\models\Services::$tech_job_title.'"></span>';
	}
	echo $icon;
}