<?php

namespace app\models\traits;

use app\models\Markers;
use yii\db\ActiveQuery;

/**
 * Владелец цветового маркера (issue #141): модель с атрибутом marker_id.
 *
 * Дает связь marker и хелперы для ручного рендера span/td
 * (там, где элемент собирается не через ItemObjectWidget):
 *
 *   <span class="unit-status <?= $model->markerClass($legacyCssClass) ?>"
 *         style="<?= $model->markerStyle() ?>">
 *
 * При наличии маркера элемент получает класс marked-item и инлайн
 * CSS-переменные (web/css/markers.css), иначе — легаси CSS-класс.
 */
trait MarkerOwnerTrait
{
	/**
	 * @return ActiveQuery
	 */
	public function getMarker()
	{
		return $this->hasOne(Markers::class, ['id' => 'marker_id']);
	}

	/**
	 * CSS-класс раскраски: marked-item при назначенном маркере, иначе легаси-класс
	 * @param string $legacy легаси CSS-класс (fallback пока маркер не назначен)
	 * @return string
	 */
	public function markerClass(string $legacy = ''): string
	{
		return is_object($this->marker) ? 'marked-item' : $legacy;
	}

	/**
	 * Инлайн-стиль раскраски (CSS-переменные маркера) либо пустая строка
	 * @return string
	 */
	public function markerStyle(): string
	{
		return is_object($this->marker) ? $this->marker->styleVars : '';
	}
}
