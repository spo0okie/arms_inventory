<?php

namespace app\console\commands;

use app\models\Comps;
use app\models\SoftHits;
use app\models\SwList;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Выгрузка нераспознанных элементов отпечатков софта.
 *
 * Итерация 0 плана plans/soft-components-hub.md: каждый отпечаток (Comps.raw_soft)
 * прогоняется через штатное распознавание (SwList::loadRaw), остатки — карточки,
 * не совпавшие ни с одним продуктом, — агрегируются по (издатель, имя) и
 * выгружаются в файл. Маски из файла params['soft.export_filter'] отсеивают
 * чувствительное (собственная разработка и т.п.).
 *
 * Использование:
 *   yii soft-raw/export <файл>            выгрузка (формат по расширению: .csv или json)
 *   yii soft-raw/export <файл> --withComps=1   с примерами имён хостов (только для
 *                                              локального анализа, на хаб не отправлять)
 *   yii soft-raw/export <файл> --filter=<маски.txt>   маски не из конфига, а из файла —
 *                                                     для итеративного подбора
 */
class SoftRawController extends Controller
{
	/** @var bool включить в выгрузку примеры имён хостов */
	public $withComps=false;

	/** @var string файл масок фильтра; перекрывает params['soft.export_filter'] —
	 * удобно для подбора масок без правки конфига */
	public $filter='';

	/** @var int сколько хостов-примеров хранить на карточку (при --withComps) */
	public $maxHosts=5;

	/** @var bool пропускать архивированные ОС */
	public $skipArchived=false;

	/** @var int размер сводки по издателям в консоли */
	public $topPublishers=30;

	public function options($actionID)
	{
		return array_merge(parent::options($actionID), [
			'withComps','maxHosts','skipArchived','topPublishers','filter'
		]);
	}

	/**
	 * Загружает маски фильтра из файла params['soft.export_filter'].
	 * Формат: по regexp-маске на строку (семантика та же, что у выражений продуктов —
	 * SoftHits::compare, без учёта регистра); пустые строки и строки с # — пропускаются.
	 * @return string[]
	 * @throws \yii\console\Exception
	 */
	protected function loadFilterMasks()
	{
		$path=strlen($this->filter)?$this->filter:(Yii::$app->params['soft.export_filter']??'');
		if (!strlen($path)) return [];
		if (!is_file($path))
			throw new \yii\console\Exception("Файл фильтра не найден: $path");
		$masks=[];
		foreach (file($path) as $line) {
			$line=trim($line);
			if (!strlen($line) || strpos($line,'#')===0) continue;
			$masks[]=$line;
		}
		return $masks;
	}

	/**
	 * Попадает ли карточка (издатель/имя) под какую-либо из масок фильтра
	 * @param string[] $masks
	 */
	protected function isFiltered(array $masks, string $publisher, string $name)
	{
		foreach ($masks as $mask)
			if (SoftHits::compare($mask,$publisher) || SoftHits::compare($mask,$name))
				return true;
		return false;
	}

	/**
	 * Выгрузить нераспознанные элементы отпечатков софта в файл (json или csv по расширению)
	 * @param string $file путь к файлу выгрузки
	 * @return int
	 * @throws \yii\console\Exception
	 */
	public function actionExport(string $file)
	{
		$masks=$this->loadFilterMasks();
		echo "Фильтр: ".(count($masks)?count($masks).' масок':'не настроен (soft.export_filter)')."\n";

		$query=Comps::find()
			->select(['id','name','domain_id','raw_soft','archived'])
			->where(['not',['raw_soft'=>null]])
			->andWhere(['not',['raw_soft'=>'']]);
		if ($this->skipArchived) $query->andWhere(['not',['archived'=>1]]);

		$scanned=0; $unparsed=0;
		$cards=[];	//агрегат нераспознанных карточек: "издатель\0имя" => [publisher,name,comps,hosts]

		foreach ($query->batch(100) as $comps) {
			/** @var Comps[] $comps */
			foreach ($comps as $comp) {
				$scanned++;
				if (!($scanned%500)) echo "... $scanned ОС\n";

				$swList=new SwList();
				if (!$swList->loadRaw($comp->raw_soft)) {
					$unparsed++;	//пустой или некорректный JSON отпечатка
					continue;
				}

				//после loadRaw в data остались только карточки, не совпавшие ни с одним продуктом
				foreach ($swList->data as $card) {
					$name=trim($card['name']??'');
					if (!strlen($name)) continue;
					$publisher=trim($card['publisher']??'');

					$key=$publisher."\0".$name;
					if (!isset($cards[$key])) $cards[$key]=[
						'publisher'=>$publisher,
						'name'=>$name,
						'comps'=>0,
						'hosts'=>[],
					];
					$cards[$key]['comps']++;
					if ($this->withComps && count($cards[$key]['hosts'])<$this->maxHosts)
						$cards[$key]['hosts'][]=$comp->name;
				}
			}
		}

		//классификация по фильтру
		$passed=[]; $filtered=[];
		foreach ($cards as $card) {
			if (!$this->withComps) unset($card['hosts']);
			if ($this->isFiltered($masks,$card['publisher'],$card['name']))
				$filtered[]=$card;
			else
				$passed[]=$card;
		}
		unset($cards);

		//сортировка: самое частое сверху
		$byFreq=function($a,$b){
			if ($a['comps']==$b['comps']) return strcasecmp($a['name'],$b['name']);
			return $b['comps']-$a['comps'];
		};
		usort($passed,$byFreq);
		usort($filtered,$byFreq);

		$this->writeFile($file,$masks,$scanned,$unparsed,$passed,$filtered);

		//сводка в консоль
		echo "\nОС с отпечатком софта: $scanned (не разобрано: $unparsed)\n";
		echo "Нераспознанных карточек (уникальных): ".(count($passed)+count($filtered))
			." = прошло фильтр: ".count($passed)." + отсеяно: ".count($filtered)."\n";
		$this->printPublishersSummary($passed,$filtered);
		echo "\nВыгрузка записана: $file\n";
		if ($this->withComps)
			echo "Внимание: выгрузка содержит имена хостов (--withComps) — только для локального анализа.\n";

		return ExitCode::OK;
	}

	/**
	 * Сводка по издателям: у кого больше всего нераспознанного — кандидаты
	 * на фильтрацию (или на заведение приватного продукта) видны сразу
	 */
	protected function printPublishersSummary(array $passed, array $filtered)
	{
		$pubs=[];
		foreach (['passed'=>$passed,'filtered'=>$filtered] as $status=>$set) {
			foreach ($set as $card) {
				$p=strlen($card['publisher'])?$card['publisher']:'(без издателя)';
				if (!isset($pubs[$p])) $pubs[$p]=['items'=>0,'comps'=>0,'filtered'=>0];
				$pubs[$p]['items']++;
				$pubs[$p]['comps']+=$card['comps'];
				if ($status=='filtered') $pubs[$p]['filtered']++;
			}
		}
		uasort($pubs,function($a,$b){return $b['comps']-$a['comps'];});

		echo "\nТоп издателей по частоте (карточек / компов / отсеяно):\n";
		$i=0;
		foreach ($pubs as $publisher=>$sum) {
			if (++$i>$this->topPublishers) break;
			$p=mb_strimwidth($publisher,0,50,'…');
			//дополнение пробелами вручную: printf("%-50s") считает байты и ломает колонки на кириллице
			$p.=str_repeat(' ',max(0,50-mb_strwidth($p)));
			printf("%4d. %s %5d / %5d / %d\n",$i,$p,$sum['items'],$sum['comps'],$sum['filtered']);
		}
		if (count($pubs)>$this->topPublishers)
			echo "  ... всего издателей: ".count($pubs)." (--topPublishers=N покажет больше)\n";
	}

	/**
	 * Запись файла выгрузки: csv (расширение .csv) или json (иначе)
	 * @throws \yii\console\Exception
	 */
	protected function writeFile(string $file, array $masks, int $scanned, int $unparsed, array $passed, array $filtered)
	{
		$h=@fopen($file,'w');
		if ($h===false)
			throw new \yii\console\Exception("Не удалось открыть файл на запись: $file");

		if (strtolower(pathinfo($file,PATHINFO_EXTENSION))=='csv') {
			fwrite($h,"\xEF\xBB\xBF");	//BOM, иначе Excel читает UTF-8 кракозябрами
			$header=['status','publisher','name','comps'];
			if ($this->withComps) $header[]='hosts';
			fputcsv($h,$header,';');
			foreach (['passed'=>$passed,'filtered'=>$filtered] as $status=>$set) {
				foreach ($set as $card) {
					$row=[$status,$card['publisher'],$card['name'],$card['comps']];
					if ($this->withComps) $row[]=implode(', ',$card['hosts']??[]);
					fputcsv($h,$row,';');
				}
			}
		} else {
			fwrite($h,json_encode([
				'generated'=>date('c'),
				'comps_scanned'=>$scanned,
				'comps_unparsed'=>$unparsed,
				'with_comps'=>(bool)$this->withComps,
				'filter_masks'=>count($masks),
				'passed'=>$passed,
				'filtered'=>$filtered,
			],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
		}
		fclose($h);
	}
}
