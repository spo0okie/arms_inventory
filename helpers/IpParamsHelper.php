<?php

namespace app\helpers;

/**
 * Сетевые параметры типов доступа и записей доступа: «TCP 443», «UDP 5060,20000-20100»,
 * «TCP,UDP 53», несколько правил — через «;» или с новой строки.
 *
 * Параметры везде хранятся свободным текстом (ip_params у записи, ip_params_def у типа,
 * переопределение у сервиса) — здесь их разбор и сравнение «протокол + пересечение портов».
 * Нераспознанный текст правил не даёт (сравнение по нему невозможно, а не «совпадает со всем»).
 */
class IpParamsHelper
{
	/**
	 * Текст → правила.
	 * Протокол без портов («GRE», «ICMP») — все порты протокола; порты без протокола
	 * («443») — любой протокол.
	 * @param string|null $text
	 * @param string[] $defaultProtocols протоколы для правила без своих (назначение проброса
	 *                                    «8443» наследует протокол входа)
	 * @return array [['protocols'=>string[] (пусто = любой), 'ports'=>[[from,to],...] (пусто = все)], ...]
	 */
	public static function parse(?string $text, array $defaultProtocols=[]): array
	{
		$rules=[];
		foreach (preg_split('/[;\n]+/',(string)$text) as $chunk) {
			$chunk=trim($chunk);
			if ($chunk==='') continue;
			if (!preg_match('/^([A-Za-z][A-Za-z\s,\/]*?)?\s*([\d\s,\-]*)$/',$chunk,$m)) continue;
			$protocols=array_values(array_filter(array_map(
				fn($p)=>strtoupper(trim($p)),
				preg_split('/[\s,\/]+/',(string)($m[1]??''))
			)));
			$ports=[];
			foreach (preg_split('/\s*,\s*/',trim((string)($m[2]??''))) as $range) {
				if ($range==='') continue;
				if (preg_match('/^(\d+)\s*-\s*(\d+)$/',$range,$r)) $ports[]=[(int)$r[1],(int)$r[2]];
				elseif (ctype_digit($range)) $ports[]=[(int)$range,(int)$range];
			}
			if (!count($protocols) && !count($ports)) continue;
			if (!count($protocols)) $protocols=array_map('strtoupper',$defaultProtocols);
			$rules[]=['protocols'=>$protocols,'ports'=>$ports];
		}
		return $rules;
	}

	/**
	 * Пересекаются ли два набора правил: есть пара правил с общим протоколом
	 * (или хотя бы одно без протокола) и общими портами (или хотя бы одно без портов).
	 * @param array $a правила parse()
	 * @param array $b правила parse()
	 * @return bool
	 */
	public static function overlap(array $a, array $b): bool
	{
		foreach ($a as $ra) foreach ($b as $rb) {
			if (count($ra['protocols']) && count($rb['protocols'])
				&& !count(array_intersect($ra['protocols'],$rb['protocols']))) continue;
			if (!count($ra['ports']) || !count($rb['ports'])) return true;
			foreach ($ra['ports'] as [$fromA,$toA]) foreach ($rb['ports'] as [$fromB,$toB])
				if ($fromA<=$toB && $fromB<=$toA) return true;
		}
		return false;
	}
}
