<?php


namespace app\helpers;

use yii\helpers\Html;

class HtmlHelper extends Html
{
	
	/**
	 * @param mixed  $model
	 * @param bool   $show_archived
	 * @return string
	 */
	static public function ArchivedDisplay($model, $show_archived=true) {
		$archived=is_object($model)?$model->archived:$model;
		return ($archived&&!$show_archived)?'display:none':'';
	}
	
	/**
	 * @param mixed  $model
	 * @param bool   $show_archived
	 * @return string
	 */
	static public function ArchivedStyle($model, $show_archived=true) {
		$archived=is_object($model)?$model->archived:$model;
		return ($archived&&!$show_archived)?'style="display:none"':'';
	}
	
}