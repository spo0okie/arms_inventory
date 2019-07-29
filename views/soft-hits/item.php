<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 04.09.2018
 * Time: 13:52
 * @var array $items
 */

foreach ($items as $item) {
	echo "${item['string']} => ${item['mask']}\n";
}