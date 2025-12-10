<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\StringHelper;

/**
 * Статистика использования тегов
 * 
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $modelStats Статистика по моделям
 * @var int $totalTags Всего тегов
 * @var int $activeTags Активных тегов
 * @var int $archivedTags Архивных тегов
 * @var int $totalLinks Всего связей
 */

$this->title = 'Статистика использования тегов';
$this->params['breadcrumbs'][] = ['label' => 'Теги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="tags-statistics">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?= $totalTags ?></h3>
                    <p class="text-muted">Всего тегов</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="text-success"><?= $activeTags ?></h3>
                    <p class="text-muted">Активных</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="text-secondary"><?= $archivedTags ?></h3>
                    <p class="text-muted">Архивных</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="text-info"><?= $totalLinks ?></h3>
                    <p class="text-muted">Всего связей</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($modelStats)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5>Распределение по типам объектов</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Тип объекта</th>
                            <th class="text-center">Количество связей</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modelStats as $stat): ?>
                            <tr>
                                <td><?= Html::encode(StringHelper::basename($stat['model_class'])) ?></td>
                                <td class="text-center"><?= $stat['count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5>Теги по популярности</h5>
        </div>
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    [
                        'attribute' => 'name',
                        'format' => 'raw',
                        'value' => function($model) {
                            $textColor = $model->getTextColor();
                            $badge = Html::tag('span', Html::encode($model->name), [
                                'class' => 'badge',
                                'style' => "background-color: {$model->color}; color: {$textColor}; padding: 5px 10px; margin-right: 5px;",
                            ]);
                            return $badge . Html::a(Html::encode($model->name), ['view', 'id' => $model->id]);
                        },
                    ],
                    [
                        'attribute' => 'usage_count',
                        'label' => 'Использований',
                        'headerOptions' => ['style' => 'width: 150px'],
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    [
                        'attribute' => 'description',
                        'format' => 'ntext',
                        'value' => function($model) {
                            return $model->description ? 
                                (mb_strlen($model->description) > 80 ? 
                                    mb_substr($model->description, 0, 80) . '...' : 
                                    $model->description
                                ) : '';
                        },
                    ],
                    [
                        'attribute' => 'archived',
                        'format' => 'boolean',
                        'headerOptions' => ['style' => 'width: 100px'],
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

    <div class="mt-3">
        <?= Html::a('Вернуться к списку', ['index'], ['class' => 'btn btn-default']) ?>
    </div>

</div>