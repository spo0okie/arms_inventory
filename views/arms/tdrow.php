<?php
/**
 * Строчка АРМа в карте рабочих мест
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 * @var \app\models\Arms $model
 * @var yii\web\View $this
 */

//подгружаем все ОС АРМа
$comps=$model->comps;
//если ни одной не нашли, то создаем массив из пустого элемента чтобы вывести данные по АРМ без ОС
if (!count($comps)) $comps=[0=>null];

//если ОС больше одной, то готовим ROWSPAN для колонок не относящихся к ОС
$physCount=0;
$vmCount=0;
if (count($comps)>1) {
	foreach ($comps as $comp)
		if ($comp->ignore_hw) $physCount++;
		else $vmCount++;
} else $rowspan='';
$rowspan=(count($comps)>1)?'rowspan="'.count($comps).'"':'';
$rowspanPhys=(count($physCount)>1)?'rowspan="'.count($physCount).'"':'';

//может быть передан список столбцов, которые не нужно выводить
if (!isset($skip)) $skip=[];

$sortedComps=[];

//if (iss)

//поехали!
for ($i=0; $i<count($comps); $i++) {
    $comp=$comps[$i]; ?>
    <tr <?= $model->is_server?'class="server"':''?>>

	    <?php if (!$i) { ?>
            <td class="arm_id" <?= $rowspan ?>><?= $this->render('/arms/item',['model'=>$model]) ?></td>
	    <?php }
	    //если у нас есть ОС
	    $age_class=is_object($comp)?$comp->updatedRenderClass:'';

	    ?>

        <td class="arm_hostname <?= $age_class ?>"><?= is_object($comp)?$this->render('/comps/item',['model'=>$comp]):$model->hostname ?></td>

        <?php if ($model->is_server) { ?>
            <td colspan="2" class="arm_services">
                <?php
                $services=[];
                
                if (isset($comp->user)) $services[]=$this->render('/users/item',['model'=>$comp->user]);
                
                if (isset($comp->services)) foreach ($comp->services as $svc) $services[]=$this->render('/services/item',['model'=>$svc]);
                echo implode('<br />',$services);
                ?>
            </td>
        <?php } else if (!$i) { ?>

            <td class="arm_uname" <?= $rowspan ?>>
                <?= (is_object($model->user))?$this->render('/users/item',['model'=>$model->user]):'' ?>
            </td>

            <td class="arm_uphone <?= count($model->voipPhones)?'tech_voip_phone':'' ?>" <?= $rowspan ?>>
		        <?php if (count($model->voipPhones)) {
		            $phones=[];
		            foreach ($model->voipPhones as $tech) $phones[]=$this->render('/techs/item',['model'=>$tech,'name'=>strlen($tech->comment)?$tech->comment:$tech->attachModel->shortest]);
		            echo implode('<br />',$phones);
		        } ?>
            </td>

        <?php } ?>

	    <?php if (!is_object($comp) || !$comp->ignore_hw) {
	    	if (!$i&&(array_search('arm_model',$skip)===false)) { ?>
            <td class="arm_model" <?= $rowspanPhys ?>>
				<?= $this->render('/tech-models/item',['model'=>$model->techModel,'short'=>true]) ?>
			</td>
        <?php }} else { ?>
			<td class="arm_model">
				<abbr title="Virtual Machine">VM</abbr>
			</td>
		<?php } ?>

	    <?php if (!is_object($comp) || !$comp->ignore_hw) {
			if (!$i&&array_search('hardware',$skip)===false) { ?>
        	    <td class="hardware" <?= $rowspanPhys ?>>
					<?= $this->render('/hwlist/shortlist',['model'=>$model->hwList,'arm_id'=>$model->id]) ?>
					<?php if (count($model->ups)) {
						echo ' / ';
						foreach ($model->ups as $tech) {
							echo $this->render('/techs/item', ['model' => $tech, 'name' => $tech->model->shortest]);
						}
					}
					?>
				</td>
			<?php }
	    } else { ?>
			<td class="hardware">
				<?= $this->render('/hwlist/shortlist',['model'=>$comp->hwList,'vm'=>true]) ?>
			</td>
		<?php } ?>

	    <?php if (!$i) { ?>
            <td class="attachments" <?= $rowspan ?>>
			    <?= $this->render('/arms/item-attachments',compact('model'))?>
            </td>
	    <?php }?>

	    <?php if (!$i) { ?>
            <td class="item_status <?= strlen($model->stateName)?$model->state->code:'' ?>" title="<?= $model->comment ?>" <?= $rowspan ?>><?= $model->stateName ?></td>
	    <?php }?>

        <td class="item_ip"><?= is_object($comp)?$comp->currentIp:'' ?></td>

        <?php if (!$i) { ?>
            <td class="item_sn"<?= $rowspan ?>><?= $model->sn ?></td>

	    <?php }?>
	    <?php if (!$i) { ?>
            <td class="item_invnum"<?= $rowspan ?>><?= $model->inv_num ?></td>

	    <?php }?>
    </tr>

<?php } ?>

<?php
/*foreach ($model->techs as $tech ) {
    //вываодим все привязанные к АРМу оборудвания
    if (!$tech->isVoipPhone && !$tech->isUps)
        echo $this->render('/techs/tdrow',['model'=>$tech]);
}*/