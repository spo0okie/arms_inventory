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
	 * Returns disabled acceptance tests list.
	 *
	 * Все тесты контроллера отключены: actionSend инициирует реальную отправку SMS,
	 * что неприемлемо при автоматическом запуске acceptance-тестов.
	 */
	public function disabledTests(): array
	{
		return ['*'];
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
}
