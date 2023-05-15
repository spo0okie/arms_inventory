<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 04.03.2018
 * Time: 11:17
 *
 * Рендер нераспознанного элемента софта
 * @var \app\models\Comps $model
 */
use yii\bootstrap5\Modal;

if (!isset($item['manufacturers_id'])) $item['manufacturers_id']=null;

/*вывод производителя*/
?>
<tr class="software_item">
<td class="os-name"><?= $model->name ?></td>
<td class="manufacturer">
<?php if (!is_null($item['manufacturers_id'])){
    //если производитель определен, то выводим его из таблицы производителей в виде "кнопочки"
    $dev=\app\models\Manufacturers::fetchItem($item['manufacturers_id']);
    echo $this->render('/manufacturers/item',['model'=>$dev]);
} else {
    //иначе выводим производителя из отпечатка сканирования и предлагаем кнопочку чтобы его добавить в таблицу
    echo $item['publisher']; //название

    //если производитель вообще имеется, то предлагаем его добавить в таблицу
    if (strlen($item['publisher'])) echo \yii\helpers\Html::a(
        '<span class="fas fa-plus-circle"/>',
        ['manufacturers-dict/create', 'word' => $item['publisher'],'return'=>'previous'],
        ['title'=>'Добавить производителя в базу','class' => 'passport_tools',]
    );
} ?>

</td>
<td class="product">

<?php



/* вывод наименования ПО */

echo $item['name'];


/*
 *  кнопочки создания продукта и добавления описания к существующему продукту
 *
 *  описание продукта можно добавлять в двух случаях:
 * - если у нас определился производитель
 * - если производитель отсуствует в принципе - это значи что он определенно не определится никогда вообще
 *
 *
 * */



if (
    !strlen($item['publisher'])         //случай когда у нас производитель отсутствует
||
    !is_null($item['manufacturers_id'])
){

    //готовим кнопочки

    //кнопочка создания продукта:
    $btn_create=['soft/create', 'id'=>$model->id,'items' => $item['name'],'descr' => $item['name'],'return'=>'previous'];
    //если производитель определен, то сразу его указываем при создании продукта
    if (!is_null($item['manufacturers_id'])) $btn_create['manufacturers_id']=$item['manufacturers_id'];

    //для кнопочки добавления описания к продукту нам нужны списки продуктов для выбора, к чему добавлять описание
    if (!is_null($item['manufacturers_id']))
        //для случаев, если производитель определен, выводим его продукты (только названия самих продуктов)
        $items=\yii\helpers\ArrayHelper::map(\app\models\Soft::fetchBy(['manufacturers_id'=>$item['manufacturers_id']]),'id','descr');
    else
        //в ином случае выводим список всех продуктов с указанием производителя в названии
        $items=\app\models\Soft::listItemsWithPublisher();



    // кнопочка создания продукта
    echo \yii\helpers\Html::a(
        '<i class="fas fa-plus-circle"></i>',
        $btn_create,
        ['title'=>'Создать продукт из этого элемента','class'=>'passport_tools']
    );

    //моздаем кнопочку добавления к продукту и открываем модальную форму выбора продукта
	echo \yii\helpers\Html::a('<i class="fas fa-wrench"></i>',
		[
			'soft/select-update',
			'name'=>$item['name'],
			'manufacturers_id'=>$item['manufacturers_id']
		],[
    	    'title' => 'Выберите продукт',
			'class' => 'open-in-modal-form'
    	]
	);


} // вот и молодцы!)



?>
</td>
<td class="passport_tools"></td>
</tr>