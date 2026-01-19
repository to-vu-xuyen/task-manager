<?php
namespace common\services\user\create;

use Yii;
use common\services\user\AbstractCreateUser;

/**
 * CreateByAdmin - Tạo user bởi Admin
 * 
 * Admin có thể chọn role từ form input
 * Default role: 'user' nếu không chọn
 */
class CreateByAdmin extends AbstractCreateUser
{
    /**
     * Gán role do admin chọn từ form
     */
    protected function assignRole(): void
    {
        $auth = Yii::$app->authManager;
        
        // Lấy role từ form input, default = 'user'
        $roleName = $this->form->role ?? 'user';
        
        $role = $auth->getRole($roleName);
        if ($role === null) {
            throw new \RuntimeException("Role '{$roleName}' not found");
        }
        
        $auth->assign($role, $this->user->id);
    }
}
