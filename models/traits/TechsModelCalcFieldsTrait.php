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
use Yii;

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
	
	
	public function getFqdn()
	{
		if (!$this->hostname) return '';
		return strtolower(is_object($this->domain)?$this->hostname.'.'.$this->domain->fqdn:$this->hostname);
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
	 * @return Users
	 */
	public function getServicesResponsible()
	{
		/** @var Techs $this */
		if (is_object($user=Services::responsibleFrom($this->services,true))) return $user;
		
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
		$team=Services::supportTeamFrom(
			(count($this->services)||!Yii::$app->params['techs.managementService.enable'])?
				$this->services:
				[$this->managementService]);
		
		//убираем из команды ответственного за ОС
		if (is_object($responsible=$this->responsible)) {
			if (isset($team[$responsible->id])) unset($team[$responsible->id]);
		}
		
		return array_values($team);
	}
	
	/**
	 * Возвращает группу пользователей ответственный + поддержка всех сервисов на компе
	 * @return Users[]
	 * @noinspection PhpUnused
	 */
	public function getServicesSupportTeam()
	{
		/** @var Techs $this */
		$team=Services::supportTeamFrom($this->services,true);
		
		//убираем из команды ответственного за ОС
		if (is_object($responsible=$this->servicesResponsible)) {
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
		if (isset($this->attrsCache['isComputer'])) return $this->attrsCache['isComputer'];
		return $this->model->type->is_computer??false;
	}
	
	public function getIsVoipPhone() {
		/** @var Techs $this */
		if (isset($this->attrsCache['isVoipPhone'])) return $this->attrsCache['isVoipPhone'];
		return $this->model->type->is_phone??false;
	}
	
	public function getIsUps() {
		/** @var Techs $this */
		if (isset($this->attrsCache['isUps'])) return $this->attrsCache['isUps'];
		return $this->model->type->is_ups??false;
	}
	
	public function getIsMonitor() {
		/** @var Techs $this */
		if (isset($this->attrsCache['isMonitor'])) return $this->attrsCache['isMonitor'];
		return $this->model->type->is_display??false;
	}
	
	/**
	 * @return array
	 */
	public function getVoipPhones()
	{
		if (isset($this->attrsCache['voipPhones'])) return $this->attrsCache['voipPhones'];
		$this->attrsCache['voipPhones']=[];
		foreach ($this->armTechs as $tech) if ($tech->isVoipPhone) $this->attrsCache['voipPhones'][]=$tech;
		return $this->attrsCache['voipPhones'];
	}
	
	/**
	 * @return array
	 */
	public function getUps()
	{
		if (isset($this->attrsCache['ups'])) return $this->attrsCache['ups'];
		$this->attrsCache['ups']=[];
		foreach ($this->armTechs as $tech) if ($tech->isUps) $this->attrsCache['ups'][]=$tech;
		return $this->attrsCache['ups'];
	}
	
	/**
	 * @return array
	 */
	public function getMonitors()
	{
		if (isset($this->attrsCache['monitors'])) return $this->attrsCache['monitors'];
		$this->attrsCache['monitors']=[];
		foreach ($this->armTechs as $tech) if ($tech->isMonitor) $this->attrsCache['monitors'][]=$tech;
		return $this->attrsCache['monitors'];
	}
	
	public function getArmTechsCount(){
		return count($this->armTechs);
	}
	
	public function getVoipPhonesCount(){
		return count($this->voipPhones);
	}
	
	public function getMonitorsCount(){
		return count($this->monitors);
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
	 * Возвращает комментарий порта из шаблона модели
	 * @param string $port
	 * @return mixed|null
	 */
	public function getModelPortComment(string $port)
	{
		if (is_object($this->model))
			return $this->model->getPortComment($port);
		else
			return null;
	}
	
	
	public function getUpdatedRenderClass(){
		if (is_object($this->comp)) {
			return $this->comp->updatedRenderClass;
		} else return '';
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