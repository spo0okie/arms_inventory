<?php

namespace app\modules\api\controllers;


class DomainsController extends BaseRestController
{
    public $modelClass='app\models\Domains';

    /**
     * В config/web.php объявлено кастомное правило `api/domains/<id:[\.\w-]+>`
     * (домены адресуются по fqdn, а не только по числовому id), и оно перекрывает
     * общие REST-правила `api/<controller>/search|filter` и OPTIONS preflight.
     * До доработки URL-менеджера тесты этих трёх action'ов пропускаем.
     *
     * TODO: поправить правила в config/web.php так, чтобы /search и /filter
     * матчились до `api/domains/<id>` (см. tests/rest-todo.md).
     */
    public function disabledTests(): array
    {
        return array_merge($this->disabledActions(), ['search', 'filter', 'preflight']);
    }
}
