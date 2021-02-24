<?php
/**
 * Список адресов машины
 * User: aareviakin
 * Date: 01.03.2019
 * Time: 19:18
 */
/* @var $this yii\web\View */
/* @var $model app\models\Comps */

if (!isset($static_view)) $static_view=false;

?>

        <h4>IP адрес(а)</h4>
        <?php
		$output=[];
        /*$ignored=explode("\n",$model->ip_ignore);
        foreach (explode("\n",$model->ip) as $ip) {
            $ip=trim($ip);
            if (strlen($ip)) {
                $excluded=array_search($ip,$ignored);
                $class=$excluded?'excluded':'included';
                $current=
					"<span class=\"$class\">".$ip.
                    \yii\helpers\Html::a('<span class="glyphicon glyphicon-log-in"/>','remotecontrol://'.$ip).
					' ';

                    if (!$static_view && $excluded) $current.=
                        \yii\helpers\Html::a('<span class="glyphicon glyphicon-eye-open"/>',
                            ['comps/unignoreip', 'id'=>$model->id,'ip' => $ip],
                            ['title'=>'Вернуть отображение этого IP']
                        );
                    
                    if (!$static_view && !$excluded) $current.=
                        \yii\helpers\Html::a('<span class="glyphicon glyphicon-eye-close"/>',
                            ['comps/ignoreip', 'id'=>$model->id,'ip' => $ip],
                            ['title'=>'Скрыть этот IP как служебный/внутренний/ВПН']
                        );
				$current.='</span>';
                $output[]=$current;
        	}
        }*/
		foreach ($model->netIps as $ip) {
			$output[]=$this->render('/net-ips/item',['model'=>$ip]);
		}
		echo implode('<br />',$output);
		?>
