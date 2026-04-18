<?php

namespace app\controllers;

use app\models\ui\SmsForm;
use Yii;


/**
 * SmsController реализует отправку SMS-сообщений.
 *
 * Содержит единственный action — actionSend, который управляет формой отправки.
 * Все acceptance-тесты отключены, так как отправка реальных SMS-сообщений
 * недопустима в тестовой среде.
 */
class SmsController extends ArmsBaseController
{
	/**
	 * Возвращает список отключенных действий базового CRUD.
	 *
	 * Контроллер реализует только форму/обработчик отправки SMS.
	 * Стандартные inherited action отключаем точечно.
	 *
	 * @return array<string>
	 */
	public function disabledActions(): array
	{
		return ['index', 'async-grid', 'item', 'item-by-name', 'ttip', 'view', 'validate', 'create', 'update', 'delete', 'editable'];
	}

	public function accessMap()
	{
		return [ArmsBaseController::PERM_EDIT=>['send']];
	}
	
	
    /**
     * Отображает форму отправки SMS или обрабатывает её отправку.
     *
     * GET (предзаполнение формы):
     *   phone (string, опционально) — номер телефона получателя.
     *   text (string, опционально)  — текст сообщения.
     * POST (поля SmsForm):
     *   phone (string) — номер телефона получателя.
     *   text (string)  — текст сообщения.
     * При успешной валидации POST отправляет SMS и рендерит send-response.
     * При GET или невалидном POST рендерит форму send-form.
     *
     * @return string HTML формы или результата отправки
     */
    public function actionSend()
	{
		$model = new SmsForm();
		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			$model->send();
			return $this->defaultRender('send-response', ['model' => $model,]);
		}
		$model->load(Yii::$app->request->get());
		return $this->defaultRender('send-form', ['model' => $model,]);
	}

	/**
	 * Acceptance test data for Send.
	 *
	 * Безопасно проверяет:
	 * - доступность формы (GET),
	 * - предзаполнение формы (GET),
	 * - невалидный POST без вызова send().
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function testSend(): array
	{
		return [
			[
				'name' => 'get-form',
				'GET' => [],
				'response' => 200,
			],
			[
				'name' => 'get-prefilled',
				'GET' => [
					'phone' => '79991234567',
					'text' => 'Acceptance prefilled message',
				],
				'response' => 200,
			],
			[
				'name' => 'post-invalid',
				'POST' => [
					'SmsForm' => [
						'phone' => '123',
						'text' => str_repeat('x', 140),
					],
				],
				'response' => 200,
			],
		];
	}
}
