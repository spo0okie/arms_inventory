<?php
/** Элемент софта
 * Created by PhpStorm.
 * User: spookie
 * Date: 04.11.2018
 * Time: 15:03
 */
use yii\helpers\Html;

/* @var $model \app\models\Soft */

if (!isset($static_view)) $static_view=false;
if (!isset($show_vendor)) $show_vendor=false;
if (!isset($hitlist)) $hitlist=null;


if (is_object($model)) {
if (!isset($name)) $name=$model->descr;
?>

	<span class="soft-item"
		qtip_ajxhrf="<?= \yii\helpers\Url::to([
			'/soft/ttip',
			'id'=>$model->id,
			'hitlist'=>$hitlist
		])?>"
	>
		<?= Html::a($name,['/soft/view','id'=>$model->id]) ?>
		<?= $static_view?'':Html::a('<span class="fas fa-pencil-alt"/>',['/soft/update','id'=>$model->id]) ?>
	</span>
<?php }