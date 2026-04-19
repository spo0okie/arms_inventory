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

		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();
		
		return $this->generateSubnetAddr($rng);
	}
	
	protected function generateSubnetAddr(\Random\Randomizer $rng): string {
		// Узкий диапазон масок: во view сетей строится список всех IP
		// (capacity = 2^(32-mask)), поэтому широкие подсети приводят к таймаутам.
		$mask = $rng->getInt(24, 30);

		return $this->generatePrivateIP($rng).'/'.$mask;
	}

}