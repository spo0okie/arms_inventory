<?php

namespace app\migrations;

use yii\db\Migration;


/**
 * Создание БД первой версии (редакция Азимут)
 * Class m191123_164814_initial
 */
class m180101_010101_initial extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	$sql= /** @lang MySQL */
		<<<SQL
set names utf8mb4;

CREATE TABLE `arms` (
  `id` int(11) NOT NULL COMMENT 'Идентификатор',
  `num` varchar(16) DEFAULT NULL COMMENT 'Инвентарный номер',
  `inv_num` varchar(16) DEFAULT NULL COMMENT 'Бухгалтерский инвентарный номер',
  `comp_id` int(11) COMMENT 'Основная ОС рабочего места',
  `model_id` int(11) DEFAULT NULL,
  `model` varchar(128) DEFAULT NULL COMMENT 'Модель системного блока',
  `sn` varchar(128) DEFAULT NULL COMMENT 'Серийный номер',
  `hw` text COMMENT 'Аппаратное обеспечение',
  `user_id` varchar(16) DEFAULT NULL COMMENT 'Пользователь',
  `responsible_id` varchar(16) DEFAULT NULL COMMENT 'Ответственный',
  `head_id` varchar(16) DEFAULT NULL COMMENT 'Руководитель отдела',
  `it_staff_id` varchar(16) DEFAULT NULL COMMENT 'Сотрудник службы ИТ',
  `places_id` int(11) DEFAULT NULL,
  `comment` varchar(128) DEFAULT NULL COMMENT 'Комментарий',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Время изменения',
  `state_id` int(11) DEFAULT NULL,
  `history` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `comps`
--

CREATE TABLE `comps` (
  `id` int(11) NOT NULL COMMENT 'Идентификатор',
  `domain_id` int(11) DEFAULT NULL COMMENT 'Домен',
  `name` varchar(32) NOT NULL COMMENT 'Имя',
  `os` varchar(128) NOT NULL COMMENT 'ОС',
  `raw_hw` text COMMENT 'Отпечаток железа',
  `raw_soft` text COMMENT 'Отпечаток софта',
  `raw_version` varchar(32) DEFAULT NULL COMMENT 'Версия скрипта отправившего данные',
  `exclude_hw` text COMMENT 'Оборудование для исключения из паспорта',
  `ignore_hw` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Игнорировать аппаратное обеспечение',
  `ip` varchar(255) NOT NULL,
  `ip_ignore` varchar(255),
  `arm_id` int(11) DEFAULT NULL COMMENT 'Рабочее место',
  `comment` varchar(128) DEFAULT NULL COMMENT 'Комментарий',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Время обновления'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL COMMENT 'id',
  `parent_id` int(11) DEFAULT NULL COMMENT 'Родительский договор',
  `is_successor` tinyint(1) NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название документа',
  `date` date DEFAULT NULL COMMENT 'Начало периода действия',
  `end_date` date DEFAULT NULL COMMENT 'Конец периода действия',
  `comment` text COLLATE utf8mb4_unicode_ci COMMENT 'Комментарий'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Договоры';

-- --------------------------------------------------------

--
-- Структура таблицы `contracts_in_arms`
--

CREATE TABLE `contracts_in_arms` (
  `id` int(11) NOT NULL,
  `contracts_id` int(11) NOT NULL,
  `arms_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `contracts_in_lics`
--

CREATE TABLE `contracts_in_lics` (
  `id` int(11) NOT NULL,
  `contracts_id` int(11) NOT NULL,
  `lics_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `contracts_in_techs`
--

CREATE TABLE `contracts_in_techs` (
  `id` int(11) NOT NULL,
  `contracts_id` int(11) NOT NULL,
  `techs_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `domains`
--

CREATE TABLE `domains` (
  `id` int(11) NOT NULL COMMENT 'Идентификатор',
  `name` varchar(16) NOT NULL COMMENT 'Имя',
  `fqdn` varchar(128) NOT NULL COMMENT 'FQDN',
  `comment` varchar(255) DEFAULT NULL COMMENT 'Комментарий'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `hw_ignore`
--

CREATE TABLE `hw_ignore` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `fingerprint` varchar(255) NOT NULL COMMENT 'Отпечаток ',
  `comment` varchar(255) NOT NULL COMMENT 'Комментарий'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `lic_groups`
--

CREATE TABLE `lic_groups` (
  `id` int(11) NOT NULL COMMENT 'Идентификатор',
  `lic_types_id` int(11) NOT NULL COMMENT 'Тип лицензирования',
  `descr` varchar(255) NOT NULL COMMENT 'Описание',
  `comment` varchar(255) DEFAULT NULL COMMENT 'Комментарий',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время создания'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `lic_groups_in_arms`
--

CREATE TABLE `lic_groups_in_arms` (
  `id` int(11) NOT NULL,
  `arms_id` int(11) NOT NULL,
  `lics_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `lic_items`
--

CREATE TABLE `lic_items` (
  `id` int(11) NOT NULL COMMENT 'Идентификатор',
  `lic_group_id` int(11) NOT NULL COMMENT 'В группе лицензий',
  `descr` varchar(255) NOT NULL COMMENT 'Описание закупки',
  `count` int(11) NOT NULL COMMENT 'Количество приобретенных лицензий',
  `comment` varchar(255) DEFAULT NULL COMMENT 'Комментарий',
  `active_from` date DEFAULT NULL COMMENT 'Начало периода действия',
  `active_to` date DEFAULT NULL COMMENT 'Окончание периода действия',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время создания'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `lic_items_in_arms`
--

CREATE TABLE `lic_items_in_arms` (
  `id` int(11) NOT NULL,
  `arms_id` int(11) NOT NULL,
  `lics_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `lic_types`
--

CREATE TABLE `lic_types` (
  `id` int(11) NOT NULL COMMENT 'Идентификатор',
  `name` varchar(32) NOT NULL COMMENT 'Служебное имя',
  `descr` varchar(128) NOT NULL COMMENT 'Описание',
  `comment` text NOT NULL COMMENT 'Комментарий',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время создания'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `login_journal`
--

CREATE TABLE `login_journal` (
  `id` int(11) NOT NULL COMMENT 'id',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время',
  `comp_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Компьютер',
  `comps_id` int(11) DEFAULT NULL COMMENT 'ID Компьютера',
  `user_login` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Пользователь',
  `users_id` varchar(16) CHARACTER SET utf8 DEFAULT NULL COMMENT 'ID Пользователя'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Журнал входа в систему';

-- --------------------------------------------------------

--
-- Структура таблицы `manufacturers`
--

CREATE TABLE `manufacturers` (
  `id` int(11) NOT NULL COMMENT 'Идентификатор',
  `name` varchar(255) NOT NULL COMMENT 'Название',
  `full_name` varchar(255) DEFAULT NULL COMMENT 'Полное название',
  `comment` varchar(255) DEFAULT NULL COMMENT 'Комментарий',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время создания'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `manufacturers_dict`
--

CREATE TABLE `manufacturers_dict` (
  `id` int(11) NOT NULL,
  `word` varchar(255) NOT NULL COMMENT 'Вариант написания',
  `manufacturers_id` int(11) NOT NULL COMMENT 'Производитель'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `org_inet`
--

CREATE TABLE `org_inet` (
  `id` int(11) NOT NULL COMMENT 'id',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Имя',
  `ip_addr` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP Адрес',
  `ip_mask` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Маска подсети',
  `ip_gw` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Шлюз по умолчанию',
  `ip_dns1` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '1й DNS сервер',
  `ip_dns2` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '2й DNS сервер',
  `type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Тип подключения',
  `static` tinyint(1) DEFAULT NULL COMMENT 'Статический?',
  `comment` text COLLATE utf8mb4_unicode_ci COMMENT 'Дополнительно',
  `prov_tel_id` int(11) NOT NULL COMMENT 'Услуга связи',
  `places_id` int(11) DEFAULT NULL COMMENT 'Помещение',
  `contracts_id` int(11) DEFAULT NULL COMMENT 'Договор',
  `account` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Аккаунт, л/с',
  `history` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'История'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `org_phones`
--

CREATE TABLE `org_phones` (
  `id` int(11) NOT NULL,
  `country_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Код страны',
  `city_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Код города',
  `local_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Местный номер',
  `places_id` int(11) DEFAULT NULL COMMENT 'Помещение',
  `prov_tel_id` int(11) NOT NULL COMMENT 'Услуга телефонии',
  `contracts_id` int(11) DEFAULT NULL,
  `account` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Комментарий'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Городские телефонные номера в организации';

-- --------------------------------------------------------

--
-- Структура таблицы `org_struct`
--

CREATE TABLE `org_struct` (
  `id` varchar(16) NOT NULL COMMENT 'Идентификатор (seqnr)',
  `pup` varchar(16) NOT NULL COMMENT 'Вышестоящий отдел',
  `name` varchar(255) NOT NULL COMMENT 'Название подразделения'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `partners`
--

CREATE TABLE `partners` (
  `id` int(11) NOT NULL COMMENT 'id',
  `inn` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ИНН',
  `kpp` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'КПП',
  `uname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Юр. название',
  `bname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Бренд',
  `coment` text COLLATE utf8mb4_unicode_ci COMMENT 'Комментарий'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Контрагенты';

-- --------------------------------------------------------

--
-- Структура таблицы `partners_in_contracts`
--

CREATE TABLE `partners_in_contracts` (
  `id` int(11) NOT NULL,
  `partners_id` int(11) NOT NULL,
  `contracts_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `places`
--

CREATE TABLE `places` (
  `id` int(11) NOT NULL COMMENT 'id',
  `parent_id` int(11) DEFAULT NULL COMMENT 'Предок',
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название',
  `addr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Адрес',
  `prefix` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Префикс',
  `short` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Короткое имя'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Помещения';

-- --------------------------------------------------------

--
-- Структура таблицы `prov_tel`
--

CREATE TABLE `prov_tel` (
  `id` int(11) NOT NULL COMMENT 'id',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cabinet_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Личный кабинет',
  `support_tel` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Телефон поддержки',
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Комментарий'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Поставки услуг телефонии';

-- --------------------------------------------------------

--
-- Структура таблицы `scans`
--

CREATE TABLE `scans` (
  `id` int(11) NOT NULL COMMENT 'id',
  `contracts_id` int(11) NOT NULL,
  `format` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Формат файла',
  `file` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `soft`
--

CREATE TABLE `soft` (
  `id` int(11) NOT NULL COMMENT 'Идентификатор',
  `manufacturers_id` int(11) NOT NULL COMMENT 'Разработчик',
  `descr` varchar(255) NOT NULL COMMENT 'Описание',
  `comment` varchar(255) DEFAULT NULL COMMENT 'Комментарий',
  `items` text COMMENT 'Элементы входящие в пакет ПО',
  `additional` text COMMENT 'Дополнительные компоненты',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время создания'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `soft_hits`
--

CREATE TABLE `soft_hits` (
  `id` int(11) NOT NULL,
  `soft_id` int(11) NOT NULL,
  `comp_id` int(11) NOT NULL,
  `hits` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `soft_in_comps`
--

CREATE TABLE `soft_in_comps` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `comp_id` int(11) NOT NULL COMMENT 'Компьютер',
  `soft_id` int(11) NOT NULL COMMENT 'ПО'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `soft_in_lics`
--

CREATE TABLE `soft_in_lics` (
  `id` int(11) NOT NULL,
  `soft_id` int(11) NOT NULL,
  `lics_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `soft_in_lists`
--

CREATE TABLE `soft_in_lists` (
  `id` int(11) NOT NULL COMMENT 'id',
  `soft_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `soft_lists`
--

CREATE TABLE `soft_lists` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `name` varchar(32) NOT NULL COMMENT 'Служебное имя',
  `descr` varchar(256) NOT NULL COMMENT 'Описание',
  `comment` text NOT NULL COMMENT 'Комментарий'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `techs`
--

CREATE TABLE `techs` (
  `id` int(11) NOT NULL COMMENT 'Идентификатор',
  `num` varchar(16) DEFAULT NULL COMMENT 'Инвентарный номер',
  `inv_num` varchar(128) DEFAULT NULL COMMENT 'Бухгалтерский инвентарный номер',
  `model_id` int(11) NOT NULL COMMENT 'Модель оборудования',
  `sn` varchar(128) DEFAULT NULL COMMENT 'Серийный номер',
  `arms_id` int(11) DEFAULT NULL COMMENT 'Рабочее место',
  `places_id` int(11) DEFAULT NULL COMMENT 'Помещение',
  `user_id` varchar(16) DEFAULT NULL COMMENT 'Пользователь',
  `it_staff_id` varchar(16) DEFAULT NULL COMMENT 'Сотрудник службы ИТ',
  `ip` varchar(16) DEFAULT NULL COMMENT 'IP адрес',
  `mac` varchar(17) DEFAULT NULL COMMENT 'MAC адрес',
  `state_id` int(11) DEFAULT NULL COMMENT 'Состояние',
  `url` text COMMENT 'Ссылка',
  `comment` text COMMENT 'Комментарий',
  `history` text NOT NULL COMMENT 'Записная кинжка'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tech_models`
--

CREATE TABLE `tech_models` (
  `id` int(11) NOT NULL COMMENT 'id',
  `type_id` int(11) NOT NULL COMMENT 'Тип оборудования',
  `manufacturers_id` int(11) NOT NULL COMMENT 'Производитель',
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Модель',
  `short` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Короткое имя',
  `links` text COLLATE utf8mb4_unicode_ci COMMENT 'Ссылки',
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Комментарий'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tech_states`
--

CREATE TABLE `tech_states` (
  `id` int(11) NOT NULL,
  `code` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `descr` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tech_types`
--

CREATE TABLE `tech_types` (
  `id` int(11) NOT NULL COMMENT 'id',
  `code` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Код',
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название',
  `prefix` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Префикс инв. номера',
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Комментарий'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` varchar(16) NOT NULL COMMENT 'Табельный номер',
  `Orgeh` varchar(16) NOT NULL COMMENT 'Подразделение (id)',
  `Doljnost` varchar(255) NOT NULL COMMENT 'Должность',
  `Ename` varchar(255) NOT NULL COMMENT 'Полное имя',
  `Persg` int(11) NOT NULL DEFAULT '1',
  `Uvolen` tinyint(1) NOT NULL COMMENT 'Уволен',
  `Login` varchar(255) NOT NULL COMMENT 'Логин',
  `Email` varchar(64) DEFAULT NULL,
  `Phone` varchar(32) DEFAULT NULL,
  `Mobile` varchar(128) DEFAULT NULL,
  `work_phone` varchar(32) DEFAULT NULL,
  `Bday` varchar(16) DEFAULT NULL,
  `manager_id` varchar(16) DEFAULT NULL,
  `nosync` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `arms`
--
ALTER TABLE `arms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `num` (`num`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `head_id` (`head_id`),
  ADD KEY `responsible_id` (`responsible_id`),
  ADD KEY `it_staff_id` (`it_staff_id`),
  ADD KEY `places_id` (`places_id`),
  ADD KEY `state_id` (`state_id`);

--
-- Индексы таблицы `comps`
--
ALTER TABLE `comps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domainname` (`domain_id`,`name`),
  ADD KEY `arm_id` (`arm_id`);

--
-- Индексы таблицы `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent` (`parent_id`),
  ADD KEY `name` (`name`),
  ADD KEY `dateFrom` (`date`),
  ADD KEY `dateTo` (`end_date`);

--
-- Индексы таблицы `contracts_in_arms`
--
ALTER TABLE `contracts_in_arms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contracts_id` (`contracts_id`),
  ADD KEY `arms_id` (`arms_id`);

--
-- Индексы таблицы `contracts_in_lics`
--
ALTER TABLE `contracts_in_lics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contracts_id` (`contracts_id`),
  ADD KEY `lics_id` (`lics_id`);

--
-- Индексы таблицы `contracts_in_techs`
--
ALTER TABLE `contracts_in_techs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contracts_id` (`contracts_id`),
  ADD KEY `tech_id` (`techs_id`);

--
-- Индексы таблицы `domains`
--
ALTER TABLE `domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`,`fqdn`);

--
-- Индексы таблицы `hw_ignore`
--
ALTER TABLE `hw_ignore`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fingerprint` (`fingerprint`);

--
-- Индексы таблицы `lic_groups`
--
ALTER TABLE `lic_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lic_types_id` (`lic_types_id`);

--
-- Индексы таблицы `lic_groups_in_arms`
--
ALTER TABLE `lic_groups_in_arms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `arms_id` (`arms_id`),
  ADD KEY `lics_id` (`lics_id`);

--
-- Индексы таблицы `lic_items`
--
ALTER TABLE `lic_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lic_group_id` (`lic_group_id`);

--
-- Индексы таблицы `lic_items_in_arms`
--
ALTER TABLE `lic_items_in_arms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `arms_id` (`arms_id`),
  ADD KEY `lics_id` (`lics_id`);

--
-- Индексы таблицы `lic_types`
--
ALTER TABLE `lic_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `login_journal`
--
ALTER TABLE `login_journal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comps_id` (`comps_id`),
  ADD KEY `users_id` (`users_id`);

--
-- Индексы таблицы `manufacturers`
--
ALTER TABLE `manufacturers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `name_2` (`name`);

--
-- Индексы таблицы `manufacturers_dict`
--
ALTER TABLE `manufacturers_dict`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `word` (`word`),
  ADD KEY `manufacturers_id` (`manufacturers_id`);

--
-- Индексы таблицы `org_inet`
--
ALTER TABLE `org_inet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `places_id` (`places_id`),
  ADD KEY `prov_tel_id` (`prov_tel_id`),
  ADD KEY `contracts_id` (`contracts_id`);

--
-- Индексы таблицы `org_phones`
--
ALTER TABLE `org_phones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_code` (`country_code`),
  ADD KEY `city_code` (`city_code`),
  ADD KEY `local_code` (`local_code`),
  ADD KEY `prov_tel_id` (`prov_tel_id`),
  ADD KEY `places_id` (`places_id`);

--
-- Индексы таблицы `org_struct`
--
ALTER TABLE `org_struct`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pup` (`pup`);

--
-- Индексы таблицы `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inn` (`inn`),
  ADD KEY `kpp` (`kpp`),
  ADD KEY `uname` (`uname`(191)),
  ADD KEY `bname` (`bname`(191));

--
-- Индексы таблицы `partners_in_contracts`
--
ALTER TABLE `partners_in_contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partners_id` (`partners_id`),
  ADD KEY `contracts_id` (`contracts_id`);

--
-- Индексы таблицы `places`
--
ALTER TABLE `places`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent` (`parent_id`);

--
-- Индексы таблицы `prov_tel`
--
ALTER TABLE `prov_tel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Индексы таблицы `scans`
--
ALTER TABLE `scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contracts_id` (`contracts_id`);

--
-- Индексы таблицы `soft`
--
ALTER TABLE `soft`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manufacturer_id` (`manufacturers_id`);

--
-- Индексы таблицы `soft_hits`
--
ALTER TABLE `soft_hits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comp_id` (`comp_id`),
  ADD KEY `soft_id` (`soft_id`);

--
-- Индексы таблицы `soft_in_comps`
--
ALTER TABLE `soft_in_comps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `soft_id_idx` (`soft_id`),
  ADD KEY `comp_id_idx` (`comp_id`);

--
-- Индексы таблицы `soft_in_lics`
--
ALTER TABLE `soft_in_lics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `soft_id` (`soft_id`),
  ADD KEY `lics_id` (`lics_id`);

--
-- Индексы таблицы `soft_in_lists`
--
ALTER TABLE `soft_in_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `soft_id` (`soft_id`,`list_id`);

--
-- Индексы таблицы `soft_lists`
--
ALTER TABLE `soft_lists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `techs`
--
ALTER TABLE `techs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `num` (`num`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `it_staff_id` (`it_staff_id`),
  ADD KEY `places_id` (`places_id`),
  ADD KEY `arms_id` (`arms_id`),
  ADD KEY `ip` (`ip`),
  ADD KEY `state_id` (`state_id`),
  ADD KEY `mac` (`mac`);

--
-- Индексы таблицы `tech_models`
--
ALTER TABLE `tech_models`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `manufacturers_id` (`manufacturers_id`),
  ADD KEY `short` (`short`);

--
-- Индексы таблицы `tech_states`
--
ALTER TABLE `tech_states`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tech_types`
--
ALTER TABLE `tech_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `code` (`code`),
  ADD KEY `name` (`name`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `struct_id` (`Orgeh`),
  ADD KEY `dismissed` (`Uvolen`),
  ADD KEY `Persg` (`Persg`),
  ADD KEY `nosync` (`nosync`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `arms`
--
ALTER TABLE `arms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор';

--
-- AUTO_INCREMENT для таблицы `comps`
--
ALTER TABLE `comps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор';

--
-- AUTO_INCREMENT для таблицы `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- AUTO_INCREMENT для таблицы `contracts_in_arms`
--
ALTER TABLE `contracts_in_arms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `contracts_in_lics`
--
ALTER TABLE `contracts_in_lics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `contracts_in_techs`
--
ALTER TABLE `contracts_in_techs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `domains`
--
ALTER TABLE `domains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор';

--
-- AUTO_INCREMENT для таблицы `hw_ignore`
--
ALTER TABLE `hw_ignore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- AUTO_INCREMENT для таблицы `lic_groups`
--
ALTER TABLE `lic_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор';

--
-- AUTO_INCREMENT для таблицы `lic_groups_in_arms`
--
ALTER TABLE `lic_groups_in_arms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `lic_items`
--
ALTER TABLE `lic_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор';

--
-- AUTO_INCREMENT для таблицы `lic_items_in_arms`
--
ALTER TABLE `lic_items_in_arms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `lic_types`
--
ALTER TABLE `lic_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор';

--
-- AUTO_INCREMENT для таблицы `login_journal`
--
ALTER TABLE `login_journal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- AUTO_INCREMENT для таблицы `manufacturers`
--
ALTER TABLE `manufacturers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор';

--
-- AUTO_INCREMENT для таблицы `manufacturers_dict`
--
ALTER TABLE `manufacturers_dict`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `org_inet`
--
ALTER TABLE `org_inet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- AUTO_INCREMENT для таблицы `org_phones`
--
ALTER TABLE `org_phones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- AUTO_INCREMENT для таблицы `partners_in_contracts`
--
ALTER TABLE `partners_in_contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `places`
--
ALTER TABLE `places`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- AUTO_INCREMENT для таблицы `prov_tel`
--
ALTER TABLE `prov_tel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- AUTO_INCREMENT для таблицы `scans`
--
ALTER TABLE `scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- AUTO_INCREMENT для таблицы `soft`
--
ALTER TABLE `soft`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор';

--
-- AUTO_INCREMENT для таблицы `soft_in_comps`
--
ALTER TABLE `soft_in_comps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- AUTO_INCREMENT для таблицы `soft_in_lics`
--
ALTER TABLE `soft_in_lics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `soft_in_lists`
--
ALTER TABLE `soft_in_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- AUTO_INCREMENT для таблицы `soft_lists`
--
ALTER TABLE `soft_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- AUTO_INCREMENT для таблицы `techs`
--
ALTER TABLE `techs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор';

--
-- AUTO_INCREMENT для таблицы `tech_models`
--
ALTER TABLE `tech_models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- AUTO_INCREMENT для таблицы `tech_states`
--
ALTER TABLE `tech_states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `tech_types`
--
ALTER TABLE `tech_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `comps`
--
ALTER TABLE `comps`
  ADD CONSTRAINT `arms` FOREIGN KEY (`arm_id`) REFERENCES `arms` (`id`),
  ADD CONSTRAINT `domains` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`);

--
-- Ограничения внешнего ключа таблицы `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `contracts` (`id`);

--
-- Ограничения внешнего ключа таблицы `contracts_in_arms`
--
ALTER TABLE `contracts_in_arms`
  ADD CONSTRAINT `contracts_in_arms_ibfk_1` FOREIGN KEY (`arms_id`) REFERENCES `arms` (`id`);

--
-- Ограничения внешнего ключа таблицы `lic_items`
--
ALTER TABLE `lic_items`
  ADD CONSTRAINT `lic_groups` FOREIGN KEY (`lic_group_id`) REFERENCES `lic_groups` (`id`);

--
-- Ограничения внешнего ключа таблицы `lic_items_in_arms`
--
ALTER TABLE `lic_items_in_arms`
  ADD CONSTRAINT `lic_items_in_arms_ibfk_1` FOREIGN KEY (`arms_id`) REFERENCES `arms` (`id`);

--
-- Ограничения внешнего ключа таблицы `login_journal`
--
ALTER TABLE `login_journal`
  ADD CONSTRAINT `login_journal_ibfk_1` FOREIGN KEY (`comps_id`) REFERENCES `comps` (`id`);

--
-- Ограничения внешнего ключа таблицы `manufacturers_dict`
--
ALTER TABLE `manufacturers_dict`
  ADD CONSTRAINT `manufacturers` FOREIGN KEY (`manufacturers_id`) REFERENCES `manufacturers` (`id`);

--
-- Ограничения внешнего ключа таблицы `org_inet`
--
ALTER TABLE `org_inet`
  ADD CONSTRAINT `org_inet_ibfk_1` FOREIGN KEY (`places_id`) REFERENCES `places` (`id`),
  ADD CONSTRAINT `org_inet_ibfk_2` FOREIGN KEY (`prov_tel_id`) REFERENCES `prov_tel` (`id`);

--
-- Ограничения внешнего ключа таблицы `org_phones`
--
ALTER TABLE `org_phones`
  ADD CONSTRAINT `org_phones_ibfk_1` FOREIGN KEY (`prov_tel_id`) REFERENCES `prov_tel` (`id`);

--
-- Ограничения внешнего ключа таблицы `scans`
--
ALTER TABLE `scans`
  ADD CONSTRAINT `scans_ibfk_1` FOREIGN KEY (`contracts_id`) REFERENCES `contracts` (`id`);

--
-- Ограничения внешнего ключа таблицы `soft`
--
ALTER TABLE `soft`
  ADD CONSTRAINT `manufacturers_id` FOREIGN KEY (`manufacturers_id`) REFERENCES `manufacturers` (`id`);

--
-- Ограничения внешнего ключа таблицы `soft_hits`
--
ALTER TABLE `soft_hits`
  ADD CONSTRAINT `comp_id_restr` FOREIGN KEY (`comp_id`) REFERENCES `comps` (`id`),
  ADD CONSTRAINT `soft_id_restr` FOREIGN KEY (`soft_id`) REFERENCES `soft` (`id`);

--
-- Ограничения внешнего ключа таблицы `soft_in_lics`
--
ALTER TABLE `soft_in_lics`
  ADD CONSTRAINT `soft_in_lics_ibfk_1` FOREIGN KEY (`soft_id`) REFERENCES `soft` (`id`),
  ADD CONSTRAINT `soft_in_lics_ibfk_2` FOREIGN KEY (`lics_id`) REFERENCES `lic_groups` (`id`);

--
-- Ограничения внешнего ключа таблицы `techs`
--
ALTER TABLE `techs`
  ADD CONSTRAINT `techs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `techs_ibfk_2` FOREIGN KEY (`it_staff_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `techs_ibfk_3` FOREIGN KEY (`arms_id`) REFERENCES `arms` (`id`),
  ADD CONSTRAINT `techs_ibfk_4` FOREIGN KEY (`places_id`) REFERENCES `places` (`id`),
  ADD CONSTRAINT `techs_ibfk_5` FOREIGN KEY (`state_id`) REFERENCES `tech_states` (`id`);
COMMIT;
SQL;
	    $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $sql= /** @lang MySQL */
			<<<SQL
		DROP TABLE IF EXISTS arms;
		DROP TABLE IF EXISTS comps;
		DROP TABLE IF EXISTS contracts;
		DROP TABLE IF EXISTS contracts_in_arms;
		DROP TABLE IF EXISTS contracts_in_lics;
		DROP TABLE IF EXISTS contracts_in_techs;
		DROP TABLE IF EXISTS domains;
		DROP TABLE IF EXISTS hw_ignore;
		DROP TABLE IF EXISTS lic_groups;
		DROP TABLE IF EXISTS lic_groups_in_arms;
		DROP TABLE IF EXISTS lic_items;
		DROP TABLE IF EXISTS lic_items_in_arms;
		DROP TABLE IF EXISTS lic_types;
		DROP TABLE IF EXISTS login_journal;
		DROP TABLE IF EXISTS manufacturers;
		DROP TABLE IF EXISTS manufacturers_dict;
		DROP TABLE IF EXISTS org_inet;
		DROP TABLE IF EXISTS org_phones;
		DROP TABLE IF EXISTS org_struct;
		DROP TABLE IF EXISTS partners;
		DROP TABLE IF EXISTS partners_in_contracts;
		DROP TABLE IF EXISTS places;
		DROP TABLE IF EXISTS prov_tel;
		DROP TABLE IF EXISTS scans;
		DROP TABLE IF EXISTS soft;
		DROP TABLE IF EXISTS soft_hits;
		DROP TABLE IF EXISTS soft_in_comps;
		DROP TABLE IF EXISTS soft_in_lics;
		DROP TABLE IF EXISTS soft_in_lists;
		DROP TABLE IF EXISTS soft_lists;
		DROP TABLE IF EXISTS techs;
		DROP TABLE IF EXISTS tech_models;
		DROP TABLE IF EXISTS tech_states;
		DROP TABLE IF EXISTS tech_types;
		DROP TABLE IF EXISTS users;
SQL;
	    $this->execute($sql);
        }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191123_164814_initial cannot be reverted.\n";

        return false;
    }
    */
}
