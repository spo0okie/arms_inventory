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
			$this->render('/ports/item',[
				'model'=>$port_link,
				'return'=>'previous',
				'modal'=>true,
			]):
			\yii\helpers\Html::a(\app\models\Ports::$port_prefix.$port_name,[
				'/ports/create',
				'return'=>'previous',
				'Ports[name]'=>$port_name,
				'Ports[comment]'=>$port_comment,
				'Ports[techs_id]'=>$model->id
			],['class'=>'open-in-modal-form','data-reload-page-on-submit'=>1])
		?>
	</td>
	<td>
		<?= is_object($port_link)?$port_link->comment:$port_comment ?>
	</td>
	<?php if (is_object($port_link)) {
		
		if (is_object($port_link->linkPort))
			echo '<td><span class="fas fa-exchange-alt"></span></td>';
		else
			echo '<td></td>';
		
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

