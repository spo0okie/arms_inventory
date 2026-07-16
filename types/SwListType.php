<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class SwListType extends TextType
{
	public static function name(): string
	{
		return 'soft-list';
	}

	/**
	 * Разбирает отпечаток софта в список карточек [['publisher'=>..,'name'=>..],..]
	 * @return array|null null - значение не разбирается как список ПО
	 */
	public static function parseList(?string $value): ?array
	{
		if (!strlen(trim((string)$value))) return [];
		$items=json_decode('['.$value.']',true);
		if (!is_array($items)) return null;
		$list=[];
		foreach ($items as $item) {
			if (!is_array($item) || !isset($item['name'])) return null;
			$list[]=[
				'publisher'=>(string)($item['publisher']??''),
				'name'=>(string)$item['name'],
			];
		}
		return $list;
	}

	/**
	 * Diff отпечатков софта для журнала истории (issue #194): вместо пары
	 * гигантских JSON показываем только изменения — установленное ПО (added),
	 * удалённое (removed) и обновлённое (changed: вендор и имя с точностью
	 * до цифровых фрагментов совпали — считаем сменой версии)
	 */
	public function diffValues(?string $old, ?string $new): ?array
	{
		$oldList=static::parseList($old);
		$newList=static::parseList($new);
		if ($oldList===null || $newList===null) return null;

		//мультимножества: одинаковые карточки взаимно сокращаются
		$removed=static::listDiff($oldList,$newList);
		$added=static::listDiff($newList,$oldList);

		//спаривание «обновлений»: выбывшая и добавленная карточки одного ПО
		$changed=[];
		foreach ($removed as $ri=>$oldItem) {
			foreach ($added as $ai=>$newItem) {
				if (static::updateKey($oldItem)!==static::updateKey($newItem)) continue;
				$changed[]=static::renderUpdate($oldItem,$newItem);
				unset($removed[$ri],$added[$ai]);
				break;
			}
		}

		return [
			'added'=>array_map([static::class,'renderListItem'],array_values($added)),
			'removed'=>array_map([static::class,'renderListItem'],array_values($removed)),
			'changed'=>$changed,
		];
	}

	/**
	 * Карточки $a, отсутствующие в $b (мультимножество: дубли считаются)
	 */
	protected static function listDiff(array $a,array $b): array
	{
		$counts=[];
		foreach ($b as $item) {
			$key=static::itemKey($item);
			$counts[$key]=($counts[$key]??0)+1;
		}
		$diff=[];
		foreach ($a as $item) {
			$key=static::itemKey($item);
			if (($counts[$key]??0)>0) {$counts[$key]--;continue;}
			$diff[]=$item;
		}
		return $diff;
	}

	/**
	 * Ключ точного совпадения карточки ПО
	 */
	protected static function itemKey(array $item): string
	{
		return mb_strtolower(trim($item['publisher']).'|'.trim($item['name']));
	}

	/**
	 * Ключ опознания «то же ПО, другая версия»: вендор + имя без версионных
	 * фрагментов. Отличить смену версии от полной замены ПО по отпечатку
	 * нельзя, поэтому эвристика: совпало всё кроме версии — это обновление
	 */
	protected static function updateKey(array $item): string
	{
		$base=$item['name'];
		//хвостовая версия пакетного стиля (python3-jwt-2.10.1-2+deb13u1):
		//от разделителя и цифры до конца имени, включая буквенно-цифровые
		//суффиксы ревизий (+deb13u1, ~rc1)
		$base=preg_replace('/[-_ ]\d[\w.+~:-]*$/u','',$base);
		$base=preg_replace('/\d[\d.,_-]*/u','',$base);	//цифровые фрагменты внутри имени
		$base=preg_replace('/\(\s*\)/u','',$base);		//опустевшие скобки
		$base=trim(preg_replace('/\s+/u',' ',$base));
		return mb_strtolower(trim($item['publisher'])).'|'.mb_strtolower($base);
	}

	/**
	 * HTML карточки ПО: имя + вендор
	 */
	protected static function renderListItem(array $item): string
	{
		$html=Html::encode($item['name']);
		if (strlen(trim($item['publisher'])))
			$html.=' <span class="text-muted">('.Html::encode($item['publisher']).')</span>';
		return $html;
	}

	/**
	 * HTML обновления ПО: общее начало имени + «хвост старый → хвост новый»,
	 * чтобы было видно, что сменился только кусок версии. Имя режется на
	 * фрагменты по разделителям (пробел/дефис/подчёркивание), чтобы работали
	 * и пакетные имена без пробелов (python3-jwt-2.6.0-1)
	 */
	protected static function renderUpdate(array $old,array $new): string
	{
		$oldPieces=preg_split('/([-_ \/])/u',trim($old['name']),-1,PREG_SPLIT_DELIM_CAPTURE);
		$newPieces=preg_split('/([-_ \/])/u',trim($new['name']),-1,PREG_SPLIT_DELIM_CAPTURE);
		$max=min(count($oldPieces),count($newPieces));
		$common=0;
		while ($common<$max && $oldPieces[$common]===$newPieces[$common]) $common++;
		//общий префикс заканчивается разделителем: хвосты - целые фрагменты,
		//версию не режем посередине (не «LibreOffice 7.<del>4.1.2</del>»)
		while ($common>0 && !preg_match('/^[-_ \/]$/u',$oldPieces[$common-1])) $common--;
		$prefix=implode('',array_slice($oldPieces,0,$common));
		$oldTail=implode('',array_slice($oldPieces,$common));
		$newTail=implode('',array_slice($newPieces,$common));

		$html=Html::encode($prefix)
			.'<del class="text-muted">'.Html::encode($oldTail).'</del>'
			.' &rarr; '.Html::encode($newTail);
		if (strlen(trim($new['publisher'])))
			$html.=' <span class="text-muted">('.Html::encode($new['publisher']).')</span>';
		return $html;
	}

	/**
	 * Пока стандартный скаляр-рендер вместо текстового (ntext) рендера
	 * родительского TextType — паритет с прежним выводом. Обогащение —
	 * по ходу аудита карточек (4в).
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		return $this->renderPlainValue($model,$attribute);
	}

	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->textInput();
	}

	public function apiSchema(): array
	{
		return ['type' => 'string', 'format' => 'soft-list'];
	}

	public function samples(): array
	{
		return [
			'{"publisher":"Microsoft","name":"Windows 10 Pro"},' . "\n" .
			'{"publisher":"Google LLC","name":"Google Chrome"}',
		];
	}

	public function generate(AttributeContext $context): mixed
	{
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		$rng = $context->randomizer();
		$publishers = ['Microsoft', 'Google LLC', 'VideoLAN', 'Mozilla', 'JetBrains'];
		$products = ['Windows 11 Pro', 'Google Chrome', 'VLC media player', 'Firefox', 'PhpStorm'];
		$count = $rng->getInt(5, 10);
		$items = [];
		for ($i = 0; $i < $count; $i++) {
			$items[] = json_encode([
				'publisher' => AttributeContext::pickRandomValue($publishers, $rng),
				'name' => AttributeContext::pickRandomValue($products, $rng),
			], JSON_UNESCAPED_UNICODE);
		}

		return implode(",\n", $items);
	}

	public static function validateSoftList(?string $value): ?string
	{
		$value = trim((string)$value);
		if ($value === '') {
			return null;
		}

		try {
			$items = json_decode('[' . $value . ']', true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			return 'Ожидается список JSON-объектов через запятую без внешнего массива';
		}

		if (!is_array($items) || $items === []) {
			return 'Список ПО должен содержать хотя бы один объект';
		}

		foreach ($items as $item) {
			if (!is_array($item)) {
				return 'Каждый элемент списка ПО должен быть JSON-объектом';
			}
			if (!array_key_exists('publisher', $item) || !is_string($item['publisher'])) {
				return 'У элемента ПО поле publisher обязательно и должно быть строкой';
			}
			if (!array_key_exists('name', $item) || !is_string($item['name'])) {
				return 'У элемента ПО поле name обязательно и должно быть строкой';
			}
		}

		return null;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
			new RuleDefinition(function ($model, $attribute) {
				$error = static::validateSoftList($model->$attribute);
				if ($error !== null) {
					$model->addError($attribute, $error);
				}
			}),
		];
	}
}
