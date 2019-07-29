<?php
/*
 * Рисует не одиночный элемент, а всю цепочку наследования
 * (если она цепочка а не один элемент)
 */


use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $selected_id integer */

if (!isset($selected_id)) $selected_id=-1;

//рисуем в том случае, если это корневой предок
if (is_object($model) && !is_object($model->predecessor)) {
	if (!isset($name)) $name = $model->name;
	//собираем линейную
	$items = $model->successorsChain;

	//тут у нас логика раздвояица. либо мы просто рисуем итем если он один, либо список с пллюсиком
	echo $this->render('/contracts/item', [
		'model' => $items[count($items)-1],
		'active' => true,
        'selected' => $selected_id==$items[count($items)-1]->id
	]);



	if (count($items)>1) {
	    $hidelist=true;
		for ($i = count($items)-2; $i >=0; $i--) if ($selected_id==$items[$i]->id) $hidelist=false;
    ?>
		<ul class="candrop <?= $hidelist?'hideme':'' ?>" >
			<?php for ($i = count($items)-2; $i >=0; $i--) { ?>
				<li class="candrop">
					<?= $this->render('/contracts/item', [
						'model' => $items[$i],
						'active' => false,
                        'selected' => $selected_id==$items[$i]->id
					]); ?>
				</li>
			<?php } ?>
		</ul>
	<?php }
    }
?>
