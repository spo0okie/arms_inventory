<?php
/**
 * Вывод строки портов
 * User: aareviakin
 * Date: 02.03.2021
 * Time: 20:49
 */

/* @var \app\models\Ports $port_link */
/* @var \app\models\Techs $model */
/* @var string $port_name */
/* @var string $port_comment */
/* @var $this yii\web\View */

?>

<tr>
	<td>
		<?= is_object($port_link)?
			$this->render('/ports/item',['model'=>$port_link]):
			\yii\helpers\Html::a('Порт '.$port_name,[
				'/ports/create',
				'return'=>'previous',
				'name'=>$port_name,
				'comment'=>$port_comment,
				'techs_id'=>$model->id
			])
		?>
	</td>
	<td>
		<?= is_object($port_link)?$port_link->comment:$port_comment ?>
	</td>
	<?php if (is_object($port_link)) {
		echo '<td><span class="fas fa-exchange-alt"></span></td>';
		if (is_object($port_link->linkPort)) {
			echo '<td>'.$port_link->linkPort->comment.'</td>';
			echo '<td>'.$this->render('/ports/item',[
					'model'=>$port_link->linkPort,
					'include_tech'=>true,
					'reverse'=>true,
				]).'</td>';
		} elseif (is_object($port_link->linkTech)) {
			echo '<td></td><td>'.$this->render('/techs/item',['model'=>$port_link->linkTech]).'</td>';
		} elseif (is_object($port_link->linkArm)) {
			echo '<td></td><td>'.$this->render('/arms/item',['model'=>$port_link->linkArm]).'</td>';;
		} else {
			echo '<td colspan="2"></td>';
		}
	} else { ?>
		<td colspan="3">
		</td>
	<?php }?>
</tr>

