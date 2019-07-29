<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 05.03.2018
 * Time: 22:31
 * Рендер списка програмных продуктов
 */
?>
<table>
    <tr>
    <th>ОC</th>
    <th>Производитель</th>
    <th>Название ПО</th>
    <th class="passport_tools"></th>
    </tr>
    <?php foreach ($items as $item) echo $item ?>
</table>