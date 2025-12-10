<?php

namespace app\models\traits;

use app\models\Tags;
use Yii;

/**
 * Трейт для добавления функционала тегов к моделям
 * 
 * Использование:
 * 1. Добавить `use TaggableTrait;` в класс модели
 * 2. Добавить в linksSchema:
 *    'tag_ids' => [Tags::class, null, 'loader' => 'tags']
 * 3. Добавить в rules(): [['tag_ids'], 'safe']
 * 4. Реализовать метод getTags()
 */
trait TaggableTrait
{
    /**
     * @var array Виртуальный атрибут для работы с формами
     */
    public $tag_ids;
    
    /**
     * Связь с тегами через junction table
     * Должна быть реализована в модели:
     * 
     * public function getTags() {
     *     return $this->hasMany(Tags::class, ['id' => 'tag_id'])
     *         ->viaTable('tags_links', ['model_id' => 'id'], function($query) {
     *             $query->andWhere(['model_class' => static::class]);
     *         });
     * }
     * 
     * @return \yii\db\ActiveQuery
     */
    abstract public function getTags();
    
    /**
     * Возвращает массив ID тегов для использования в формах
     * 
     * @return array
     */
    public function getTagsArray()
    {
        if ($this->isNewRecord) {
            return [];
        }
        
        return Yii::$app->db->createCommand(
            'SELECT tag_id FROM tags_links WHERE model_class = :class AND model_id = :id',
            [':class' => static::class, ':id' => $this->id]
        )->queryColumn();
    }
    
    /**
     * Сохраняет теги из формы
     * 
     * @param array $tagIds Массив ID тегов
     * @return bool
     */
    public function setTagsArray($tagIds)
    {
        if ($this->isNewRecord) {
            return true;
        }
        
        $tagIds = is_array($tagIds) ? $tagIds : [];
        
        // Получаем текущие теги
        $currentTags = $this->getTagsArray();
        
        // Определяем что добавить и что удалить
        $toAdd = array_diff($tagIds, $currentTags);
        $toRemove = array_diff($currentTags, $tagIds);
        
        // Удаляем лишние
        if (!empty($toRemove)) {
            Yii::$app->db->createCommand()->delete('tags_links', [
                'tag_id' => $toRemove,
                'model_class' => static::class,
                'model_id' => $this->id,
            ])->execute();
            
            // Обновляем счетчики
            foreach ($toRemove as $tagId) {
                if ($tag = Tags::findOne($tagId)) {
                    $tag->recalculateUsageCount();
                }
            }
        }
        
        // Добавляем новые
        if (!empty($toAdd)) {
            $rows = [];
            foreach ($toAdd as $tagId) {
                $rows[] = [
                    'tag_id' => $tagId,
                    'model_class' => static::class,
                    'model_id' => $this->id,
                    'created_at' => gmdate('Y-m-d H:i:s'),
                ];
            }
            
            Yii::$app->db->createCommand()->batchInsert(
                'tags_links',
                ['tag_id', 'model_class', 'model_id', 'created_at'],
                $rows
            )->execute();
            
            // Обновляем счетчики
            foreach ($toAdd as $tagId) {
                if ($tag = Tags::findOne($tagId)) {
                    $tag->recalculateUsageCount();
                }
            }
        }
        
        return true;
    }
    
    /**
     * Добавляет тег к объекту
     * 
     * @param int $tagId ID тега
     * @return bool
     */
    public function addTag($tagId)
    {
        if ($this->isNewRecord) {
            return false;
        }
        
        // Проверяем что тег существует
        if (!Tags::findOne($tagId)) {
            return false;
        }
        
        // Проверяем что связь еще не существует
        if ($this->hasTag($tagId)) {
            return true;
        }
        
        // Создаем связь
        Yii::$app->db->createCommand()->insert('tags_links', [
            'tag_id' => $tagId,
            'model_class' => static::class,
            'model_id' => $this->id,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ])->execute();
        
        // Обновляем счетчик
        if ($tag = Tags::findOne($tagId)) {
            $tag->recalculateUsageCount();
        }
        
        return true;
    }
    
    /**
     * Удаляет тег у объекта
     * 
     * @param int $tagId ID тега
     * @return bool
     */
    public function removeTag($tagId)
    {
        if ($this->isNewRecord) {
            return false;
        }
        
        $result = Yii::$app->db->createCommand()->delete('tags_links', [
            'tag_id' => $tagId,
            'model_class' => static::class,
            'model_id' => $this->id,
        ])->execute();
        
        // Обновляем счетчик
        if ($result > 0 && ($tag = Tags::findOne($tagId))) {
            $tag->recalculateUsageCount();
        }
        
        return $result > 0;
    }
    
    /**
     * Проверяет наличие тега у объекта
     * 
     * @param int $tagId ID тега
     * @return bool
     */
    public function hasTag($tagId)
    {
        if ($this->isNewRecord) {
            return false;
        }
        
        return (bool) Yii::$app->db->createCommand(
            'SELECT COUNT(*) FROM tags_links WHERE tag_id = :tag_id AND model_class = :class AND model_id = :id',
            [':tag_id' => $tagId, ':class' => static::class, ':id' => $this->id]
        )->queryScalar();
    }
    
    /**
     * Находит объекты по тегу
     * 
     * @param int $tagId ID тега
     * @return \yii\db\ActiveQuery
     */
    public static function findByTag($tagId)
    {
        return static::find()
            ->innerJoin('tags_links', 
                'tags_links.model_id = ' . static::tableName() . '.id 
                AND tags_links.model_class = :class 
                AND tags_links.tag_id = :tag_id',
                [':class' => static::class, ':tag_id' => $tagId]
            );
    }
    
    /**
     * Находит объекты по нескольким тегам
     * 
     * @param array $tagIds Массив ID тегов
     * @param bool $matchAll Если true - объект должен иметь ВСЕ теги, если false - хотя бы один
     * @return \yii\db\ActiveQuery
     */
    public static function findByTags($tagIds, $matchAll = false)
    {
        if (empty($tagIds)) {
            return static::find()->where('1=0'); // Пустой результат
        }
        
        $query = static::find();
        
        if ($matchAll) {
            // Объект должен иметь ВСЕ указанные теги
            $query->innerJoin('tags_links', 
                'tags_links.model_id = ' . static::tableName() . '.id 
                AND tags_links.model_class = :class',
                [':class' => static::class]
            )
            ->andWhere(['tags_links.tag_id' => $tagIds])
            ->groupBy(static::tableName() . '.id')
            ->having('COUNT(DISTINCT tags_links.tag_id) = :count', [':count' => count($tagIds)]);
        } else {
            // Объект должен иметь хотя бы один из указанных тегов
            $query->innerJoin('tags_links', 
                'tags_links.model_id = ' . static::tableName() . '.id 
                AND tags_links.model_class = :class 
                AND tags_links.tag_id IN (' . implode(',', array_map('intval', $tagIds)) . ')',
                [':class' => static::class]
            )
            ->groupBy(static::tableName() . '.id');
        }
        
        return $query;
    }
    
    /**
     * После сохранения модели сохраняем теги
     * Этот метод должен быть вызван в afterSave() модели
     */
    public function saveTagsAfterSave()
    {
        if (isset($this->tag_ids)) {
            $this->setTagsArray($this->tag_ids);
        }
    }
    
    /**
     * После удаления модели удаляем все связи с тегами
     * Этот метод должен быть вызван в afterDelete() модели
     */
    public function deleteTagsAfterDelete()
    {
        if ($this->isNewRecord) {
            return;
        }
        
        // Получаем все теги для обновления счетчиков
        $tagIds = $this->getTagsArray();
        
        // Удаляем все связи
        Yii::$app->db->createCommand()->delete('tags_links', [
            'model_class' => static::class,
            'model_id' => $this->id,
        ])->execute();
        
        // Обновляем счетчики
        foreach ($tagIds as $tagId) {
            if ($tag = Tags::findOne($tagId)) {
                $tag->recalculateUsageCount();
            }
        }
    }
}