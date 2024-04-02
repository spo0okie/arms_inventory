<?php
/**
 * Вычисляемые поля для Оборудования
 */

namespace app\models\traits;




use app\helpers\ArrayHelper;
use app\models\MaintenanceReqs;
use app\models\Scans;
use app\models\Services;
use app\models\Techs;
use app\models\Users;

/**
 * @package app\models\traits

 * @property string $name
 * @property Scans $preview
 * @property Users[] $supportTeam
 */

trait TechsModelCalcFieldsTrait
{
	public function getEffectiveMaintenanceReqs()
	{
		/** @var Techs $this */
		$reqs=[];
		
		foreach ($this->maintenanceReqs as $maintenanceReq) {
			$reqs[$maintenanceReq->id]=$maintenanceReq;
		}
		
		foreach ($this->services as $service) {
			foreach ($service->maintenanceReqsRecursive as $maintenanceReq) {
				$reqs[$maintenanceReq->id]=$maintenanceReq;
			}
		}
		
		$reqs=ArrayHelper::findByField($reqs,'spread_techs',1);
		
		return MaintenanceReqs::filterEffective($reqs);
	}
	
	public function getName() {
		/** @var Techs $this */
		return $this->hostname?$this->hostname:$this->num;
	}
	
	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getPreview()
	{
		/** @var Techs $this */
		//ищем собственную картинку
		if ($this->scans_id && is_object($scan=Scans::find()->where(['id' => $this->scans_id ])->one())) return $scan;
		
		//ищем картинку от модели
		if (is_object($this->model)) return $this->model->preview;
		
		//сдаемся
		return null;
	}
	
	/**
	 * @return Users
	 */
	public function getResponsible()
	{
		/** @var Techs $this */
		if (is_object($user=Services::responsibleFrom($this->services))) return $user;
		
		return $this->itStaff;
	}
	
	/**
	 * Возвращает группу пользователей ответственный + поддержка всех сервисов на компе
	 * @return Users[]
	 * @noinspection PhpUnused
	 */
	public function getSupportTeam()
	{
		/** @var Techs $this */
		$team=Services::supportTeamFrom($this->services);
		
		//убираем из команды ответственного за ОС
		if (is_object($responsible=$this->responsible)) {
			if (isset($team[$responsible->id])) unset($team[$responsible->id]);
		}
		
		return array_values($team);
	}
	
	/**
	 * Возвращает название поля комментарий
	 */
	public function getCommentLabel()
	{
		/** @var Techs $this */
		if (is_object($model=$this->model)) {
			if (is_object($type=$model->type)) {
				if (strlen($type->comment_name))
					return $type->comment_name;
			}
		}
		return $this->getAttributeLabel('comment');
	}
	
	
	public function getIsComputer() {
		/** @var Techs $this */
		return $this->model->type->is_computer;
	}
	
	public function getIsVoipPhone() {
		/** @var Techs $this */
		return $this->model->type->is_phone;
	}
	
	public function getIsUps() {
		/** @var Techs $this */
		return $this->model->type->is_ups;
	}
	
	public function getIsMonitor() {
		/** @var Techs $this */
		return $this->model->type->is_display;
	}
	
	public function getArchived()
	{
		/** @var Techs $this */
		return is_object($this->state)?$this->state->archived:false;
	}
	
	public function getFormattedMac() {
		/** @var Techs $this */
		return Techs::formatMacs($this->mac);
	}
	
	/**
	 * Получить соединения
	 * @param string $direction направление соединений incoming / outgoing
	 * @param string $nodeSide с какой стороны участвует нода target / initiator
	 * @return array|mixed
	 */
	public function getEffectiveConnections(string $direction, string $nodeSide)
	{
		$directAttr=$direction.'Connections';
		$nodeAttr=$nodeSide.'Techs';
		$cacheAttr=$directAttr.'Effective';
		
		if (isset($this->attrsCache[$cacheAttr])) return $this->attrsCache[$cacheAttr];
		/** @var Techs $this */
		$connections=[];
		
		//выбираем прямые соединения
		foreach ($this->$directAttr as $connection)
			$connections[$connection->id]=$connection;
		
		//выбираем сервисы где не объявлены компы
		foreach ($this->services as $service) {
			foreach ($service->$directAttr as $connection) {
				if (empty($connection->$nodeAttr)) {
					$connections[$connection->id]=$connection;
				}
			}
		}
		return $this->attrsCache[$cacheAttr]=$connections;
		
	}
	
	/**
	 * Получить входящие соединения
	 * @return array|mixed
	 */
	public function getIncomingConnectionsEffective() {
		return $this->getEffectiveConnections('incoming','target');
	}
	
	/**
	 * Получить входящие соединения
	 * @return array|mixed
	 */
	public function getOutgoingConnectionsEffective() {
		return $this->getEffectiveConnections('outgoing','initiator');
	}
	
}