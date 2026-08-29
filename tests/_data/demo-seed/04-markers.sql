-- Демо-данные, этап 4: цветовые маркеры (см. tests/_data/readme.md, issue #141).
--
-- Справочник из 32 маркеров приезжает миграцией, но покрашены им были только
-- состояния оборудования, состояния документов и сегменты. Категории
-- оборудования видны в каждом гриде - их цвет заметнее всего; L2-домены
-- красим для карты рабочих мест и IPAM.
--
-- Дата у tech_types задаётся явно: там ON UPDATE CURRENT_TIMESTAMP.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Категории оборудования
-- ---------------------------------------------------------------------------
UPDATE `tech_types` SET `marker_id`=64, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='pc';           -- светло-синий
UPDATE `tech_types` SET `marker_id`=60, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='laptop';       -- светло-зелёный
UPDATE `tech_types` SET `marker_id`=80, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='aio_pc';       -- оливково-жёлтый
UPDATE `tech_types` SET `marker_id`=52, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='srv';          -- ярко-синий
UPDATE `tech_types` SET `marker_id`=75, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='nas';          -- бежевый
UPDATE `tech_types` SET `marker_id`=51, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='net_switch';   -- голубой
UPDATE `tech_types` SET `marker_id`=58, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='net_router';   -- фиолетовый
UPDATE `tech_types` SET `marker_id`=77, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='wifi_ap';      -- бирюзовый
UPDATE `tech_types` SET `marker_id`=76, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='wifi_router';  -- светло-оливковый
UPDATE `tech_types` SET `marker_id`=74, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='voip_phone';   -- жёлто-зелёный
UPDATE `tech_types` SET `marker_id`=61, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='voip_gw';      -- лаймовый
UPDATE `tech_types` SET `marker_id`=70, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='mfu_bw_a3';    -- палевый
UPDATE `tech_types` SET `marker_id`=71, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='scan';         -- светло-жёлтый
UPDATE `tech_types` SET `marker_id`=59, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='ups';          -- жёлтый
UPDATE `tech_types` SET `marker_id`=53, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='display';      -- серый
UPDATE `tech_types` SET `marker_id`=63, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='dvr_cam';      -- розовый
UPDATE `tech_types` SET `marker_id`=78, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='vid_reg';      -- тёмно-серый
UPDATE `tech_types` SET `marker_id`=79, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='skud';         -- хаки
UPDATE `tech_types` SET `marker_id`=57, `updated_at`='2026-07-13 09:00:00', `updated_by`='admin' WHERE `code`='Racks';        -- серый с оранжевым текстом

-- ---------------------------------------------------------------------------
-- L2-домены: цвет виден на карте рабочих мест и в IPAM
-- ---------------------------------------------------------------------------
UPDATE `net_domains` SET `marker_id`=52 WHERE `name`='msk_dom';
UPDATE `net_domains` SET `marker_id`=61 WHERE `name`='chl_dom';

-- ---------------------------------------------------------------------------
-- Авторство правок в самом справочнике маркеров
-- ---------------------------------------------------------------------------
UPDATE `markers` SET `updated_at`='2026-07-13 08:50:00', `updated_by`='admin'
	WHERE `id` IN (51,52,53,54,55,56);
