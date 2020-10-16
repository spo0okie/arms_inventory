<?php
/**
 * Строчка АРМа в карте рабочих мест
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 * @var \app\models\Arms $model
 * @var yii\web\View $this
 * @var string $cabinet_col первая колнка - название помещения
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
		if (!$comp->ignore_hw) $physCount++;
		else $vmCount++;
} else $rowspan='';
$rowspan=(count($comps)>1)?'rowspan="'.count($comps).'"':'';
$rowspanPhys=($physCount>1)?'rowspan="'.$physCount.'"':'';

//может быть передан список столбцов, которые не нужно выводить
if (!isset($skip)) $skip=[];

$sortedComps=[];

//if (iss)

//поехали!
for ($i=0; $i<count($comps); $i++) {
    $comp=$comps[$i]; ?>
    <tr <?= $model->is_server?'class="server"':''?>>
	
		<?php //в самой первой строчке нужно вставить в начале колонку кабинета/помещения.
		// вставить надо только один раз, т.к. у нее rowspan=0 и она идет сквозняком до конца таблицы
		
		if (isset($cabinet_col)) {
			echo $cabinet_col;
			unset ($cabinet_col);
		}?>
		
		
		<?php if (!$i) { ?>
            <td class="arm_id" <?= $rowspan ?>><?= $this->render('/arms/item',['model'=>$model]) ?></td>
	    <?php } ?>
	    
	    <?php //если у нас есть ОС, то зададим ячейке класс свежести данных об этой ОС
	    	$age_class=is_object($comp)?$comp->updatedRenderClass:'';
	    ?>
        <td class="arm_hostname <?= $age_class ?>"><?= is_object($comp)?$this->render('/comps/item',['model'=>$comp]):$model->hostname ?></td>

		
        <?php if ($model->is_server) {
        	$services=[];
			if (isset($comp->user))
				$services[]='Польз.:'.$this->render('/users/item',['model'=>$comp->user]);
			if (isset($comp->services))
				foreach ($comp->services as $svc)
					$services[]=$this->render('/services/item',['model'=>$svc]);
		?>
            <td colspan="2" class="arm_services"><?= implode('<br />',$services); ?></td>
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

        <?php /*if (!$i) { ?>
            <td class="item_sn"<?= $rowspan ?>><?= $model->sn ?></td>

	    <?php }*/ ?>
	    <?php if (!$i) {
	    	$ttip="Серийный номер: ".($model->sn?$model->sn:' отсутствует '). '<br />'.
				"Инвентарный номер (бухг.):".($model->inv_num?$model->inv_num:' отсутствует ');
	    	$tokens=[];
	    	if (strlen($model->sn)) $tokens[]=$model->sn;
	    	if (strlen($model->inv_num)) $tokens[]=$model->inv_num;
	    	?>
            <td class="item_invnum"<?= $rowspan ?>>
				<?php if (count($tokens)) { ?>
					<span title="<?= $ttip ?>">
						<?= implode(', ',$tokens) ?>
					</span>
				<?php } ?>
			</td>

	    <?php }?>
    </tr>

<?php }