<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

$domain = is_object($model->domain)?$model->domain->name:'- не в домене - ';

$this->title = 'ОС '.$domain.'\\'.strtolower($model->name);
$this->params['breadcrumbs'][] = ['label' => 'ОС', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\helpers\Url::remember();
$manufacturers=\app\models\Manufacturers::fetchNames();
$model->swList->sortByName();

?>
<div class="comps-view row">
	<div class="col-md-6">
		<?= $this->render('card',['model'=>$model]) ?>

		<div class="hardware_settings">
			<h4>Железо</h4>
			<?php if ($model->ignore_hw) echo "<p>Игнорируется.</p>"; else {
				echo '<table>';
				foreach ($model->getHardArray() as $item) {
					echo $this->render('/hwlist/item',
						compact('model','item', 'manufacturers')
					);
				}
				echo '</table>';
			} ?>
		</div>

	</div>
	<div class="col-md-6">
		<div class="software_settings">
			<h3>Софт</h3>
			<?php // echo '<pre>'; var_dump($model->swList->items); echo '</pre>'; ?>
			<h4 id="ignored_toggle">Игнорируемый</h4><table>
				<?php foreach ($model->swList->items as $item) if ($item['ignored']) {
					echo $this->render('/swlist/item', compact('item', 'model'));
				} ?></table>

			<h4>Согласованный</h4><table>
				<?php foreach ($model->swList->items as $item) if (!$item['ignored'] && $item['agreed']) {
					echo $this->render('/swlist/item', compact('item', 'model'));
				} ?></table>

			<h4>Требующий согласования</h4><table>
				<?php foreach ($model->swList->items as $item) if (!$item['ignored'] && !$item['agreed']) {
					echo $this->render('/swlist/item', compact('item', 'model'));
				}?> </table>
			<h4>Не распознанный:</h4>
			<table>
				<?php if (is_array($model->swList->data)) foreach ($model->swList->data as $item) { ?>
					<?= $this->render('soft_item_unrecognized', compact('model','item')) ?>
				<?php } ?>
			</table>
		</div>

	</div>
</div>