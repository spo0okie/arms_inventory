<?php
namespace app\migrations;
use Yii;
use yii\db\Migration;

/**
 * Class m200712_185556_add_permissions
 */
class m200712_185556_add_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$rbac = Yii::$app->authManager;
    	
    	echo "creating permissions ... \n";
    	$permissions=$rbac->getPermissions();
    	foreach ([
			'view' => 'Просмотр всех объектов',
			'edit' => 'Редактирование всех объектов',
			'acl' => 'Управление правами доступа'
		] as $permName=>$description) {
    		echo $permName."\n";
			if (!isset($permissions[$permName])) {
				$permission=$rbac->createPermission($permName);
				$permission->description=$description;
				$rbac->add($permission);
			}
		}
	
		echo "creating roles ... \n";
		$roles=$rbac->getRoles();
		foreach ([
			'viewer' => ['descr'=>'Может только просматривать данные','perms'=>['view']],
			'editor' => ['descr'=>'Может просматривать и редактировать данные','perms'=>['view','edit']],
			'admin' => ['descr'=>'Управление данными и правами доступа к ним','perms'=>['view','edit','acl']],
		] as $roleName=>$data) {
			echo $roleName." - check\n";
			if (!isset($roles[$roleName])) {
				$role = $rbac->createRole($roleName);
				$role->description = $data['descr'];
				$rbac->add($role);
			} else $role=$roles[$roleName];
			
			$permissions=$rbac->getPermissionsByRole($roleName);
			foreach ($data['perms'] as $permName) {
				echo $roleName." - $permName check\n";
				if (!isset($permissions[$permName])) {
					$permission=$rbac->getPermission($permName);
					$rbac->addChild($role,$permission);
				}
			}
		}
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "Remove all needed permissions manually.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200712_185556_add_permissions cannot be reverted.\n";

        return false;
    }
    */
}
