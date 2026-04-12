<?php

namespace app\controllers;


use app\models\LicKeys;
use app\models\links\LicLinks;
use Throwable;
use yii\data\ArrayDataProvider;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * LicKeysController implements the CRUD actions for LicKeys model.
 */
class LicKeysController extends ArmsBaseController
{
	
	/**
	 * Acceptance test data for Link.
	 *
	 * Пропускается, так как для теста необходимо:
	 *  - создать LicKeys через getTestData()['full'];
	 *  - привязать к ключу хотя бы один объект (Soft, Arms, Users или Comps).
	 * Без предварительно созданной связи тест привязки не выполним.
	 */
	public function testLink(): array
	{
		return self::skipScenario('default', 'requires LicKeys with at least one linked object — prepare via getTestData() and link manually');
	}
	
	/**
	 * Acceptance test data for Unlink.
	 *
	 * Пропускается, так как для теста необходимо:
	 *  - создать LicKeys через getTestData()['full'];
	 *  - привязать к ключу хотя бы один объект (Soft, Arms, Users или Comps);
	 *  - передать в GET параметр id и соответствующий *_id объекта для отвязки.
	 * Без предварительно созданных связей проверить логику разрыва невозможно.
	 */
	public function testUnlink(): array
	{
		return self::skipScenario('default', 'requires LicKeys with linked objects — prepare via getTestData() and link manually');
	}
	public $modelClass=LicKeys::class;
	public function disabledActions()
	{
		return ['item-by-name',];
	}
    /**
     * Страница просмотра лицензионного ключа.
     *
     * Отображает карточку LicKeys и таблицу объектов, привязанных к ключу
     * (Soft, Arms, Users, Comps) через LicLinks.
     *
     * GET-параметры:
     * @param int $id Идентификатор LicKeys.
     *
     * @return mixed
     * @throws NotFoundHttpException если запись не найдена
     */
    public function actionView(int $id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
			'linksData'=>new ArrayDataProvider([
				'allModels' => LicLinks::findForLic('keys',$id),
				'key'=>'id',
				'sort' => [
					'attributes'=> [
						'objName',
						'comment',
						'changedAt',
						'changedBy',
					],
					'defaultOrder' => [
						'objName' => SORT_ASC
					]
				],
				'pagination' => false,
			]),
		]);
    }
	
	/**
	 * Удаление лицензионного ключа.
	 *
	 * Удаляет запись LicKeys и перенаправляет на страницу просмотра
	 * родительской лицензионной позиции (/lic-items/view?id={lic_items_id}).
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор LicKeys.
	 *
	 * POST-параметры: пустой POST (требуется VerbFilter).
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если LicKeys с данным id не найдена
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
    	/** @var LicKeys $model */
    	$model=$this->findModel($id);
    	$lic_items_id=$model->lic_items_id;
        $model->delete();

	    return $this->redirect(['/lic-items/view', 'id' => $lic_items_id]);
    }
}
