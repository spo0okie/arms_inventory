<?php


namespace app\helpers;

use yii\helpers\Html;

class HtmlHelper extends Html
{
	
	/**
	 * @param object $model
	 * @param bool   $show_archived
	 * @return string
	 */
	static public function ArchivedDisplay(object $model, $show_archived=true) {
		return ($model->archived&&!$show_archived)?'style="display:none"':'';
	}
	
}