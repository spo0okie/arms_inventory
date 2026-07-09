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

<?php
//Активные адреса — объекты NetIps (relation уже отфильтрован от ip_ignore
//в Comps::beforeSave). Игнорируемые — строки из ip_ignore: отдельными
//записями NetIps они больше не являются, поэтому рендерим их из текста и
//только в интерактивном режиме (в тултипах/печати скрытые не нужны).
$activeIps=$model->netIps;
$ignoredIps=$static_view?[]:array_filter(array_map('trim',$model->ignoredIps),'strlen');

if (count($activeIps) || count($ignoredIps)) {
	$rows=[];
	foreach ($activeIps as $netIp)
		$rows[]=$this->render('ip_item',[
			'model'=>$netIp,'owner'=>$model,'ignored'=>false,'static_view'=>$static_view,
		]);
	foreach ($ignoredIps as $addr)
		$rows[]=$this->render('ip_item',[
			'address'=>$addr,'owner'=>$model,'ignored'=>true,'static_view'=>$static_view,
		]);

	[$ipsTitle,$ipsTitleOptions]=\app\components\ModelFieldWidget::fieldTitle($model,'netIps',null,'IP адрес(а)');
	echo \app\components\ExpandableCardWidget::widget([
		'content'=>Html::tag('h4',$ipsTitle,$ipsTitleOptions).implode($glue,$rows),
		'cardClass'=>'pe-4 '.($lineBreak?'line-break':'line-nobr'),
	]);
}
?>

<div class="pe-5">
	<?= \app\components\ModelFieldWidget::renderFieldTitle($model,'mac') ?>
	<?php /* значения - ссылками на поиск по MAC (функциональность, не просто вывод значения) */ ?>
	<p><?php
		$output=[];
		foreach (explode("\n",$model->formattedMac) as $mac) {
			$output[]= Html::a($mac,['comps/index','CompsSearch[mac]'=>$mac]);
		}
		echo implode($glue,$output);
	?></p>
</div>
