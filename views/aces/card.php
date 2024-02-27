<?php

use app\components\HistoryWidget;
use app\helpers\ArrayHelper;
use app\models\Aces;
use kartik\markdown\Markdown;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$items=[];
$hasIp=$model->hasIpAccess();
$hasPhone=$model->hasPhoneAccess();

foreach ($model->users as $user) {
	
	if ($hasPhone)
		$rendered = $this->render('/users/item', ['model' => $user, 'static_view' => true, 'icon' => true, 'name' => $user->shortName.(strlen($user->Phone)?' ('.$user->Phone.')':'')]);
	else
		$rendered = $this->render('/users/item', ['model' => $user, 'static_view' => true, 'icon' => true, 'short' => true]);
	
	if ($hasIp) {
		$ips=[];
		foreach ($user->netIps as $ip) {
			$ips[$ip->sname] = $this->render('/net-ips/item', ['model' => $ip, 'static_view' => true, 'icon' => true, 'no_class' => true]);
			$items[$ip->sname]='';
		}
		if (count($ips)) $rendered.=': '.implode(', ',$ips);
	}
	
	
	$items[$user->shortName]=$rendered;
}

foreach ($model->comps as $comp) {
	$rendered = $this->render('/comps/item', ['model' => $comp, 'static_view' => true, 'icon' => true]);
	
	if ($hasIp) {
		$ips=[];
		foreach ($comp->netIps as $ip) {
			$ips[$ip->sname] = $this->render('/net-ips/item', ['model' => $ip, 'static_view' => true, 'icon' => true, 'no_class' => true]);
			$items[$ip->sname]='';
		}
		if (count($ips)) $rendered.=': '.implode(', ',$ips);
	}
	$items[$comp->name]=$rendered;
}

foreach ($model->netIps as $ip) {
	if (!isset($items[$ip->sname]))
		$items[$ip->sname]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true,'icon'=>true,'no_class'=>true]);
}

if (strlen($model->comment))
	$items[$model->comment]='<span class="text-wrap">'.$model->comment.'</span>';

ksort($items,SORT_STRING);



$accessTypes=[];

foreach ($model->accessTypes as $accessType)
	$accessTypes[]=$accessType->name;

if (!count($accessTypes)) $accessTypes[]= Aces::$noAccessName;
?>

<div class="card w-100 my-2 ace-card shadow-sm g-0" id="ace_card_<?= $model->id ?>">
	<div class="d-flex g-0">
		<div class="p-2 text-wrap flex-fill small">
			<?php if (count($items)) {
				echo ArrayHelper::implode(' <br /> ',$items);
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
		<div class="col-md-4 ace-access-card d-flex flex-column pt-2 pull-right">
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
						'data-update-url' => Url::to(['/aces/view','id'=>$model->id]),
					]) ?>
					<?=  HistoryWidget::widget([
						'model'=>$model,
						'showUser'=>false,
						'showDate'=>false,
						'empty'=>'',
						'iconOptions'=>['class'=>'btn btn-sm text-white ace-access-buttons'],
					])?>
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
	<?php if ($model->notepad) { ?>
		<div class="p-1 small text-wrap border-top">
			<?=  Markdown::convert($model->notepad) ?>
		</div>
	<?php } ?>

</div>
