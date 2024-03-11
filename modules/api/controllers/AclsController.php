<?php

namespace app\modules\api\controllers;




use app\models\Acls;

class AclsController extends BaseRestController
{
    public $modelClass=Acls::class;
}
