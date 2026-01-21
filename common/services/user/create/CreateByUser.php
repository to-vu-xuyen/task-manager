<?php
namespace common\services\user\create;

use Yii;
use common\services\user\create\AbstractCreateUser;

/**
 * CreateByUser - Tạo user khi tự đăng ký (Signup)
 * 
 * User tự đăng ký luôn được gán role 'user'
 */
class CreateByUser extends AbstractCreateUser
{
    /**
     * Gán role 'user' mặc định cho người tự đăng ký
     */
    protected function assignRole(): void
    {
        $auth = Yii::$app->authManager;
        
        $role = $auth->getRole('user');
        if ($role === null) {
            throw new \RuntimeException("Role 'user' not found");
        }
        
        $auth->assign($role, $this->user->id);
    }
}
