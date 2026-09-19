<?php

namespace app\controllers;

use app\generation\ModelFactory;
use app\models\DnsNames;
use app\models\NetIps;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * DnsNamesController реализует CRUD операции для модели DnsNames (DNS-имена в зонах).
 */
class DnsNamesController extends ArmsBaseController
{
	/**
	 * @var string Класс модели для CRUD операций
	 */
	public $modelClass = DnsNames::class;

	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(), [
			self::PERM_VIEW => ['search-list'],
			self::PERM_VIEW . '-dns-names' => ['search-list'],
			self::PERM_EDIT => ['attach-ip'],
			self::PERM_EDIT . '-dns-names' => ['attach-ip'],
		]);
	}

	/**
	 * item-by-name работает по полному имени: колонки name у таблицы нет,
	 * DnsNames::findByName раскладывает FQDN на зону и имя в зоне.
	 * @param string $name
	 * @return DnsNames
	 * @throws NotFoundHttpException
	 */
	protected function findByName(string $name)
	{
		if (($model = DnsNames::findByName($name)) !== null) {
			return $model;
		}
		throw new NotFoundHttpException('Object with requested name does not exist.');
	}

	/**
	 * Подсказки полных имён для поля «выбери готовое или введи своё»
	 * (форма {@see actionAttachIp()}): JSON [{value: fqdn, id}], не больше 30.
	 *
	 * GET-параметры:
	 * @param string|null $name подстрока полного имени
	 * @return array
	 */
	public function actionSearchList($name = null)
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		$name = mb_strtolower(trim((string)$name, " \t\n\r\0\x0B."));
		$out = [];
		foreach (DnsNames::find()->with('domain')->all() as $item) {
			/** @var DnsNames $item */
			if ($name !== '' && mb_strpos($item->fqdn, $name) === false) continue;
			$out[] = ['value' => $item->fqdn, 'id' => $item->id];
		}
		usort($out, fn($a, $b) => strcmp($a['value'], $b['value']));
		return array_slice($out, 0, 30);
	}

	public function testSearchList(): array
	{
		return [
			['name' => 'all'],
			['name' => 'filtered', 'GET' => ['name' => 'a']],
		];
	}

	/**
	 * Одно поле — два исхода: привязать адрес к DNS-имени. Если введённое полное
	 * имя уже заведено — адрес дописывается в его список `ip`, иначе создаётся
	 * новое имя с этим адресом. Источник истины связи имя↔адрес — текстовое поле
	 * `ip` имени (junction пересчитывает {@see DnsNames::beforeSave()}), поэтому
	 * привязка идёт через сохранение имени, а не через связи адреса.
	 *
	 * GET-параметры:
	 * @param int $ips_id адрес (net_ips.id), который привязываем
	 *
	 * POST: DnsNames[host] (полное имя или имя в зоне), DnsNames[domain_id]
	 * (зона — нужна, только если суффикс не совпал ни с одной заведённой),
	 * DnsNames[comment] (только для нового имени).
	 *
	 * @return array|string|Response
	 * @throws NotFoundHttpException если адрес не найден
	 */
	public function actionAttachIp(int $ips_id)
	{
		if (($ip = NetIps::findOne($ips_id)) === null) {
			throw new NotFoundHttpException('The requested IP does not exist.');
		}

		$model = new DnsNames();
		if ($model->load(Yii::$app->request->post())) {
			//прогон правил host раскладывает FQDN на зону + имя (DnsNames::filterHost);
			//ошибку unique тут игнорируем — совпадение как раз и означает «привязать»
			$model->validate(['host']);
			$model->clearErrors();

			$existing = empty($model->domain_id) ? null :
				DnsNames::findOne(['domain_id' => $model->domain_id, 'host' => $model->host]);

			if (is_object($existing)) {
				if (!in_array($ip->id, $existing->net_ips_ids)) {
					$existing->ip = trim(($existing->ip ?? '') . "\n" . $ip->text_addr);
				}
				if ($existing->save()) {
					return $this->defaultReturn(['/net-ips/view', 'id' => $ip->id], [$existing]);
				}
				$model->addError('host', 'Не удалось привязать адрес к имени: '
					. implode('; ', $existing->getErrorSummary(true)));
			} else {
				$model->ip = $ip->text_addr;
				if ($model->save()) {
					return $this->defaultReturn(['/net-ips/view', 'id' => $ip->id], [$model]);
				}
			}
		}

		return $this->defaultRender('attach-ip', ['model' => $model, 'ip' => $ip]);
	}

	/**
	 * Сценарии {@see actionAttachIp()}: открытие формы; создание нового имени
	 * (случайный host в зоне из тестовых данных); повторная привязка того же имени
	 * к другому адресу (ветка «имя уже есть»).
	 * @return array
	 */
	public function testAttachIp(): array
	{
		$testData = $this->getTestData();
		/** @var DnsNames $full */
		$full = $testData['full'];
		$ip = ModelFactory::create(NetIps::class, ['empty' => true]);
		$host = 'attach-' . Yii::$app->security->generateRandomString(8);
		return [
			['name' => 'form load', 'GET' => ['ips_id' => $ip->id]],
			[
				'name' => 'create new name',
				'GET' => ['ips_id' => $ip->id],
				'POST' => ['DnsNames' => ['host' => strtolower($host), 'domain_id' => $full->domain_id]],
				'response' => 302,
			],
			[
				'name' => 'attach existing name',
				'GET' => ['ips_id' => $ip->id],
				'POST' => ['DnsNames' => ['host' => $full->fqdn]],
				'response' => 302,
			],
		];
	}
}
