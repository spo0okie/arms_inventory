<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use app\models\NetIps;
use yii\helpers\Html;
use yii\web\View;

class IpNetType extends IpType
{
	public static function name(): string
	{
		return 'ipNet';
	}

	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		// Детерминированная генерация
		mt_srand($context->seed());
		
		return $this->generateSubnetAddr();
	}
	
	protected function generateSubnetAddr(): string {
		$mask=mt_rand(8, 30);
		
		return $this->generatePrivateIP().'/'.$mask;
	}

}