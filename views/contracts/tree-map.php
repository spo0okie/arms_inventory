<?php
/**
 * Дерево документов
 * User: aareviakin
 * Date: 20.01.19
 * Time: 19-36
 */

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $selected_id integer */
/* @var $map string */
/* @var $static_view bool */

/**
 * Типы вывода карты
 * full - показать полную карту
 * chain-up - цепочка до корневого документа
 */

if (!isset($map)) $map='full';
if (!isset($show_payment)) $show_payment=false;

if (is_object($model)) switch ($map) {
	case 'full':
		//ПОЛНАЯ КАРТА
		//запоминаем выбранную позицию
		$selected_id=$model->id;
		//уходим в корень
		while (is_object($model->parent)) $model=$model->parent;
		echo $this->render('tree-list',['model'=>$model,'selected_id'=>$selected_id,'show_payment'=>$show_payment]);
		$js=<<<JS
			$(".ul-dropfree").find("li:has(ul.candrop)").prepend('<div class="drop"></div>');
			$(".ul-dropfree div.drop").click(function() {
				if ($(this).nextAll("ul.candrop").css('display')=='none') {
					$(this).nextAll("ul.candrop").slideDown(400);
					$(this).css({'background-position':"-11px 0"});
				} else {
					$(this).nextAll("ul.candrop").slideUp(400);
					$(this).css({'background-position':"0 0"});
				}
			});
			$(".ul-dropfree").find("ul.hideme").slideUp(0).parents("li").children("div.drop").css({'background-position':"0 0"});
JS;
		$this->registerJs($js);
		break;
	case 'chain-up':
		//ЦЕПОЧКА ДО КОРНЯ
		$output=$this->render('item',['model'=>$model,'show_payment'=>$show_payment]);
		while (is_object($model->parent)) {
			$model=$model->parent;
			$output=$this->render('item',['model'=>$model,'show_payment'=>$show_payment]).'<ul class="contracts_tree tree"><li>'.$output.'</li></ul>';
		}
		echo $output;
		break;
}


