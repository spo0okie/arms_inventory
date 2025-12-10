<?php

namespace app\models;

use app\helpers\StringHelper;
use Yii;
use yii\helpers\Inflector;

/**
 * Модель для работы с тегами
 *
 * @property int $id
 * @property string $name Название тега
 * @property string $slug Уникальный идентификатор
 * @property string $color Цвет фона в HEX формате
 * @property string $description Описание назначения тега
 * @property int $usage_count Количество использований
 * @property bool $archived Признак архивирования
 * @property string $created_at Дата создания
 * @property string $updated_at Дата последнего изменения
 * @property string $updated_by Автор последних изменений
 *
 * @property string $textColor Вычисленный цвет текста для контрастности
 */
class Tags extends ArmsModel
{
    /** @var string Название модели в единственном числе */
    public static $title = 'Тег';
    
    /** @var string Название модели во множественном числе */
    public static $titles = 'Теги';
    
    /** @var string Атрибут, который считается именем модели */
    public static $nameAttr = 'name';
    
    /** @var string История изменений НЕ требуется согласно ТЗ */
    protected $historyClass = null;
    
    /**
     * Схема связей модели согласно AttributeLinksModelTrait
     * @var array
     */
    public $linksSchema = [
        'services_ids' => [Services::class, 'tag_ids'],
    ];
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tags';
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'color'], 'required'],
            [['name'], 'string', 'max' => 32],
            [['name'], 'trim'],
            [['slug'], 'string', 'max' => 48],
            [['slug'], 'unique'],
            [['color'], 'string', 'max' => 7],
            [['color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Цвет должен быть в формате HEX (#RRGGBB)'],
            [['description'], 'string', 'max' => 255],
            [['usage_count'], 'integer'],
            [['usage_count'], 'default', 'value' => 0],
            [['archived'], 'boolean'],
            [['archived'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'safe'],
            [['updated_by'], 'string', 'max' => 32],
            [['services_ids'], 'each', 'rule' => ['integer']],
        ];
    }
    
    /**
     * Метаданные атрибутов согласно AttributeDataModelTrait
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return array_merge(parent::attributeData(), [
            'name' => [
                'label' => 'Название',
                'hint' => 'Название тега (до 32 символов)',
                'indexLabel' => 'Тег',
                'type' => 'string',
            ],
            'slug' => [
                'label' => 'Slug',
                'hint' => 'Уникальный идентификатор (генерируется автоматически из названия)',
                'type' => 'string',
                'readOnly' => true,
            ],
            'color' => [
                'label' => 'Цвет',
                'hint' => 'Цвет фона тега в формате HEX (#RRGGBB). Цвет текста подбирается автоматически для обеспечения контрастности.',
                'indexLabel' => 'Цвет',
                'type' => 'string',
            ],
            'description' => [
                'label' => 'Описание',
                'hint' => 'Краткое описание назначения тега (до 255 символов)',
                'type' => 'string',
            ],
            'usage_count' => [
                'label' => 'Использований',
                'hint' => 'Количество объектов с этим тегом (обновляется автоматически)',
                'indexLabel' => 'Исп.',
                'type' => 'integer',
                'readOnly' => true,
            ],
            'services_ids' => [
                'label' => 'Сервисы',
                'hint' => 'Сервисы, помеченные этим тегом',
                'indexLabel' => 'Сервисы',
                'placeholder' => 'Нет связанных сервисов',
            ],
        ]);
    }
    
    /**
     * Генерация slug перед сохранением
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        // Генерируем slug если его нет
        if (empty($this->slug)) {
            $this->slug = $this->generateSlug($this->name);
        }
        
        return true;
    }
    
    /**
     * Генерирует уникальный slug из названия
     * 
     * @param string $name Название тега
     * @return string Уникальный slug
     */
    protected function generateSlug($name)
    {
        // Транслитерация и преобразование в slug
        $slug = Inflector::slug($name, '-', true);
        
        // Если slug пустой (например, только спецсимволы), используем fallback
        if (empty($slug)) {
            $slug = 'tag-' . time();
        }
        
        // Проверяем уникальность и добавляем суффикс при коллизиях
        $originalSlug = $slug;
        $counter = 2;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Проверяет существование slug в БД
     * 
     * @param string $slug Проверяемый slug
     * @return bool
     */
    protected function slugExists($slug)
    {
        $query = static::find()->where(['slug' => $slug]);
        
        // Исключаем текущую запись при обновлении
        if (!$this->isNewRecord) {
            $query->andWhere(['!=', 'id', $this->id]);
        }
        
        return $query->exists();
    }
    
    /**
     * Вычисляет контрастный цвет текста (#000 или #fff) на основе яркости фона
     * Использует алгоритм WCAG для обеспечения читаемости
     * 
     * @return string '#000000' или '#ffffff'
     */
    public function getTextColor()
    {
        // Извлекаем RGB компоненты из HEX
        $hex = ltrim($this->color, '#');
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Вычисляем относительную яркость по формуле WCAG
        // https://www.w3.org/TR/WCAG20/#relativeluminancedef
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        
        // Если яркость больше 0.5, используем темный текст, иначе светлый
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
    
    /**
     * Пересчитывает количество использований тега
     * 
     * @return bool
     */
    public function recalculateUsageCount()
    {
        $this->usage_count = (int) Yii::$app->db->createCommand(
            'SELECT COUNT(*) FROM tags_links WHERE tag_id = :tag_id',
            [':tag_id' => $this->id]
        )->queryScalar();
        
        return $this->silentSave(false);
    }
    
    /**
     * Связь с сервисами через junction table
     * @return \yii\db\ActiveQuery
     */
    public function getServices()
    {
        return $this->hasMany(Services::class, ['id' => 'model_id'])
            ->viaTable('tags_links', ['tag_id' => 'id'], function($query) {
                $query->andWhere(['model_class' => Services::class]);
            });
    }
 
}