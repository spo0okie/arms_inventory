<?php

namespace tests\unit\helpers;

use app\helpers\IpParamsHelper;
use Codeception\Test\Unit;

/**
 * Разбор и сравнение сетевых параметров доступов ({@see IpParamsHelper}):
 * «TCP 443», «UDP 5060,20000-20100», «TCP,UDP 53», правила через «;» / перевод строки.
 */
class IpParamsHelperTest extends Unit
{
	public function testParse()
	{
		$this->assertSame([['protocols'=>['TCP'],'ports'=>[[443,443]]]], IpParamsHelper::parse('TCP 443'));
		$this->assertSame([['protocols'=>['UDP'],'ports'=>[[5060,5060],[20000,20100]]]],
			IpParamsHelper::parse('UDP 5060,20000-20100'));
		$this->assertSame([['protocols'=>['TCP','UDP'],'ports'=>[[53,53]]]], IpParamsHelper::parse('tcp,udp 53'),
			'несколько протоколов, регистр не важен');
		$this->assertSame([['protocols'=>['GRE'],'ports'=>[]]], IpParamsHelper::parse('GRE'), 'протокол без портов');
		$this->assertSame([['protocols'=>[],'ports'=>[[443,443]]]], IpParamsHelper::parse('443'), 'порты без протокола');
		$this->assertSame([['protocols'=>['TCP'],'ports'=>[[8443,8443]]]], IpParamsHelper::parse('8443',['TCP']),
			'назначение проброса наследует протокол входа');
		$this->assertCount(2, IpParamsHelper::parse("TCP 80;TCP 443"), 'правила через «;»');
		$this->assertCount(2, IpParamsHelper::parse("TCP 80\nUDP 53"), 'правила с новой строки');
		$this->assertSame([], IpParamsHelper::parse(''));
		$this->assertSame([], IpParamsHelper::parse('как в описании сервиса'), 'нераспознанный текст правил не даёт');
	}

	public function testOverlap()
	{
		$p=fn($t)=>IpParamsHelper::parse($t);
		$this->assertTrue(IpParamsHelper::overlap($p('TCP 443'),$p('TCP 443')));
		$this->assertTrue(IpParamsHelper::overlap($p('TCP 8000-8100'),$p('TCP 8080')), 'порт внутри диапазона');
		$this->assertTrue(IpParamsHelper::overlap($p('TCP 80;TCP 443'),$p('TCP 443')), 'хотя бы одно правило');
		$this->assertTrue(IpParamsHelper::overlap($p('TCP,UDP 53'),$p('UDP 53')));
		$this->assertTrue(IpParamsHelper::overlap($p('443'),$p('TCP 443')), 'правило без протокола — любой протокол');
		$this->assertTrue(IpParamsHelper::overlap($p('GRE'),$p('GRE')), 'правило без портов — все порты');
		$this->assertFalse(IpParamsHelper::overlap($p('TCP 443'),$p('TCP 8443')), 'другой порт');
		$this->assertFalse(IpParamsHelper::overlap($p('UDP 443'),$p('TCP 443')), 'другой протокол');
		$this->assertFalse(IpParamsHelper::overlap([],$p('TCP 443')), 'пустые правила ни с чем не совпадают');
	}
}
