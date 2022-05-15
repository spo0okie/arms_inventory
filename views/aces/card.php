<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$items=[];

foreach ($model->users as $user)
	$items[$user->shortName]=$this->render('/users/item',['model'=>$user,'static_view'=>true,'icon'=>true,'short'=>true]);

foreach ($model->comps as $comp)
	$items[$comp->name]=$this->render('/comps/item',['model'=>$comp,'static_view'=>true,'icon'=>true]);

foreach ($model->netIps as $ip)
	$items[$ip->sname]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true,'icon'=>true,'no_class'=>true]);

if (strlen($model->comment))
	$items[$model->comment]=$model->comment;

ksort($items,SORT_STRING);



$accessTypes=[];

foreach ($model->accessTypes as $accessType)
	$accessTypes[]=$accessType->name;

if (!count($accessTypes)) $accessTypes[]=\app\models\Aces::$noAccessName;
?>

<div class="card w-100 my-2 ace-card shadow-sm g-0" id="ace_card_<?= $model->id ?>">
	<div class="row g-0">
		<div class="col-md-8 p-2">
			<?php if (count($items)) {
				echo implode(' <br /> ',$items);
			} else { ?>
				<span class="text-center divider2-striped">
					<span class="ace-card p-1">
						<span class="fas fa-exclamation-triangle"></span>
						НЕТ ЗАПИСЕЙ
						<span class="fas fa-exclamation-triangle"></span>
					</span>
				</span>
				<span class="row text-center"><small >добавьте записи в этот элемент списка доступа</small></span>
			
			<?php } ?>
		</div>
		<div class="col-md-4 ace-access-card d-flex flex-column pt-2">
			<div class="text-center text-white"><?= implode(', ',$accessTypes) ?></div>
			
			<div class="row mt-auto g-0">
				<div class="btn-group" role="group">
					<?=  Html::a('<span class="fas fa-pencil-alt"></span>',[
						'/aces/update',
						'id'=>$model->id,
						'ajax'=>1,
						'modal'=>'modal_form_loader'
					],[
						'class' => 'btn btn-sm text-white ace-access-buttons open-in-modal-form',
						'title' => 'Правка элемента доступа',
						'data-update-element' => '#ace_card_'.$model->id,
						'data-update-url' => Url::to(['/aces/view','id'=>$model->id,'ajax'=>1]),
					]) ?>
					<?=  Html::a('<span class="fas fa-trash"/>', ['aces/delete', 'id' => $model->id,'return'=>'previous'], [
						'data' => [
							'confirm' => 'Удалить этого участника доступа? Действие необратимо!',
							'method' => 'post',
						],
						'class'=>'btn btn-sm text-white ace-access-buttons'
					])?>
				</div>
			</div>
		</div>
	</div>

</div>
