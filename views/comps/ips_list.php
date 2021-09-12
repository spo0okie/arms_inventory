<?php
/**
 * Список адресов машины
 * User: aareviakin
 * Date: 01.03.2019
 * Time: 19:18
 */
/* @var $this yii\web\View */
/* @var $model app\models\Comps */

if (!isset($static_view)) $static_view=false;
if (!isset($glue)) $glue='<br />';

?>

        <h4>IP адрес(а)</h4>
        <?php
		$output=[];
		foreach ($model->netIps as $ip) {
			$output[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>$static_view]);
		}
		echo implode($glue,$output);
		?>
