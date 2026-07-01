<?php

namespace tests\unit\models;

use app\models\Aces;
use app\models\Acls;
use Codeception\Test\Unit;

/**
 * Тесты группировки ACL по одинаковому набору ACE (итерация 2 групповых ACL).
 *
 * Проверяется контракт «одинаковости» ACE и группировки ACL без обращения к БД:
 * модели строятся в памяти, связи ACL→ACE задаются через populateRelation().
 *
 * @see Aces::aceSignature()
 * @see Acls::acesSignature()
 * @see Acls::groupByAces()
 */
class AclsGroupingTest extends Unit
{
	/** @var \UnitTester */
	protected $tester;

	/**
	 * Собирает ACE с заданными атрибутами (без сохранения).
	 */
	private function ace(array $attrs): Aces
	{
		$ace=new Aces();
		foreach ($attrs as $name=>$value) $ace->$name=$value;
		return $ace;
	}

	/**
	 * Собирает ACL с подставленным набором ACE (без сохранения).
	 *
	 * @param Aces[] $aces
	 */
	private function acl(array $aces): Acls
	{
		$acl=new Acls();
		$acl->populateRelation('aces',$aces);
		return $acl;
	}

	public function testIdenticalAcesHaveEqualSignature()
	{
		$a=$this->ace(['comment'=>'доступ','notepad'=>'заметка','users_ids'=>[1,2]]);
		$b=$this->ace(['comment'=>'доступ','notepad'=>'заметка','users_ids'=>[2,1]]);
		$this->assertSame($a->aceSignature(),$b->aceSignature(),
			'ACE с одинаковым составом (порядок субъектов не важен) должны иметь равную сигнатуру');
	}

	public function testCommentAffectsSignature()
	{
		$a=$this->ace(['comment'=>'A']);
		$b=$this->ace(['comment'=>'B']);
		$this->assertNotSame($a->aceSignature(),$b->aceSignature(),
			'Разный комментарий — разные ACE');
	}

	public function testNotepadAffectsSignature()
	{
		$a=$this->ace(['comment'=>'X','notepad'=>'n1']);
		$b=$this->ace(['comment'=>'X','notepad'=>'n2']);
		$this->assertNotSame($a->aceSignature(),$b->aceSignature(),
			'Разная записная книжка — разные ACE');
	}

	public function testSubjectsAffectSignature()
	{
		$a=$this->ace(['users_ids'=>[1]]);
		//подтверждаем, что виртуальный атрибут связи возвращает заданное значение до сохранения
		$this->assertEquals([1],$a->users_ids);
		$b=$this->ace(['users_ids'=>[2]]);
		$this->assertNotSame($a->aceSignature(),$b->aceSignature(),
			'Разный набор субъектов — разные ACE');
	}

	public function testAclSignatureIsOrderIndependent()
	{
		$x=$this->ace(['comment'=>'X']);
		$y=$this->ace(['comment'=>'Y']);
		$acl1=$this->acl([$x,$y]);
		$acl2=$this->acl([$y,$x]);
		$this->assertSame($acl1->acesSignature(),$acl2->acesSignature(),
			'Порядок ACE внутри ACL не должен влиять на сигнатуру набора');
	}

	public function testDifferentAceSetsDifferentSignature()
	{
		$acl1=$this->acl([$this->ace(['comment'=>'X'])]);
		$acl2=$this->acl([$this->ace(['comment'=>'X']),$this->ace(['comment'=>'Y'])]);
		$this->assertNotSame($acl1->acesSignature(),$acl2->acesSignature(),
			'ACL с разным набором ACE имеют разные сигнатуры');
	}

	public function testGroupByAces()
	{
		//два ACL с одинаковым набором ACE и один с другим
		$g1a=$this->acl([$this->ace(['comment'=>'same','users_ids'=>[1]])]);
		$g1b=$this->acl([$this->ace(['comment'=>'same','users_ids'=>[1]])]);
		$g2 =$this->acl([$this->ace(['comment'=>'other'])]);

		$groups=Acls::groupBySignatures([$g1a,$g2,$g1b]);

		$this->assertCount(2,$groups,'Должно получиться две группы');
		$sizes=array_map('count',$groups);
		sort($sizes);
		$this->assertSame([1,2],$sizes,'Одна группа из 2 ACL (одинаковые ACE) и одна из 1 (другой ACE)');
	}

}
