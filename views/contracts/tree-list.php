<?php
/**
 * Список документов
 * User: aareviakin
 * Date: 20.01.19
 * Time: 19-36
 */

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $selected_id integer */
if (!isset($show_payment)) $show_payment=false;

?>
<?= $this->render('item-chain',[
    'model'=>$model,
    'selected_id'=>$selected_id,
	'show_payment'=>$show_payment
]) ?>

	<ul class="ul-treefree ul-dropfree">
		<?php foreach ($model->chainChildren as $child) if (!$child->is_successor) { ?>
			<li>
				<?= $this->render('tree-list',[
					'model'=>$child,
					'selected_id'=>$selected_id,
					'show_payment'=>$show_payment
				]); ?>
			</li>
		<?php } ?>
	</ul>



