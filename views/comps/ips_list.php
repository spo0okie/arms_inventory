<?php
/**
 * Список адресов машины
 * User: aareviakin
 * Date: 01.03.2019
 * Time: 19:18
 */
/* @var $this yii\web\View */
/* @var $model app\models\Comps */

use app\components\ListObjectsWidget;
use yii\helpers\Html;

if (!isset($static_view)) $static_view=false;
if (!isset($glue)) $glue='<br />';
if (!isset($lineBreak)) $lineBreak=false;

if ($glue=='<br />') {
	$glue=' ';
	$lineBreak=true;
}

?>

<?= ListObjectsWidget::widget([
	'models'=>$model->netIps,
	'title'=>'IP адрес(а)',
	'lineBr'=>$lineBreak,
	'glue'=>$glue,
	'item_options'=>[
		'static_view'=>$static_view,
	],
	'card_options'=>['cardClass'=>'pe-4'],
]) ?>

<div class="pe-5">
	<h4>MAC адрес(а)</h4>
	<p><?php
		$output=[];
		foreach (explode("\n",$model->formattedMac) as $mac) {
			$output[]= Html::a($mac,['comps/index','CompsSearch[mac]'=>$mac]);
		}
		echo implode($glue,$output);
	?></p>
</div>
