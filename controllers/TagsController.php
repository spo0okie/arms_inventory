<?php

namespace app\controllers;

use app\models\Tags;
use app\models\TagsSearch;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * TagsController реализует CRUD операции для модели Tags
 */
class TagsController extends ArmsBaseController
{
    /**
     * @var string Класс модели для CRUD операций
     */
    public $modelClass = Tags::class;
    
    /**
     * Карта доступа к экшенам контроллера
     * @return array
     */
    public function accessMap()
    {
        return [
            self::PERM_VIEW => ['index', 'view', 'statistics'],
            self::PERM_EDIT => ['create', 'update', 'delete'],
        ];
    }
    
    /**
     * Статистика использования тегов
     * 
     * @return string
     */
    public function actionStatistics()
    {
        $query = Tags::find()
            ->orderBy(['usage_count' => SORT_DESC, 'name' => SORT_ASC]);
        
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);
        
        // Статистика по моделям
        $modelStats = Yii::$app->db->createCommand(
            'SELECT model_class, COUNT(*) as count 
             FROM tags_links 
             GROUP BY model_class 
             ORDER BY count DESC'
        )->queryAll();
        
        // Общая статистика
        $totalTags = Tags::find()->count();
        $activeTags = Tags::find()->where(['archived' => 0])->count();
        $archivedTags = Tags::find()->where(['archived' => 1])->count();
        $totalLinks = Yii::$app->db->createCommand('SELECT COUNT(*) FROM tags_links')->queryScalar();
        
        return $this->render('statistics', [
            'dataProvider' => $dataProvider,
            'modelStats' => $modelStats,
            'totalTags' => $totalTags,
            'activeTags' => $activeTags,
            'archivedTags' => $archivedTags,
            'totalLinks' => $totalLinks,
        ]);
    }
}