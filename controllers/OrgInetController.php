<?php

namespace app\controllers;

use app\models\OrgInet;

/**
 * OrgInetController implements the CRUD actions for OrgInet model.
 */
class OrgInetController extends ArmsBaseController
{
	public function testView(): array
	{
		return self::skipScenario('default', 'requires external integration context');
	}
	
	public function testTtip(): array
	{
		return self::skipScenario('default', 'requires external integration context');
	}
	
	public $modelClass=OrgInet::class;
}
