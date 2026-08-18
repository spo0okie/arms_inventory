<?php

namespace app\components\integrations\providers;

/**
 * Форма действия создания учётки AD — ad/create-account
 * (docs/dev/integrations.md, {@see AdUserProvider}).
 *
 * Три «интересных» поля: логин (предзаполняется по алгоритму
 * {@see AdUserProvider::suggestLogin()} — фамилия.и транслитом, свободный
 * вариант подбирает рендер формы), подразделение (дерево OU под
 * настроенным корнем usersOu) и группы (мультиселект, предвыбор из
 * конфига defaultGroups). Пароль — как у сброса: генерируется, уходит
 * пользователю по SMS и администратору не показывается, поэтому от
 * родительской формы наследуются только его параметры (тип/длина);
 * unlock смысла не имеет и не рендерится.
 *
 * @property string $login логин (sAMAccountName) создаваемой учётки
 * @property string $ou DN подразделения, куда создавать
 * @property array $groups DN групп, в которые включить
 */
class AdCreateAccountForm extends AdPasswordResetForm
{
	public $login = '';
	public $ou = '';
	public $groups = [];

	public function rules()
	{
		return array_merge(parent::rules(), [
			[['login', 'ou'], 'required'],
			['login', 'filter', 'filter' => static fn($value) => mb_strtolower(trim((string)$value))],
			//лимит 12 - регламент сквозных учёток (ограничение SAP,
			//AdUserProvider::LOGIN_MAX), жёстче лимита sAMAccountName в 20
			['login', 'match',
				'pattern' => '/^[a-z][a-z0-9._-]{0,'.(AdUserProvider::LOGIN_MAX - 1).'}$/',
				'message' => 'Логин: латиница, цифры, точка, дефис, подчёркивание; не более '
					.AdUserProvider::LOGIN_MAX.' символов (ограничение SAP), начинается с буквы'],
			['ou', 'string', 'max' => 512],
			['groups', 'default', 'value' => []],
			//не each: форма построена на ArmsModel (ActiveRecord), и
			//встроенный валидатор each полез бы в схему несуществующей
			//таблицы; фильтр нормализует массив DN-строк сам
			['groups', 'filter', 'filter' => static fn($value) => array_values(array_filter(
				array_map(static fn($dn) => trim((string)(is_scalar($dn) ? $dn : '')), (array)$value),
				static fn($dn) => $dn !== '' && mb_strlen($dn) <= 512
			))],
		]);
	}

	public function attributeData()
	{
		return array_merge(parent::attributeData(), [
			'login' => [
				'Логин',
				'hint' => 'Имя учётной записи (sAMAccountName). Предзаполняется по регламенту: '
					.'«фамилия.и» транслитерацией ФИО, не более '.AdUserProvider::LOGIN_MAX
					.' символов (ограничение SAP; не влезло - обрезается с конца вместе с точкой). '
					.'Занятость в AD проверяется при открытии формы, однофамильцам добавляется номер.',
			],
			'ou' => [
				'Подразделение',
				'hint' => 'Контейнер (OU) дерева AD, в котором будет создана учётная запись.',
			],
			'groups' => [
				'Группы',
				'hint' => 'Группы AD, в которые включить новую учётную запись '
					.'(Ctrl+клик — выбор нескольких). Первичная группа «Пользователи домена» '
					.'добавляется самим AD.',
			],
		]);
	}
}
