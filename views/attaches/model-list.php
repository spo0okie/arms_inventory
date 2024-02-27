<?php
/**
 * Список вложений прикрепленных к модели
 * User: aareviakin
 * Date: 14.05.2023
 * Time: 14:33
 */

/** @var yii\web\View $this */
/** @var Techs $model */

use app\models\Attaches;
use app\models\Techs;

if (!isset($static_view)) $static_view=false;
if (!isset($link)) $link=$model::tableName().'_id';

$attaches=$this->render('list',['models'=>$model->attaches]);
if ($attaches) $attaches.='<br />';

?>
<h4>Файлы:</h4>
<p><?= $attaches ?>
	<?php if (!$static_view) {
		//моздаем кнопочку добавления к продукту и открываем модальную форму выбора продукта
		echo $this->render('/attaches/_inline_form',[
			'model'=>new Attaches(),
			'link'=>$link,
			'linkModel'=>$model,
		]);
		
	} ?>
</p>

