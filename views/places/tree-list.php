<?php
/**
 * Один уровень древовидного списка помещений
 * User: aareviakin
 * Date: 24.11.2018
 * Time: 10:52
 */

/* @var $this yii\web\View */
/* @var $models app\models\Places[] */
/* @var $parent_id integer */
/* @var $tree_level integer */
if (!isset($tree_level)) $tree_level=0;
$filtered=[];
//echo $parent_id;
//перебираем все модельки и отфильтровываем нужные нам
foreach ($models as $model) {
	if ($model->parent_id == $parent_id) {
		$filtered[]=$model;
	}
}

if (count($filtered)) {
	//если чтото нафильтровали то вперед!
?>
	<ul class="places_tree places_tree_lev_<?= $tree_level ?>">
		<?php foreach ($filtered as $model) {
			//рисуем элемнт ?>
			<li>
                <?= $this->render('item',['model'=>$model,'static_view'=>false]) ?>
                <?= $subtree=$this->render('tree-list',[
                    'models'=>$models,
                    'parent_id'=>$model->id,
                    $tree_level+1
                ]); ?>
                <?php if (!strlen($subtree)) echo \yii\helpers\Html::a(
                    '<span class="glyphicon glyphicon-plus-sign"></span>',
                        ['places/create','parent_id'=>$model->id]
                ) ?>
            </li>
		<?php } ?>
        <li>
            <?= \yii\helpers\Html::a(
	            'Добавить новое помещение',
	            ['places/create','parent_id'=>$parent_id]
            ) ?>
        </li>
	</ul>



<?php
}
