<?php

use yii\db\Migration;

class m251229_040225_init_rbac extends Migration
{

    private $permissions = [
        'rbac.manage',
        'system.config',
        'system.view_logs',


        'user.view',
        'user.create',
        'user.update',
        'user.deactivate',
        'user.assign_role',

        'task.view_all',
        'task.delete',

        'task.view',
        'task.create',
        'task.update',
        'task.assign',
        'task.change_status',
        'task.set_priority',
        'task.set_due_date',

        'attachment.upload',
        'attachment.download',
        'activity.view',
    ];
    private $admin_permissions = [
        'rbac.manage',
        'system.config',
        'system.view_logs',

        'user.create',
        'user.update',
        'user.deactivate',
        'user.assign_role',

        'task.view_all',
        'task.delete',

        'activity.view',
    ];
    // private $manager_permission = [
    //     'task.view_all',
    //     'task.create',
    //     'task.update',
    //     'task.assign',
    //     'task.set_priority',
    //     'task.set_due_date',
    //     'user.view',
    //     'activity.view',
    // ];
    private $user_permissions = [

        'task.view',
        'task.create',
        'task.update',
        'task.assign',
        'task.change_status',
        'task.set_priority',
        'task.set_due_date',

        'user.view',
        'user.create',
        'user.update',
        'user.deactivate',

        'attachment.upload',
        'attachment.download',

        'activity.view',
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = \Yii::$app->authManager;
        $auth->removeAll(); // reset an toàn khi init


        // $permissions = [
        //     // task
        //     'task.view',
        //     'task.view_all',
        //     'task.create',
        //     'task.update',
        //     'task.assign',
        //     'task.change_status',
        //     'task.set_priority',
        //     'task.set_due_date',
        //     'task.delete',

        //     // attachment
        //     'attachment.upload',
        //     'attachment.download',

        //     // user
        //     'user.view',
        //     'user.create',
        //     'user.update',
        //     'user.deactivate',
        //     'user.assign_role',

        //     // system
        //     'activity.view',
        //     'rbac.manage',
        //     'system.view_logs',
        //     'system.config',
        // ];

        $permission_arr = [];
        foreach ($this->permissions as $name) {
            $auth_permission = $auth->createPermission($name);
            $auth->add($auth_permission);
            $permission_arr[$name] = $auth_permission;
        }


        // role manager
        $admin = $auth->createRole('admin');
        // $manager = $auth->createRole('manager');
        $user = $auth->createRole('user');

        $auth->add($admin);
        // $auth->add($manager);
        $auth->add($user);


        foreach ($this->user_permissions as $value) {
            $auth->addChild($user, $permission_arr[$value]);
        }

        $auth->addChild($admin, $user);
        foreach ($this->admin_permissions as $value) {
            $auth->addChild($admin, $permission_arr[$value]);
        }

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251229_040225_init_rbac cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251229_040225_init_rbac cannot be reverted.\n";

        return false;
    }
    */
}
