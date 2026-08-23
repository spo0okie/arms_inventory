<?php
/**
 * Блок «Сетевые порты» карточки устройства.
 *
 * Таблица здесь одна и её владелец — карточка. Опрос коммутатора
 * (интеграция macsearch) не рисует рядом вторую таблицу, а заменяет
 * содержимое этого же блока обогащённой версией: где записанное совпало с
 * найденным, где разошлось и что предлагается сделать
 * (plans/network-map.md, этап 3.4).
 */

/* @var \app\models\Techs $model */
/* @var $this yii\web\View */

$containerId = 'techs-ports-'.$model->id;

//порты объявляет модель оборудования, но у экземпляра имена могут отличаться
//(стек, переименование на MikroTik) - тогда действует его «порты фактически»
if (!count($model->portsTemplate)) { ?>
	<div class="alert alert-striped">
		У этой модели оборудования нет сетевых портов.
		Если это неверно - <?= \yii\helpers\Html::a('отредактируйте модель оборудования',[
			'/tech-models/update',
			'id'=>$model->model_id,
			'return'=>'previous'
		]) ?>
		или объявите порты этого устройства в поле
		<?= \yii\helpers\Html::a('«Порты фактически»',[
			'/techs/update',
			'id'=>$model->id,
			'return'=>'previous'
		]) ?>.
	</div>
	<br/>
<?php }

if (count($model->portsList)) { ?>
	<div id="<?= $containerId ?>">
		<?= $this->render('_ports-table', ['model' => $model, 'ports' => null]) ?>
	</div>
<?php }

//кнопка опроса появляется только у коммутатора с настроенной интеграцией;
//результат ложится в контейнер выше, а не рядом с ним
echo \app\components\integrations\PanelsWidget::widget([
	'model' => $model,
	'only' => 'macsearch/switch',
	'target' => $containerId,
]);

echo ' '.\yii\helpers\Html::a(
	'Добавить нестандартный порт',
	[
		'/ports/create',
		'Ports[techs_id]'=>$model->id,
		'return'=>'previous'
	],[
		'class'=>'btn btn-sm btn-info',
		'title' => 'Стандартные порты редактируются в модели оборудования'
	]
) ?>
