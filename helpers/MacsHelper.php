<?php


namespace app\helpers;


class MacsHelper
{
	public static function fixList($list) {
		/* убираем посторонние символы из MAC*/
		$macs=[];
		foreach (explode("\n",$list) as $i=>$mac) {
			$fixed=preg_replace('/[^0-9a-f]/', '', mb_strtolower($mac));
			
			if (
				strlen($fixed) //не пустые
				&&
				array_search($fixed,$macs)===false //убираем дубликаты
				&&
				hexdec($fixed)>0	//убираем MAC вида 0000000
			)
				$macs[]=$fixed;
		}
		return implode("\n",$macs);
	}
}