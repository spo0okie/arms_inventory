<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;


if (is_object($model)) {
	//выбираем иконку
	if ($model->is_service) {
		$icon=$model->is_end_user?
			'<span class="fas fa-users-cog service-icon" title="'.\app\models\Services::$user_service_title.'"></span>':
			'<span class="fas fa-cog service-icon" title="'.\app\models\Services::$tech_service_title.'"></span>';
	} else {
		$jobTitle=$model->is_end_user?
			\app\models\Services::$user_job_title:
			\app\models\Services::$tech_job_title;
		if ($model->loaderCount('orgInets') ?? count($model->orgInets))
			$icon='<span class="fas fa-network-wired service-icon" title="'.$jobTitle.'"></span>';
		elseif ($model->loaderCount('orgPhones') ?? count($model->orgPhones))
			$icon='<span class="fas fa-phone-alt service-icon" title="'.$jobTitle.'"></span>';
		else $icon=$model->is_end_user?
			'<span class="fas fa-broom service-icon" title="'.\app\models\Services::$user_job_title.'"></span>':
			'<span class="fas fa-screwdriver service-icon" title="'.\app\models\Services::$tech_job_title.'"></span>';
	}
	echo $icon;
}