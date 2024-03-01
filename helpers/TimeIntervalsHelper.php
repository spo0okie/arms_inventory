<?php

namespace app\helpers;


class TimeIntervalsHelper {
	
	// МАТЕМАТИКА ИНТЕРВАЛОВ //
	/*public static function interval2Schedule($interval)
	{
		return date('H:i',$interval[0]).'-'.date('H:i',$interval[1]);
	}
	
	public static function schedule2Interval($schedule,$date)
	{
		//var_dump($schedule);
		$tokens=explode('-',$schedule);
		return [
			strtotime($date.' '.$tokens[0]),
			strtotime($date.' '.$tokens[1]),
		];
		
	}*/
	
	/**
	 * Приводим интервал вида [22:00-06:00] к математически корректному [22:00-30:00]
	 * чтобы с ним можно было проводить все математические манипуляции
	 * @param array $interval
	 * @return array
	 */
	public static function dayMinutesOverheadFix(array $interval) {
		if ($interval[1]<$interval[0]) $interval[1]+=1440;
		return $interval;
	}
	// то же самое но для массива интервалов
	public static function dayMinutesOverheadFixAll(array $intervals) {
		foreach ($intervals as $i=>$interval) {
			$intervals[$i]=static::dayMinutesOverheadFix($interval);
		}
		return $intervals;
	}
		
	/**
	 * Возвращаем математически корректный [22:00-30:00] к приятному человеку виду [22:00-06:00]
	 * но с которым нельзя корректно выполнять мат. операции
	 * @param array $interval
	 * @return array
	 */
	public static function dayMinutesOverheadHumanize(array $interval) {
		if ($interval[1]>1440) $interval[1]-=1440;
		return $interval;
	}
	// то же самое но для массива интервалов
	public static function dayMinutesOverheadHumanizeAll(array $intervals) {
		foreach ($intervals as $i=>$interval) {
			$intervals[$i]=static::dayMinutesOverheadHumanize($interval);
		}
		return $intervals;
	}
	
	/**
	 * обрезает границы интервала $interval так, чтобы они не выходили за рамки $range
	 * @param $interval array
	 * @param $range array
	 * @return array
	 */
	public static function intervalCut(array $interval, array $range)
	{
		//граница NULL означает что с этого края интервал открыт
		if (
			$interval[0]<$range[0]
			||
			is_null($interval[0])
		) $interval[0]=$range[0];
		if (
			$interval[1]>$range[1]
			||
			is_null($interval[1])
		) $interval[1]=$range[1];
		return $interval;
	}
	
	/**
	 * проверка попадания в интервал
	 * @param $interval array|false
	 * @param $value integer
	 * @return bool
	 */
	public static function intervalCheck($interval,int $value)
	{
		if ($interval===false) return false;
		return $interval[0]<=$value && $interval[1]>=$value;
	}
	
	/**
	 * проверка пересечения интервалов
	 * @param $interval1 array
	 * @param $interval2 array
	 * @return bool
	 */
	public static function intervalIntersect(array $interval1, array $interval2)
	{
		// сортируем интервалы так, чтобы второй был не раньше первого
		if ((is_null($interval2[0]) && !is_null($interval1[0])) || $interval2[0]<$interval1[0]) {
			$tmp=$interval1;
			$interval1=$interval2;
			$interval2=$tmp;
		}
		/*error_log('['.
		(is_null($interval1[0])?'null':$interval1[0]).','.
		(is_null($interval1[1])?'null':$interval1[1]).']<['.
		(is_null($interval2[0])?'null':$interval2[0]).','.
		(is_null($interval2[1])?'null':$interval2[1]).
		']');*/
		return
			is_null($interval2[0]) //у более позднего отрезка нет начала
			||
			is_null($interval1[1]) //у более раннего отрезка нет конца
			||
			(is_null($interval1[0]) && $interval1[1]>=$interval2[0]) //луч без начала кончается позже начала второго отрезка
			||
			(is_null($interval2[1]) && $interval2[0]<=$interval1[1]) //луч без конца начинается раньше конца второго отрезка
			||
			$interval2[0]<=$interval1[1]
			;
	}
	
	/**
	 * Сравнивает интервалы
	 * сначала сравнивает какой раньше начинается, если начинаются одновременно, сравнивает какой раньше заканчивается
	 * @param $interval1
	 * @param $interval2
	 * @return int 1 - первый позже, -1 - первый раньше, 0 - начинаются одновременно
	 */
	public static function intervalsCompare($interval1,$interval2)
	{
		//сначала сравниваем по левому краю
		if ($interval1[0]===$interval2[0]) {
			//потом по правому
			if ($interval1[1]===$interval2[1]) {
				return 0;
			}
			if (is_null($interval1[1])) //первый интервал - луч вправо
				return 1;	//первый позже заканчивается
			if (is_null($interval2[1])) //второй интервал - луч вправо
				return -1;	//первый раньше заканчивается (второй позже заканчивается, т.к. это луч)
			return ($interval1[1] > $interval2[1])?1:-1;
		}
		if (is_null($interval1[0])) //первый интервал - луч влево
			return -1;	//первый раньше начинается
		if (is_null($interval2[1])) //второй интервал - луч влево
			return 1;	//первый позже начинается
		return ($interval1[0] > $interval2[0])?1:-1;
	}
	
	public static function intervalsSort(&$intervals)
	{
		usort($intervals,[static::class,'intervalsCompare']);
	}
	
	/**
	 * Вычитание 2x интервалов
	 * @param $A array Уменьшаемое
	 * @param $B array Вычитаемое
	 * @return array[]
	 */
	public static function intervalSubtraction(array $A, array $B)
	{
		$meta=isset($A['meta'])?$A['meta']:false;
		if ($B[0]<=$A[0]) {
			//если вычитаемое начинается раньше
			if ($B[1]>=$A[1]) {
				//и вычитаемое заканчивается позже, то оно уничтожает уменьшаемое
				return [];
			} elseif ($B[1]<=$A[0]) {
				//если вычитаемое заканчивается раньше, то оно не пересекается и не трогает уменьшаемое
				return $A;
			} else {
				//если начинается раньше начала А и заканчивается раньше конца А, то
				//возвращаем кусок от конца вычитаемого до конца уменьшаемого (остальное вычтено)
				return [
					[$B[1],$A[1],'meta'=>$meta]
				];
			}
		} else {
			//вычитаемое В начинается позже начала уменьшаемого А
			if ($B[0]>=$A[1]) {
				//если вычитаемое начинается после уменьшаемого, то они не пересекаются. А не тронуто
				return $A;
			} elseif ($B[1]>=$A[1]) {
				//вычитаемое начинается внутри уменьшаемого заканчивается позже (откусывает правый кусок)
				return [
					[$A[0],$B[0],'meta'=>$meta]
				];
			} else {
				//В находится внутри А и режет его на кусочки
				return [
					[$A[0],$B[0],'meta'=>$meta],
					[$B[1],$A[1],'meta'=>$meta]
				];
			}
		}
	}
	
	/**
	 * Вычитает интервалы (массив из массива)
	 * @param $minuend array уменьшаемые
	 * @param $subtrahend array вычитаемые
	 * @return array[]
	 */
	public static function intervalsSubtraction(array $minuend, array $subtrahend)
	{
		//выкусываем из всех уменьшаемых вычитаемые по одному
		
		//перебираем вычитаемые (они не меняются в результате операций)
		foreach ($subtrahend as $sub) {
			$difference=[]; //сюда складываем результат
			//перебираем уменьшаемые (а вот они после каждого вычитания меняются)
			foreach ($minuend as $min) {
				$difference=array_merge($difference,static::intervalSubtraction($min,$sub));
			}
			//меняем исходное уменьшаемое после очередной итерации вычитания
			$minuend=$difference;
		}
		return $minuend;
		
	}
	
	/**
	 * склеивает все пересекающиеся интервалы в массиве
	 * @param $intervals array[]
	 * @return array
	 */
	public static function intervalMerge(array $intervals)
	{
		do {
			$intersect=false; //сначала мы не знаем ни о каких пересечениях
			if (count($intervals)>1) { //если интервалов больше 1
				for ($i=0;$i<count($intervals)-1;$i++) { //сравниваем все интервалы по очереди
					for ($j=$i+1;$j<count($intervals);$j++) {
						if (static::intervalIntersect($intervals[$i],$intervals[$j])) { //если они пересекаются,то
							$intersect=true;
							
							//интервал пересечение;
							$merged=[
								min($intervals[$i][0],$intervals[$j][0]),
								max($intervals[$i][1],$intervals[$j][1]),
							];
							if (isset($intervals[$j]['meta'])) $merged['meta']=$intervals[$j]['meta'];
							//убираем исходные интервалы
							unset($intervals[$i]);
							unset($intervals[$j]);
							
							//добавляем сумму пересечения
							$intervals[]=$merged;
							
							//сбрасываем индексы массива интервалов (reindex)
							$intervals=array_values($intervals);
							
							break 2; //выходим из 2х вложенных for
						}
					}
				}
			}
		} while ($intersect);
		return $intervals;
	}
	
	/**
	 * укладывает "плиточкой" интервалы: те что пересекаются обрезаются по началу следующего интервала
	 * и укладываются стык в стык с сохранением метаданных
	 * @param $intervals array[]
	 * @return array
	 */
	public static function intervalTile(array $intervals)
	{
		do {
			static::intervalsSort($intervals);
			$intersect=false; //сначала мы не знаем ни о каких пересечениях
			if (count($intervals)>1) { //если интервалов больше 1
				for ($i=0;$i<count($intervals)-1;$i++) { //сравниваем все интервалы по очереди

					//если мы его грохнули в о вложенном цикле на предыдущем шаге
					if (!isset($intervals[$i])) continue;

					//если этот интервал вырожденный (длина 0)
					if ($intervals[$i][0]===$intervals[$i][1]) {
						unset($intervals[$i]); //убираем его
						continue; //переходим к следующему
					}

					for ($j=$i+1;$j<count($intervals);$j++) { //со всеми остальными
						
						//если этот интервал вырожденный (длина 0)
						if ($intervals[$j][0]===$intervals[$j][1]) {
							unset($intervals[$j]); //убираем его
							continue; //переходим к следующему
						}

						if (static::intervalIntersect($intervals[$i],$intervals[$j])) { //если они пересекаются,то
							$intersect=true;
							//поскольку они отсортированы, то $i должен быть раньше чем $j либо они одинаковые
							//в любом случае левая граница $i <= $j
							
							//если интервалы идентичны
							if (static::intervalsCompare($intervals[$i],$intervals[$j])==0) {
								unset($intervals[$i]);	//просто удаляем один из них (по идее первый, но они же идентичны. так что хз)
								$intervals=array_values($intervals);	//сбрасываем индексы массива интервалов (reindex)
								break 2; //выходим из 2х вложенных for
							}
							
							//если второй интервал - луч влево (когда оба лучи влево, но второй заканчивается позже)
							if (is_null($intervals[$j][0])) {
								//второй интервал начинаем сразу после конца первого
								$intervals[$j][0]=$intervals[$i][1]+1;
							} else {
								//первый интервал заканчиваем сразу перед вторым
								$intervals[$i][1]=$intervals[$j][0]-1;
							}
						}
					}
				}
			}
		} while ($intersect);
		return $intervals;
	}
	
}