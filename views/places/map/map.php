<?php

use app\assets\ContextMenuAsset;
use app\assets\ImgCropAsset;
use app\models\Places;
use app\models\Techs;
use app\models\ui\MapItemForm;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $models app\models\Places[] */

ImgCropAsset::register($this);
ContextMenuAsset::register($this);

const MAP_SIZE=1100;

if (is_object($model->mapImage)) {
	$bg=$model->mapImage;
	$srcWidth=$bg->getImageWidth();
	$srcHeight=$bg->getImageHeight();
	if ($srcWidth>$srcHeight) {
		$imgWidth=MAP_SIZE;
		$imgHeight=$srcHeight*MAP_SIZE/$srcWidth;
	} else {
		$imgHeight=MAP_SIZE;
		$imgWidth=$srcWidth*MAP_SIZE/$srcHeight;
	}
	
	$deletionUrl=Url::to(['/places/map-delete']);
	
	$jsContextMenu= <<<JS
		//из координат на странице получить координаты на карте
		function getMapCoords(e){
    		let \$map=$('div#place-map');
    		let offset=\$map.offset();
    		let x=e.pageX-offset.left;
    		let y=e.pageY-offset.top;
    		return {x:x,y:y};
		}
		
		//сформировать прямоугольник нового объекта из координат клика
		function getNewItemRect(coords){
    		//console.log(coords);
    		let width=Math.min($imgWidth,50);
    		let height=Math.min($imgHeight,50);
    		let x=Math.round(Math.max(coords.x-width/2,0));
    		let y=Math.round(Math.max(coords.y-height/2,0));
    		return {x:x,y:y,width:width,height:height};
		}

		//сформировать прямоугольник нового объекта из координат клика
		function getItemRect(item){
    		//console.log(coords);
    		let width=item.css('width');
    		let height=item.css('height');
    		let x=item.css('left');
    		let y=item.css('top');
    		return {
    		    x:x.substring(0,x.length-2),
    		    y:y.substring(0,y.length-2),
    		    width:width.substring(0,width.length-2),
    		    height:height.substring(0,height.length-2)};
		}
		
		function addMapItem (rect){
    		//console.log('adding item');
  			$('#place-map').hide();
  			$('#item-edit').show();
  			CropSelectInitRect(rect);
		}
		
		function switchMapForm(type,item_id=null){
    		switch (type) {
    		    case 'techs': {
   					$('#techs_id').show();
   					$('#places_id').hide();
   					$('input#mapitemform-item_type').val('techs');
   					if (item_id) {
   					    console.log("setting tech_id => "+item_id)
	   					$('select#mapitemform-techs_id').val(item_id).trigger("change");
   					}
    		        break;
    		    }
    		    case 'places': {
   					$('#techs_id').hide();
   					$('#places_id').show();
   					$('input#mapitemform-item_type').val('places');
   					if (item_id) {
   					    console.log("setting places_id => "+item_id)
	   					$('select#mapitemform-places_id').val(item_id).trigger("change");
   					}
    		        break;
    		    }
    		}
		}
		
		
		
		//для элементов карты
        $.contextMenu({
            selector: '.map-item',
            callback: function(key, options, e) {
                let \$element=options.\$trigger;
                switch (key) {
                    case 'edit': {
                        addMapItem(getItemRect(\$element));
						switchMapForm(
						    \$element.attr('data-item-type'),
						    \$element.attr('data-item-id')
						);
						break;
                    }
                    case 'delete': {
                        if (confirm("Убрать этот элемент с карты?")) {
                            window.location.href = '$deletionUrl?id={$model->id}&item_type='+
                            	\$element.attr('data-item-type') + '&item_id='+\$element.attr('data-item-id')
                        }
                        break;
                    }
                }
            },
            items: {
                "edit": {name: "Изменить", icon: "edit"},
                "delete": {name: "Удалить", icon: "delete"}
            }
        });

		//для пустого места
       $.contextMenu({
            selector: '#place-map',
            callback: function(key, options,e) {
                switch (key) {
                    case 'add-place': {
    					switchMapForm('places');
                        addMapItem(getNewItemRect(getMapCoords(e)));
                        break;
                    }
                    case 'add-tech': {
    					switchMapForm('techs');
                        addMapItem(getNewItemRect(getMapCoords(e)));
                        break;
                    }
                }
            },
            items: {
                "add-place": {name: "Помещение", icon: "add"},
                "add-tech": {name: "АРМ/оборудование", icon: "add"}
            }
        });
JS;



	$items=[];
	$map=json_decode($model->map,true);
	if (is_array($map)) {
		if (isset($map['techs'])) {
			$techs=$map['techs'];
			foreach ($techs as $id=>$coords) {
				$tech=Techs::findOne($id);
				$items[]=Html::tag('div',is_object($tech)?$tech->name:'NOT FOUND',[
					'data'=>['item-type'=>'techs','item-id'=>$tech->id],
					'style'=>''
						.'left:'.$coords['x'].'px;'
						.'top:'.$coords['y'].'px;'
						.'width:'.$coords['width'].'px;'
						.'height:'.$coords['height'].'px;',
					'qtip_ajxhrf'=>Url::to(['/techs/ttip','id'=>$id]),
					'class'=>'map-item techs',
					'onclick'=>'window.location.href="'.Url::to(['/techs/view','id'=>$tech->id]).'"'
				]);
			}
		}
		if (isset($map['places'])) {
			$places=$map['places'];
			foreach ($places as $id=>$coords) {
				$place= Places::findOne($id);
				$items[]=Html::tag('div',is_object($place)?$place->name:'NOT FOUND',[
					'data'=>['item-type'=>'places','item-id'=>$place->id],
					'style'=>''
						.'left:'.$coords['x'].'px;'
						.'top:'.$coords['y'].'px;'
						.'width:'.$coords['width'].'px;'
						.'height:'.$coords['height'].'px;',
					'qtip_ajxhrf'=>Url::to(['/places/ttip','id'=>$id]),
					'class'=>'map-item places',
					'onclick'=>'window.location.href="'.Url::to(['/places/view','id'=>$place->id]).'"',
				]);
			}
		}
	}
	
	echo Html::tag('div',implode($items),[
		'id'=>'place-map',
		'style'=>"width:{$imgWidth}px; height:{$imgHeight}px; background-image: URL('{$bg->fullFname}'); background-size:{$imgWidth}px; position: relative",
	]);
	$this->registerJs($jsContextMenu);
	$editItem=new MapItemForm(['item_type'=>'none','place_id'=>$model->id]);
	?>
	<div style="display: none" id="item-edit" class="text-center">
		<?= $this->render('_form',['model'=>$editItem,'places'=>$model->children,'techs'=>$model->techs,'mapImage'=>$bg]) ?>
	</div>
	
	<?php
}