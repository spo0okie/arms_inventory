<?php

namespace tests\unit\models;

use app\models\Tags;
use app\models\TagsSearch;
use Codeception\Test\Unit;

/**
 * Тесты для модели TagsSearch
 */
class TagsSearchTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
        // Очищаем таблицу перед каждым тестом
        Tags::deleteAll();
        
        // Создаем тестовые данные
        $this->createTestTags();
    }
    
    /**
     * Создает тестовые теги
     */
    protected function createTestTags()
    {
        $tags = [
            ['name' => 'Важный', 'color' => '#FF0000', 'description' => 'Важные задачи', 'usage_count' => 10],
            ['name' => 'Срочный', 'color' => '#FF5733', 'description' => 'Срочные дела', 'usage_count' => 5],
            ['name' => 'Обычный', 'color' => '#33FF57', 'description' => 'Обычные задачи', 'usage_count' => 3],
            ['name' => 'Архивный', 'color' => '#808080', 'description' => 'Старый тег', 'usage_count' => 1, 'archived' => 1],
        ];
        
        foreach ($tags as $tagData) {
            $tag = new Tags($tagData);
            $tag->save(false);
        }
    }
    
    /**
     * Тест базового поиска без фильтров
     */
    public function testSearchWithoutFilters()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([]);
        
        $this->assertNotNull($dataProvider);
        $this->assertEquals(4, $dataProvider->getTotalCount(), 'Должно быть найдено 4 тега');
    }
    
    /**
     * Тест поиска по имени
     */
    public function testSearchByName()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([
            'TagsSearch' => ['name' => 'Важный']
        ]);
        
        $models = $dataProvider->getModels();
        $this->assertCount(1, $models, 'Должен быть найден 1 тег');
        $this->assertEquals('Важный', $models[0]->name);
    }
    
    /**
     * Тест поиска по частичному совпадению имени
     */
    public function testSearchByPartialName()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([
            'TagsSearch' => ['name' => 'ный']
        ]);
        
        $models = $dataProvider->getModels();
        $this->assertGreaterThanOrEqual(3, count($models), 'Должно быть найдено минимум 3 тега с "ный"');
    }
    
    /**
     * Тест поиска по slug
     */
    public function testSearchBySlug()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([
            'TagsSearch' => ['slug' => 'vaznyj']
        ]);
        
        $models = $dataProvider->getModels();
        $this->assertCount(1, $models, 'Должен быть найден 1 тег');
        $this->assertEquals('vaznyj', $models[0]->slug);
    }
    
    /**
     * Тест поиска по цвету
     */
    public function testSearchByColor()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([
            'TagsSearch' => ['color' => '#FF0000']
        ]);
        
        $models = $dataProvider->getModels();
        $this->assertCount(1, $models, 'Должен быть найден 1 тег с красным цветом');
        $this->assertEquals('#FF0000', $models[0]->color);
    }
    
    /**
     * Тест поиска по описанию
     */
    public function testSearchByDescription()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([
            'TagsSearch' => ['description' => 'задачи']
        ]);
        
        $models = $dataProvider->getModels();
        $this->assertGreaterThanOrEqual(2, count($models), 'Должно быть найдено минимум 2 тега');
    }
    
    /**
     * Тест поиска по usage_count
     */
    public function testSearchByUsageCount()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([
            'TagsSearch' => ['usage_count' => 10]
        ]);
        
        $models = $dataProvider->getModels();
        $this->assertCount(1, $models, 'Должен быть найден 1 тег с usage_count = 10');
        $this->assertEquals(10, $models[0]->usage_count);
    }
    
    /**
     * Тест поиска по статусу архивирования
     */
    public function testSearchByArchived()
    {
        // Поиск неархивированных
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([
            'TagsSearch' => ['archived' => 0]
        ]);
        
        $models = $dataProvider->getModels();
        $this->assertCount(3, $models, 'Должно быть найдено 3 неархивированных тега');
        
        // Поиск архивированных
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([
            'TagsSearch' => ['archived' => 1]
        ]);
        
        $models = $dataProvider->getModels();
        $this->assertCount(1, $models, 'Должен быть найден 1 архивированный тег');
        $this->assertEquals('Архивный', $models[0]->name);
    }
    
    /**
     * Тест сортировки по умолчанию
     */
    public function testDefaultSorting()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([]);
        
        $models = $dataProvider->getModels();
        
        // По умолчанию сортировка: usage_count DESC, name ASC
        $this->assertEquals('Важный', $models[0]->name, 'Первым должен быть тег с наибольшим usage_count');
        $this->assertEquals(10, $models[0]->usage_count);
    }
    
    /**
     * Тест множественных фильтров
     */
    public function testMultipleFilters()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([
            'TagsSearch' => [
                'name' => 'ный',
                'archived' => 0,
            ]
        ]);
        
        $models = $dataProvider->getModels();
        $this->assertGreaterThanOrEqual(2, count($models), 'Должно быть найдено минимум 2 неархивированных тега с "ный"');
        
        foreach ($models as $model) {
            $this->assertEquals(0, $model->archived, 'Все найденные теги должны быть неархивированными');
            $this->assertStringContainsString('ный', $model->name, 'Все найденные теги должны содержать "ный"');
        }
    }
    
    /**
     * Тест пагинации
     */
    public function testPagination()
    {
        $searchModel = new TagsSearch();
        $dataProvider = $searchModel->search([]);
        
        $pagination = $dataProvider->getPagination();
        $this->assertNotNull($pagination);
        $this->assertEquals(50, $pagination->pageSize, 'Размер страницы должен быть 50');
    }
    
    /**
     * Тест валидации search модели
     */
    public function testValidation()
    {
        $searchModel = new TagsSearch();
        
        // Валидные данные
        $searchModel->load([
            'TagsSearch' => [
                'name' => 'Тест',
                'usage_count' => 5,
            ]
        ]);
        $this->assertTrue($searchModel->validate(), 'Валидные данные должны пройти валидацию');
        
        // Невалидные данные
        $searchModel = new TagsSearch();
        $searchModel->load([
            'TagsSearch' => [
                'usage_count' => 'не число',
            ]
        ]);
        $this->assertFalse($searchModel->validate(), 'Невалидные данные не должны пройти валидацию');
    }
}