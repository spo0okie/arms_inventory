<?php
/**
 * Вывод портов устройства
 * User: aareviakin
 * Date: 01.03.2021
 * Time: 20:49
 */

/* @var \app\models\Techs $model */
/* @var $this yii\web\View */

//Порты которые должны быть у этой модели оборудования
$model_ports=[];

//Порты которые созданы для этой модели
$custom_ports=$model->ports;

?>

<table class="table table-striped">
<tr>
	<th>
		Порт
	</th>
	<th>
		Пояснение
	</th>
	<th colspan="3">
		Соединение с
	</th>
</tr>

<?php
//если корректно пришита модель и у модели есть набор портов

foreach ($model->portsList as $port) {
	$port['model']=$model;
	echo $this->render('port-row',$port);
}
/*
if (is_object($model->model) && strlen($model->model->ports)) {
	//распарсиваем порты
	foreach (explode("\n",$model->model->ports) as $port) {
		$tokens=explode(' ',$port);
		
		//вытаскиваем первое слово
		$port_name=trim($tokens[0]);
		unset ($tokens[0]);
		
		//остальные слова - комментарий
		$port_comment=implode(' ',$tokens);
		
		//ищем есть ли порт-объект к этому порту
		$port_link=null;
		foreach ($custom_ports as $i=>$custom_port) if ($custom_port->name == $port_name) {
			$port_link=$custom_port;
			unset($custom_ports[$i]);
		}
		
		echo $this->render('port-row',compact('model','port_name','port_comment','port_link'));
	}
}

foreach ($custom_ports as $port_link)
	echo $this->render('port-row',compact('model','port_link'));
*/
?>


</table>

