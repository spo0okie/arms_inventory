<?php
/* возвращает отрендеренные элементы участники ACE */

/* @var $this yii\web\View */
/* @var $model app\models\Aces */


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

foreach ($model->networks as $network) {
	if (!isset($items[$network->sname]))
		$items[$network->sname]=$this->render('/networks/item',['model'=>$network,'static_view'=>true,'icon'=>true,'no_class'=>true]);
}

foreach ($model->services as $service) {
	foreach ($service->comps as $comp) {
		if (!isset($items[$comp->sname]))
			$items[$comp->sname]=$this->render('/comps/item',['model'=>$comp,'static_view'=>true,'icon'=>true,'no_class'=>true]);
	}
	foreach ($service->techs as $tech) {
		if (!isset($items[$tech->sname]))
			$items[$tech->sname] = $this->render('/techs/item', ['model' => $tech, 'static_view' => true, 'icon' => true, 'no_class' => true]);
	}
}

foreach ($items as $key=>$value) {
	if (!strlen($value)) unset($items[$key]);
}

ksort($items,SORT_STRING);

return $items;