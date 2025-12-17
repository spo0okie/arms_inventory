<?php

namespace tests\unit\models;

use app\models\Tags;
use Codeception\Test\Unit;

/**
 * Тесты для модели Tags
 */
class TagsTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
        // Очищаем таблицу перед каждым тестом
        Tags::deleteAll();
    }
    
    /**
     * Тест создания тега с валидными данными
     */
    public function testCreateTag()
    {
        $tag = new Tags([
            'name' => 'Тестовый тег',
            'color' => '#FF5733',
            'description' => 'Описание тестового тега',
        ]);
        
        $this->assertTrue($tag->save(), 'Тег должен быть сохранен');
        $this->assertNotEmpty($tag->id, 'ID тега должен быть установлен');
        $this->assertEquals('Тестовый тег', $tag->name);
        $this->assertEquals('#FF5733', $tag->color);
        $this->assertEquals(0, $tag->usage_count, 'usage_count должен быть 0 по умолчанию');
        $this->assertEquals(0, $tag->archived, 'archived должен быть 0 по умолчанию');
    }
    
    /**
     * Тест автогенерации slug из названия
     */
    public function testSlugGeneration()
    {
        $tag = new Tags([
            'name' => 'Тестовый Тег',
            'color' => '#FF5733',
        ]);
        
        $this->assertTrue($tag->save());
        $this->assertNotEmpty($tag->slug, 'Slug должен быть сгенерирован');
        $this->assertEquals('testovyj-teg', $tag->slug, 'Slug должен быть транслитерирован');
    }
    
    /**
     * Тест обработки коллизий slug
     */
    public function testSlugUniqueness()
    {
        // Создаем первый тег
        $tag1 = new Tags([
            'name' => 'Тест',
            'color' => '#FF5733',
        ]);
        $this->assertTrue($tag1->save());
        $this->assertEquals('test', $tag1->slug);
        
        // Создаем второй тег с таким же именем
        $tag2 = new Tags([
            'name' => 'Тест',
            'color' => '#33FF57',
        ]);
        $this->assertTrue($tag2->save());
        $this->assertEquals('test-2', $tag2->slug, 'Slug должен иметь суффикс -2');
        
        // Создаем третий тег
        $tag3 = new Tags([
            'name' => 'Тест',
            'color' => '#3357FF',
        ]);
        $this->assertTrue($tag3->save());
        $this->assertEquals('test-3', $tag3->slug, 'Slug должен иметь суффикс -3');
    }
    
    /**
     * Тест валидации HEX цвета
     */
    public function testColorValidation()
    {
        // Валидный цвет
        $tag = new Tags([
            'name' => 'Тег',
            'color' => '#FF5733',
        ]);
        $this->assertTrue($tag->validate(['color']), 'Валидный HEX цвет должен пройти валидацию');
        
        // Невалидные цвета
        $invalidColors = [
            'FF5733',      // без #
            '#FF57',       // короткий
            '#FF57339',    // длинный
            '#GGGGGG',     // неверные символы
            'red',         // название цвета
        ];
        
        foreach ($invalidColors as $color) {
            $tag = new Tags([
                'name' => 'Тег',
                'color' => $color,
            ]);
            $this->assertFalse($tag->validate(['color']), "Цвет '$color' не должен пройти валидацию");
        }
    }
    
    /**
     * Тест вычисления цвета текста
     */
    public function testGetTextColor()
    {
        // Светлый фон - должен быть темный текст
        $tag = new Tags([
            'name' => 'Светлый',
            'color' => '#FFFFFF', // белый
        ]);
        $this->assertEquals('#000000', $tag->getTextColor(), 'На белом фоне должен быть черный текст');
        
        // Темный фон - должен быть светлый текст
        $tag = new Tags([
            'name' => 'Темный',
            'color' => '#000000', // черный
        ]);
        $this->assertEquals('#ffffff', $tag->getTextColor(), 'На черном фоне должен быть белый текст');
        
        // Средний фон
        $tag = new Tags([
            'name' => 'Средний',
            'color' => '#808080', // серый
        ]);
        $textColor = $tag->getTextColor();
        $this->assertTrue(
            in_array($textColor, ['#000000', '#ffffff']),
            'Цвет текста должен быть либо черным, либо белым'
        );
    }
    
    /**
     * Тест контрастности цвета текста (WCAG)
     */
    public function testGetTextColorContrast()
    {
        $testColors = [
            '#FF0000' => '#ffffff', // красный -> белый
            '#00FF00' => '#000000', // зеленый -> черный
            '#0000FF' => '#ffffff', // синий -> белый
            '#FFFF00' => '#000000', // желтый -> черный
            '#FF00FF' => '#ffffff', // пурпурный -> белый
            '#00FFFF' => '#000000', // циан -> черный
        ];
        
        foreach ($testColors as $bgColor => $expectedTextColor) {
            $tag = new Tags([
                'name' => 'Тест',
                'color' => $bgColor,
            ]);
            $this->assertEquals(
                $expectedTextColor,
                $tag->getTextColor(),
                "Для цвета $bgColor ожидается текст $expectedTextColor"
            );
        }
    }
    
    /**
     * Тест пересчета usage_count
     */
    public function testRecalculateUsageCount()
    {
        $tag = new Tags([
            'name' => 'Тег для подсчета',
            'color' => '#FF5733',
        ]);
        $this->assertTrue($tag->save());
        $this->assertEquals(0, $tag->usage_count);
        
        // Добавляем связи вручную
        \Yii::$app->db->createCommand()->insert('tags_links', [
            'tag_id' => $tag->id,
            'model_class' => 'app\\models\\Services',
            'model_id' => 1,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ])->execute();
        
        \Yii::$app->db->createCommand()->insert('tags_links', [
            'tag_id' => $tag->id,
            'model_class' => 'app\\models\\Services',
            'model_id' => 2,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ])->execute();
        
        // Пересчитываем
        $tag->recalculateUsageCount();
        $tag->refresh();
        
        $this->assertEquals(2, $tag->usage_count, 'usage_count должен быть 2');
    }
    
    /**
     * Тест валидации обязательных полей
     */
    public function testValidationErrors()
    {
        $tag = new Tags();
        $this->assertFalse($tag->save(), 'Тег без обязательных полей не должен сохраниться');
        $this->assertArrayHasKey('name', $tag->errors, 'Должна быть ошибка для name');
        $this->assertArrayHasKey('color', $tag->errors, 'Должна быть ошибка для color');
    }
    
    /**
     * Тест метода fetchNames() из базового класса
     */
    public function testFetchNames()
    {
        // Создаем несколько тегов
        $tag1 = new Tags(['name' => 'Альфа', 'color' => '#FF0000']);
        $tag1->save();
        
        $tag2 = new Tags(['name' => 'Бета', 'color' => '#00FF00']);
        $tag2->save();
        
        $tag3 = new Tags(['name' => 'Гамма', 'color' => '#0000FF', 'archived' => 1]);
        $tag3->save();
        
        $list = Tags::fetchNames();
        
        $this->assertIsArray($list);
        $this->assertCount(3, $list, 'Должно быть 3 тега (включая архивированный)');
        $this->assertArrayHasKey($tag1->id, $list);
        $this->assertArrayHasKey($tag2->id, $list);
        $this->assertArrayHasKey($tag3->id, $list);
    }
    
    /**
     * Тест архивирования
     */
    public function testArchiving()
    {
        $tag = new Tags(['name' => 'Архивный', 'color' => '#808080']);
        $tag->save();
        
        $this->assertEquals(0, $tag->archived, 'По умолчанию archived = 0');
        
        $tag->archived = 1;
        $this->assertTrue($tag->save(), 'Тег должен быть архивирован');
        
        $tag->refresh();
        $this->assertEquals(1, $tag->archived, 'Тег должен быть помечен как архивированный');
    }
}