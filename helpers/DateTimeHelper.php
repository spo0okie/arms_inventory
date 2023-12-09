<?php


namespace app\helpers;


class DateTimeHelper
{
	/**
	 * @param string|integer $time
	 * @return false|int
	 */
	public static function unixTime($time) {
		if (!$time)	return strtotime('today');
		if (is_int($time)) return $time;
		return strtotime($time);
	}
	
	/**
	 * Возвращает начало дня
	 * @param int $time
	 * @return false|int
	 */
	public static function dayStart(int $time) {
		return strtotime(date('Y-m-d',$time));
	}
	
	/**
	 * Понедельник той недели, в какую указывает $date
	 * @param null $date
	 * @return false|int
	 */
	public static function weekMonday($date=null) {
		$date=static::dayStart(
			static::unixTime($date)
		);
		$weekDay=date('N',$date);
		$date-=86400*($weekDay-1);
		return $date;
	}
	
	/**
	 * Воскресенье той недели, в какую указывает $date
	 * @param null $date
	 * @return false|int
	 */
	public static function weekSunday($date=null) {
		$date=static::dayStart(
			static::unixTime($date)
		);
		$weekDay=date('N',$date);
		$date+=86400*(7-$weekDay);
		return $date;
	}
}