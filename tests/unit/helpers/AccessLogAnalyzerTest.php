<?php

namespace tests\unit\helpers;

use app\helpers\AccessLogAnalyzer;
use Codeception\Test\Unit;

/**
 * Тесты разбора и агрегации access-лога ({@see AccessLogAnalyzer},
 * docs/dev/perf-monitoring.md).
 *
 * Формат combined_time (combined + "%Dus" в конце строки) — контракт
 * с docker/apache/site.conf: изменение формата должно ломать эти тесты.
 */
class AccessLogAnalyzerTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** строка нового формата combined_time разбирается целиком */
	public function testParseCombinedTime()
	{
		$entry = AccessLogAnalyzer::parseLine(
			'10.0.0.5 - ivanov [16/Aug/2026:14:32:05 +0500] "GET /services/view?id=5 HTTP/1.1" 200 12345 "https://arms/services" "Mozilla/5.0 (X11; Linux)" 1234567us'
		);
		$this->assertNotNull($entry);
		$this->assertEquals('10.0.0.5', $entry['ip']);
		$this->assertEquals('ivanov', $entry['user']);
		$this->assertEquals(strtotime('2026-08-16 14:32:05 +0500'), $entry['timestamp']);
		$this->assertEquals('14:32:05', $entry['timeLocal']); //время "как в логе", без пересчёта TZ
		$this->assertEquals('GET', $entry['method']);
		$this->assertEquals('/services/view?id=5', $entry['path']);
		$this->assertEquals(200, $entry['status']);
		$this->assertEqualsWithDelta(1234.567, $entry['durationMs'], 0.001);
	}

	/** старый combined (без %Dus) учитывается, но без тайминга */
	public function testParseOldCombinedWithoutDuration()
	{
		$entry = AccessLogAnalyzer::parseLine(
			'10.0.0.5 - - [16/Aug/2026:14:32:05 +0500] "GET /site/index HTTP/1.1" 200 512 "-" "Mozilla/5.0"'
		);
		$this->assertNotNull($entry);
		$this->assertNull($entry['durationMs']);
		$this->assertEquals(200, $entry['status']);
	}

	/** мусор и чужие форматы не роняют разбор, а возвращают null */
	public function testParseGarbage()
	{
		$this->assertNull(AccessLogAnalyzer::parseLine(''));
		$this->assertNull(AccessLogAnalyzer::parseLine('какая-то не та строка'));
		//error-лог апача - не access-формат
		$this->assertNull(AccessLogAnalyzer::parseLine(
			'[Sun Aug 16 14:32:05.123456 2026] [php:error] [pid 123] PHP Fatal error: ...'
		));
	}

	/** оборванный запрос ("-" вместо "%r") не считается битой строкой */
	public function testParseBrokenRequest()
	{
		$entry = AccessLogAnalyzer::parseLine(
			'10.0.0.5 - - [16/Aug/2026:14:32:05 +0500] "-" 408 - "-" "-" 30000000us'
		);
		$this->assertNotNull($entry);
		$this->assertEquals('-', $entry['method']);
		$this->assertEquals(408, $entry['status']);
	}

	/** нормализация: query отрезается, числовые сегменты -> {id}, метод входит в ключ */
	public function testNormalizeRoute()
	{
		$this->assertEquals('GET /services/view',
			AccessLogAnalyzer::normalizeRoute('GET', '/services/view?id=5&sort=name'));
		$this->assertEquals('PUT /api/techs/{id}',
			AccessLogAnalyzer::normalizeRoute('PUT', '/api/techs/123'));
		$this->assertEquals('GET /schedules/{id}/edit',
			AccessLogAnalyzer::normalizeRoute('GET', '/schedules/45/edit'));
		//нечисловые сегменты не трогаются
		$this->assertEquals('GET /api/comps/domain/host-01',
			AccessLogAnalyzer::normalizeRoute('GET', '/api/comps/domain/host-01'));
	}

	/** перцентиль методом ближайшего ранга */
	public function testPercentile()
	{
		$sorted = range(1, 100);
		$this->assertEquals(50, AccessLogAnalyzer::percentile($sorted, 50));
		$this->assertEquals(95, AccessLogAnalyzer::percentile($sorted, 95));
		$this->assertEquals(99, AccessLogAnalyzer::percentile($sorted, 99));
		$this->assertEquals(100, AccessLogAnalyzer::percentile($sorted, 100));
		$this->assertEquals(42, AccessLogAnalyzer::percentile([42], 95));
	}

	/** строка лога с заданными параметрами */
	protected static function line(string $time, string $request, int $status, ?int $durationMs): string
	{
		$suffix = $durationMs === null ? '' : ' ' . ($durationMs * 1000) . 'us';
		return "10.0.0.5 - - [$time] \"$request\" $status 100 \"-\" \"agent\"$suffix";
	}

	/** агрегация: счётчики, суммы, списки таймаутов и 5xx, фильтр периода */
	public function testAggregation()
	{
		$analyzer = new AccessLogAnalyzer();
		$analyzer->slowSeconds = 29.0;
		$from = strtotime('2026-08-16 00:00:00 +0500');
		$to = $from + 86400;

		//3 запроса одного маршрута с разными id + один 500 + один таймаут + чужой день + мусор
		$analyzer->consumeLine(static::line('16/Aug/2026:10:00:00 +0500', 'GET /services/view?id=1 HTTP/1.1', 200, 100), $from, $to);
		$analyzer->consumeLine(static::line('16/Aug/2026:10:00:01 +0500', 'GET /services/view?id=2 HTTP/1.1', 200, 200), $from, $to);
		$analyzer->consumeLine(static::line('16/Aug/2026:10:00:02 +0500', 'GET /services/view?id=3 HTTP/1.1', 200, 300), $from, $to);
		$analyzer->consumeLine(static::line('16/Aug/2026:10:00:03 +0500', 'GET /api/techs/55 HTTP/1.1', 500, 50), $from, $to);
		$analyzer->consumeLine(static::line('16/Aug/2026:10:00:04 +0500', 'GET /soft/index HTTP/1.1', 200, 30000), $from, $to);
		$analyzer->consumeLine(static::line('15/Aug/2026:10:00:00 +0500', 'GET /services/view?id=1 HTTP/1.1', 200, 100), $from, $to);
		$analyzer->consumeLine('мусорная строка', $from, $to);

		$totals = $analyzer->getTotals();
		$this->assertEquals(7, $totals['lines']);
		$this->assertEquals(5, $totals['parsed']);
		$this->assertEquals(1, $totals['filtered']);
		$this->assertEquals(1, $totals['skipped']);
		$this->assertEquals(['2xx' => 4, '5xx' => 1], $totals['statuses']);

		$stats = $analyzer->routeStats();
		$view = $stats['GET /services/view'];
		$this->assertEquals(3, $view['count']);
		$this->assertEquals(600, $view['sumMs']);
		$this->assertEquals(200, $view['avgMs']);
		$this->assertEquals(200, $view['p50']);
		$this->assertEquals(300, $view['p95']);
		$this->assertEquals(300, $view['maxMs']);
		$this->assertEquals(0, $view['count5xx']);

		$this->assertEquals(1, $stats['GET /api/techs/{id}']['count5xx']);

		//таймауты и 5xx попали в свои списки
		$this->assertEquals(1, $analyzer->getSlowTotal());
		$this->assertEquals('GET /soft/index', $analyzer->getSlowRequests()[0]['route']);
		$this->assertEquals(1, $analyzer->getErrorsTotal());
		$this->assertEquals(500, $analyzer->getErrorRequests()[0]['status']);
	}

	/** строки без тайминга учитываются в счётчиках, но не в перцентилях */
	public function testNoDurationLines()
	{
		$analyzer = new AccessLogAnalyzer();
		$analyzer->consumeLine(static::line('16/Aug/2026:10:00:00 +0500', 'GET /a HTTP/1.1', 200, null));
		$analyzer->consumeLine(static::line('16/Aug/2026:10:00:01 +0500', 'GET /a HTTP/1.1', 200, 100));

		$this->assertEquals(1, $analyzer->getTotals()['noDuration']);
		$stat = $analyzer->routeStats()['GET /a'];
		$this->assertEquals(2, $stat['count']);
		$this->assertEquals(100, $stat['p50']); //перцентиль только по строке с таймингом
	}

	/** списки slow/5xx ограничены listLimit, но счётчики полные */
	public function testListLimit()
	{
		$analyzer = new AccessLogAnalyzer();
		$analyzer->listLimit = 2;
		for ($i = 0; $i < 5; $i++)
			$analyzer->consumeLine(static::line('16/Aug/2026:10:00:0' . $i . ' +0500', 'GET /err HTTP/1.1', 500, 10));
		$this->assertEquals(5, $analyzer->getErrorsTotal());
		$this->assertCount(2, $analyzer->getErrorRequests());
	}

	/** consumeFile читает обычный файл и .gz одинаково */
	public function testConsumeFile()
	{
		$content = static::line('16/Aug/2026:10:00:00 +0500', 'GET /a HTTP/1.1', 200, 100) . "\n"
			. static::line('16/Aug/2026:10:00:01 +0500', 'GET /b HTTP/1.1', 200, 200) . "\n";

		$plain = tempnam(sys_get_temp_dir(), 'alt');
		file_put_contents($plain, $content);
		$gz = $plain . '.gz';
		file_put_contents($gz, gzencode($content));

		try {
			$analyzer = new AccessLogAnalyzer();
			$this->assertTrue($analyzer->consumeFile($plain));
			$this->assertTrue($analyzer->consumeFile($gz));
			$this->assertFalse($analyzer->consumeFile($plain . '.nonexistent'));
			//оба файла дали одинаковый набор строк
			$this->assertEquals(4, $analyzer->getTotals()['parsed']);
			$this->assertEquals(2, $analyzer->routeStats()['GET /a']['count']);
		} finally {
			unlink($plain);
			unlink($gz);
		}
	}
}
