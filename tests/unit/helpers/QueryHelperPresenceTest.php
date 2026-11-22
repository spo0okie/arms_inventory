<?php

namespace tests\unit\helpers;

use app\helpers\QueryHelper;
use Codeception\Test\Unit;
use yii\db\Connection;
use yii\db\Query;
use yii\db\QueryBuilder;

/** Проверяет поиск по наличию даты и числа через Yii2. */
class QueryHelperPresenceTest extends Unit
{
	/** Проверяет SQL и параметры для подстановок, обычных значений и составных фильтров. */
	public function testNumberAndDatePresence(): void
	{
		$builder = new QueryBuilder(new Connection(['dsn' => 'mysql:dbname=arms_test']));
		$select = 'SELECT * FROM `samples`';

		foreach ([
			['amount', '*', ' WHERE `amount` IS NOT NULL', []],
			['amount', '-', ' WHERE `amount` IS NULL', []],
			['amount', '0', ' WHERE `amount` LIKE :qp0', [':qp0' => '%0%']],
			['amount', '-5', ' WHERE `amount` LIKE :qp0', [':qp0' => '%-5%']],
			['amount', '* & <0', ' WHERE (`amount` IS NOT NULL) AND (`amount` < :qp0)', [':qp0' => '0']],
			['expires_at', ' - ', ' WHERE `expires_at` IS NULL', []],
			['expires_at', '- | <2026-02-01', ' WHERE (`expires_at` IS NULL) OR (`expires_at` < :qp0)',
				[':qp0' => '2026-02-01']],
			['expires_at', null, '', []],
		] as [$column, $filter, $where, $params]) {
			// andFilterWhere() должен сохранить SQL NULL при разборе подстановок.
			$query = (new Query())->from('samples')
				->andFilterWhere(QueryHelper::querySearchNumberOrDate($column, $filter));
			$this->assertSame([$select.$where, $params], $builder->build($query), "$column: $filter");
		}
	}
}
