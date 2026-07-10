<?php

namespace tests\unit\models;

use app\models\traits\UsersModelCalcFieldsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для UsersModelCalcFieldsTrait.
 *
 * Используем анонимный класс-заглушку вместо реальной модели Users,
 * чтобы не требовать БД и полного поднятия Yii2.
 */
class UsersModelCalcFieldsTraitTest extends TestCase
{
    /**
     * Создаёт минимальный объект, использующий трейт.
     *
     * @param array $props Свойства, которые нужно установить на объекте.
     * @return object
     */
    private function makeStub(array $props = []): object
    {
        $stub = new class {
            use UsersModelCalcFieldsTrait;

            public $Phone = null;
            public $techs = [];
        };

        foreach ($props as $key => $value) {
            $stub->$key = $value;
        }

        return $stub;
    }

    /**
     * Создаёт заглушку оборудования (Techs).
     */
    private function makeTech(array $props = []): object
    {
        $tech = new class {
            public $isVoipPhone = false;
            public $comment = null;
            public $voipPhones = [];
        };

        foreach ($props as $key => $value) {
            $tech->$key = $value;
        }

        return $tech;
    }

    public function testReturnsPhoneWhenNoTechs(): void
    {
        $stub = $this->makeStub(['Phone' => '1234', 'techs' => []]);
        $this->assertSame('1234', $stub->getEffectivePhone());
    }

    public function testReturnsVoipPhoneCommentFromAttachedTech(): void
    {
        $tech = $this->makeTech(['isVoipPhone' => true, 'comment' => '1100']);
        $stub = $this->makeStub(['Phone' => '1234', 'techs' => [$tech]]);
        $this->assertSame('1100', $stub->getEffectivePhone());
    }

    public function testReturnsVoipPhoneFromArmPeripheral(): void
    {
        $voipPhone = $this->makeTech(['isVoipPhone' => true, 'comment' => '1405']);
        $arm = $this->makeTech(['isVoipPhone' => false, 'voipPhones' => [$voipPhone]]);
        $stub = $this->makeStub(['Phone' => '1234', 'techs' => [$arm]]);
        $this->assertSame('1405', $stub->getEffectivePhone());
    }

    public function testCombinesMultipleVoipNumbers(): void
    {
        $voipPhone = $this->makeTech(['isVoipPhone' => true, 'comment' => '1405']);
        $arm = $this->makeTech(['isVoipPhone' => false, 'voipPhones' => [$voipPhone]]);
        $ownPhone = $this->makeTech(['isVoipPhone' => true, 'comment' => '1100']);
        $stub = $this->makeStub(['Phone' => '1234', 'techs' => [$arm, $ownPhone]]);
        $this->assertSame('1405, 1100', $stub->getEffectivePhone());
    }

    public function testIgnoresNonNumericComment(): void
    {
        $tech = $this->makeTech(['isVoipPhone' => true, 'comment' => 'переговорная']);
        $stub = $this->makeStub(['Phone' => '1234', 'techs' => [$tech]]);
        $this->assertSame('1234', $stub->getEffectivePhone());
    }

    public function testReturnsPhoneWhenTechHasNoVoip(): void
    {
        $tech = $this->makeTech(['isVoipPhone' => false, 'comment' => null]);
        $stub = $this->makeStub(['Phone' => '1234', 'techs' => [$tech]]);
        $this->assertSame('1234', $stub->getEffectivePhone());
    }

    public function testReturnsNullWhenNoPhoneAndNoTechs(): void
    {
        $stub = $this->makeStub(['Phone' => null, 'techs' => []]);
        $this->assertNull($stub->getEffectivePhone());
    }
}
