<?php

namespace app\controllers;

use app\models\Techs;
use Yii;
use app\models\Ports;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * PortsController implements the CRUD actions for Ports model.
 */
class PortsController extends ArmsBaseController
{
	public $modelClass=Ports::class;

	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'edit'=>['port-list',],
		]);
	}

	/**
	 * Создаёт порт через общий сценарий actionUpdate(null).
	 *
	 * Для POST нужны поля Ports (например, link_techs_id и параметры порта)
	 * с валидными внешними ключами.
	 */
	public function actionCreate() {
		return $this->actionUpdate(null);
	}

	/**
	 * Тестовые данные для actionCreate.
	 *
	 * Нужен валидный payload Ports с существующим link_techs_id.
	 * В текущем автогенеративном прогоне используется базовый тест create из ArmsBaseController.
	 */
	public function testCreate(): array
	{
		return parent::testCreate();
	}

    /**
     * Обновляет существующий порт или создаёт новый (если id = null).
     *
     * Для POST нужен валидный payload модели Ports.
     * Для GET при id != null должен существовать порт с указанным id.
     *
     * @param int|null $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate(int $id=null)
    {
        $model = is_null($id)?
			$model=new Ports():
			$this->findModel($id);

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->defaultReturn(['view', 'id' => $model->id],[$model]);
		}

		$model->load(Yii::$app->request->get());
		return $this->defaultRender('update', ['model' => $model,]);
    }

	/**
	 * Тестовые данные для actionUpdate.
	 *
	 * Нужен существующий id порта и валидный payload полей для обновления.
	 * В текущем прогоне используется базовый сценарий update из ArmsBaseController.
	 */
	public function testUpdate(): array
	{
		return parent::testUpdate();
	}

	/**
	 * Возвращает список доступных сетевых портов для выбранного оборудования (depdrop JSON).
	 *
	 * Для корректного запроса требуется POST['depdrop_all_params']['link_techs_id'].
	 *
	 * @return array
	 */
	public function actionPortList()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		if (isset($_POST['depdrop_all_params'])) {
			$params = $_POST['depdrop_all_params'];
			if (is_array($params)) {
				if (isset($params['link_techs_id']) && strlen($params['link_techs_id'])) {
					$model=Techs::findOne($params['link_techs_id']);
					return ['output'=>$model->ddPortsList, 'selected'=>''];
				} else {
					return ['output'=>[], 'selected'=>''];
				}
			}
		}
		return ['output'=>'', 'selected'=>''];
	}

	/**
	 * Тестовые данные для actionPortList.
	 *
	 * Нужно отправить POST-данные depdrop:
	 * - depdrop_all_params[link_techs_id] = существующий ID Techs.
	 * Сейчас тест пропущен, так как acceptance-генератор не формирует depdrop-POST формат
	 * и не готовит гарантированный комплект связанных Techs/Ports.
	 */
	public function testPortList(): array
	{
		return self::skipScenario('default', 'requires depdrop POST payload and linked Techs fixtures');
	}

	/**
	 * Тестовые данные для actionItem (унаследованного).
	 *
	 * Нужен id порта с валидной связью на Techs/Network.
	 * Сейчас тест пропущен из-за отсутствия стабильного набора связанных фикстур.
	 */
	public function testItem(): array
	{
		return self::skipScenario('default', 'ports item requires linked tech/network fixtures');
	}

	/**
	 * Тестовые данные для actionTtip (унаследованного).
	 *
	 * Нужен id порта с валидной связью на Techs/Network.
	 * Сейчас тест пропущен из-за отсутствия стабильного набора связанных фикстур.
	 */
	public function testTtip(): array
	{
		return self::skipScenario('default', 'ports ttip requires linked tech/network fixtures');
	}

	/**
	 * Тестовые данные для actionView (унаследованного).
	 *
	 * Нужен id порта с валидной связью на Techs/Network.
	 * Сейчас тест пропущен из-за отсутствия стабильного набора связанных фикстур.
	 */
	public function testView(): array
	{
		return self::skipScenario('default', 'ports view requires linked tech/network fixtures');
	}

	/**
	 * Finds the Ports model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param integer $id
	 * @param null    $failRoute
	 * @return Ports the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
    protected function findModel(int $id, $failRoute=null)
    {
        if (($model = Ports::findOne($id)) !== null) {
            return $model;
        }

        if (!is_null($failRoute)) {
			$this->redirect($failRoute);
		}

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
