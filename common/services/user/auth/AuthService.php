<?php
namespace common\services\user\auth;

use Yii;
use common\models\User;
use common\forms\user\UserLoginForm;

/**
 * AuthService - Xử lý Authentication (Login/Logout)
 * 
 * Trách nhiệm:
 * - Login: Xác thực credentials và tạo session
 * - Logout: Xóa session
 * 
 * KHÔNG xử lý: User creation (đã tách sang UserService)
 */
class AuthService implements AuthServiceInterface
{
    /**
     * Đăng nhập user
     * 
     * @param UserLoginForm $form Form chứa username/password
     * @return bool True nếu login thành công
     */
    public function login(UserLoginForm $form): bool
    {
        if (!$form->validate()) {
            return false;
        }
        
        $user = User::findByUsername($form->username);
        if (!$user || !$user->validatePassword($form->password)) {
            return false;
        }
        
        // rememberMe: 30 ngày nếu checked, 0 = session only
        $duration = $form->rememberMe ? 3600 * 24 * 30 : 0;
        
        return Yii::$app->user->login($user, $duration);
    }

    /**
     * Đăng xuất user hiện tại
     */
    public function logout(): void
    {
        Yii::$app->user->logout();
    }
}
