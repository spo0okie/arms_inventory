<?php
/**
 * Вычисляемые поля для связей сервисов
 */

namespace app\models\traits;





use app\helpers\ArrayHelper;
use app\models\Comps;
use app\models\ServiceConnections;
use app\models\Services;
use app\models\Techs;

/**
 * @property int $id
 * @property int|null $initiator_id
 * @property int|null $target_id
 * @property string|null $initiator_details
 * @property string|null $target_details
 * @property string|null $comment
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property int[] $initiator_comps_ids
 * @property int[] $initiator_techs_ids
 * @property int[] $target_comps_ids
 * @property int[] $target_techs_ids
 * @property Comps[] $initiatorComps
 * @property Comps[] $targetComps
 * @property Techs[] $initiatorTechs
 * @property Techs[] $targetTechs
 * @property Comps[] $initiatorCompsEffective
 * @property Comps[] $targetCompsEffective
 * @property Techs[] $initiatorTechsEffective
 * @property Techs[] $targetTechsEffective
 * @property Services $initiator
 * @property Services $target
 * @property string sname
 * @property string initiatorName
 * @property string targetName
 * @property string name
 * @property string restOfComment
 * @package app\models\traits
 */

trait ServiceConnectionsModelCalcFieldsTrait
{

	public function getInitiatorName() {
		/** @var ServiceConnections $this */
		if (!isset($this->attrsCache['initiatorName'])) {
			//если есть имя сервиса, то збс
			if (is_object($this->initiator)) {
				return $this->attrsCache['initiatorName']=$this->initiator->name;
			}
			$nodes=array_merge(
				ArrayHelper::getArrayField($this->initiatorComps,'name'),
				ArrayHelper::getArrayField($this->initiatorTechs,'name')
			);
			if (count($nodes)) {
				sort($nodes);
				$this->attrsCache['initiatorName']=implode(", ",$nodes);
			} else {
				$this->attrsCache['initiatorName']=(string)($this->initiator_details);
			}
			
		}
		return $this->attrsCache['initiatorName'];
	}
	
	public function getTargetName() {
		/** @var ServiceConnections $this */
		if (!isset($this->attrsCache['targetName'])) {
			//если есть имя сервиса, то збс
			if (is_object($this->target)) {
				return $this->attrsCache['targetName']=$this->target->name;
			}
			$nodes=array_merge(
				ArrayHelper::getArrayField($this->targetComps,'name'),
				ArrayHelper::getArrayField($this->targetTechs,'name')
			);
			if (count($nodes)) {
				sort($nodes);
				$this->attrsCache['targetName']=implode(", ",$nodes);
			} else {
				$this->attrsCache['targetName']=(string)($this->target_details);
			}
			
		}
		return $this->attrsCache['initiatorName'];
	}
	
	public function getName() {
		if (strlen($this->comment)) {
			return explode("\n",$this->comment)[0];
		}
		return $this->initiatorName.' → '.$this->targetName;
	}
	
	public function getSname() {return $this->getName();}
	
	/**
	 * комментарий без первой строки
	 * @return mixed|string
	 */
	public function getRestOfComment() {
		if (isset($this->attrsCache['restOfComment'])) return $this->attrsCache['restOfComment'];
		if (!strlen($this->comment)) return $this->attrsCache['restOfComment']='';
		$rows=explode("\n",$this->comment);
		if (count($rows)) unset($rows[0]);
		return $this->attrsCache['restOfComment']=implode("\n",$rows);
	}
	
	/**
	 * Получить узлы связи из явно заявленных или из сервиса
	 * @param string $source target/initiator
	 * @param string $type comps/techs
	 * @return Comps[]|Techs[]
	 */
	public function getNodesEffective(string $source, string $type)
	{
		//где хранятся явно заявленные узлы
		$selfNodes=lcfirst($source).ucfirst($type);
		
		//где будет храниться кэш такого запроса?
		$cacheAttr=$selfNodes.'Effective';
		
		//если кэш уже заполнен - отдаем
		if (isset($this->attrsCache[$cacheAttr])) return $this->attrsCache[$cacheAttr];
		
		//если явно обозначены узлы, то нужно вернуть их
		if (count($this->$selfNodes))
			return $this->attrsCache[$cacheAttr]=$this->$selfNodes;
		
		//иначе все узлы берем из сервиса
		if (is_object($this->$source))
			return $this->attrsCache[$cacheAttr]=$this->$source->$type;
		
		//иначе пусто
		return $this->attrsCache[$cacheAttr]=[];
	
	}
	
	/**
	 * Какие ОС/ВМ участвуют в
	 * @return Comps[]|Techs[]
	 */
	public function getInitiatorCompsEffective() {
		return $this->getNodesEffective('initiator','comps');
	}
	public function getInitiatorTechsEffective() {
		return $this->getNodesEffective('initiator','techs');
	}
	public function getTargetCompsEffective() {
		return $this->getNodesEffective('target','comps');
	}
	public function getTargetTechsEffective() {
		return $this->getNodesEffective('target','techs');
	}
}