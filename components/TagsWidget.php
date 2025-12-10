<?php

namespace app\components;

use app\models\Tags;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Виджет для отображения тегов объекта в виде цветных бейджей
 * 
 * Использование:
 * ```php
 * // С моделью, использующей TaggableTrait
 * echo TagsWidget::widget(['model' => $model]);
 * 
 * // С массивом тегов напрямую
 * echo TagsWidget::widget(['tags' => $tags]);
 * 
 * // С настройкой размера
 * echo TagsWidget::widget(['model' => $model, 'size' => 'sm']);
 * ```
 */
class TagsWidget extends Widget
{
    /**
     * @var \app\models\ArmsModel Модель с трейтом TaggableTrait
     */
    public $model;
    
    /**
     * @var Tags[] Массив тегов (альтернатива model)
     */
    public $tags;
	
    /**
     * @var string Сообщение если тегов нет
     */
    public $emptyText = '';
    
    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // Получаем теги
        if ($this->model !== null) {
            $tags = $this->model->tags;
        } elseif ($this->tags !== null) {
            $tags = $this->tags;
        } else {
            $tags = [];
        }
        
        // Если тегов нет
        if (empty($tags)) return $this->emptyText;
        
        // Рендерим бейджи
        $badges = [];
        foreach ($tags as $tag) {
            $badges[] = $tag->renderItem($this->view);
        }
        
        return implode(' ', $badges);
    }
	
}