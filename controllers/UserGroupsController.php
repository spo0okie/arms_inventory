<?php

namespace app\controllers;

use app\models\UserGroups;

/**
 * UserGroupsController implements the CRUD actions for UserGroups model.
 */
class UserGroupsController extends ArmsBaseController
{
	/**
	 * Returns disabled acceptance tests list.
	 */
	public function disabledTests(): array
	{
		return ['*'];
	}
	public $modelClass=UserGroups::class;
}
