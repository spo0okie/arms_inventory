<?php


namespace app\helpers;


use app\models\ArmsModel;

class HtmlHelper extends \yii\helpers\Html
{
	
	/**
	 * @param ArmsModel $model
	 * @param bool $show_archived
	 */
	static public function ArchivedDisplay($model,$show_archived=true) {
		return ($model->archived&&!$show_archived)?'style="display:none"':'';
	}
	
}