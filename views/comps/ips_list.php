<?php
/**
 * Список адресов машины
 * User: aareviakin
 * Date: 01.03.2019
 * Time: 19:18
 */

if (!isset($static_view)) $static_view=false;

?>

    <div class="ip_addresses">
        <h3>IP адрес(а)</h3>
        <?php
        $ignored=explode("\n",$model->ip_ignore);
        foreach (explode("\n",$model->ip) as $ip) {
            $ip=trim($ip);
            if (strlen($ip)) {
                $excluded=array_search($ip,$ignored)
                ?>
                <span class="<?= $excluded?'excluded':'inclded'?>">
                    <?= $ip ?>
                    <?php

                    echo \yii\helpers\Html::a('<span class="glyphicon glyphicon-log-in"/>','remotecontrol://'.$ip).' ';

                    if (!$static_view && $excluded) echo
                        \yii\helpers\Html::a('<span class="glyphicon glyphicon-eye-open"/>',
                            ['comps/unignoreip', 'id'=>$model->id,'ip' => $ip],
                            ['title'=>'Вернуть отображение этого IP']
                        );
                    if (!$static_view && !$excluded) echo
                        \yii\helpers\Html::a('<span class="glyphicon glyphicon-eye-close"/>',
                            ['comps/ignoreip', 'id'=>$model->id,'ip' => $ip],
                            ['title'=>'Скрыть этот IP как служебный/внутренний/ВПН']
                        );
                    ?>
                </span>
                <br />
        <?php }
        } ?>

    </div>