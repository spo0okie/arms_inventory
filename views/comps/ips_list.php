<?php
/**
 * Список адресов машины
 * User: aareviakin
 * Date: 01.03.2019
 * Time: 19:18
 */
/* @var $this yii\web\View */
/* @var $model app\models\Comps */

use app\components\ListObjectWidget;
use yii\helpers\Html;

if (!isset($static_view)) $static_view=false;
if (!isset($glue)) $glue='<br />';

?>

<?= ListObjectWidget::widget([
	'models'=>$model->netIps,
	'title'=>'IP адрес(а)',
	'lineBr'=>($glue=='<br />'),
]) ?>

<h4>MAC адрес(а)</h4>
<p><?php
	$output=[];
	foreach (explode("\n",$model->formattedMac) as $mac) {
		$output[]= Html::a($mac,['comps/index','CompsSearch[mac]'=>$mac]);
	}
	echo implode($glue,$output);
?></p>