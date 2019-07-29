<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 12.03.2018
 * Time: 8:19
 * @var array $items
 *
 * рисует список софта в 2 колонки
 */

//количество в первую колонку
$left_count=round(count($items)/2);
$right_count=count($items)-$left_count;
$left=array_slice($items,0,$left_count);
$right=($right_count)?array_slice($items,$left_count):null;

?>

<div class="row">
    <div class="col-xs-6"><?= $this->render('list',['items'=>$left]) ?></div>
    <div class="col-xs-6"><?= ($right_count)?($this->render('list',['items'=>$right])):'' ?></div>
</div>

