<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$aces=$model->aces;
if (!is_array($aces)) $aces=[];

$acesContent=[];
foreach ($aces as $ace) {
	$acesContent[]=$this->render('/aces/tdrow',['model'=>$ace]);
}
$acesContent[]=
'<td colspan="2">'.
	\yii\helpers\Html::a('Добавить доступ',['aces/create','acls_id'=>$model->id],['class'=>'btn btn-success']).
'</td>';

?>

<tr>
	<td class="acl-resource" rowspan="<?= count($acesContent) ?>">
		<?= $this->render('item',['model'=>$model]) ?>
	</td>
<?php
	$row=0;
	foreach ($acesContent as $aceContent) {
		if ($row) echo '<tr>';
		echo $aceContent;
		if ($row) echo '</tr>';
		$row++;
	}
?>
</tr>
