<?php
namespace common\services\user\auth;

use Yii;
use common\models\User;
use common\forms\user\UserLoginForm;

/**
 * AuthService - Xử lý Authentication (Login/Logout)
 * 
 * Trách nhiệm:
 * - Validate credentials (business logic)
 * - Tạo/xóa session
 */
class AuthService implements AuthServiceInterface {
    
    /**
     * Đăng nhập user
     * 
     * @param UserLoginForm $form Form chứa username/password
     * @return bool True nếu login thành công
     */
    public function login(UserLoginForm $form): bool {
        if (!$form->validate()) {
            return false;
        }
        
        $user = User::findByUsername($form->username);
        
        
        if (!$user || !$user->validatePassword($form->password)) {
            $form->addError('password', 'Sai tên đăng nhập hoặc mật khẩu');
            return false;
        }
        
        // Tạo session
        $duration = $form->rememberMe ? 3600 * 24 * 30 : 0;
        
        return Yii::$app->user->login($user, $duration);
    }

    /**
     * Đăng xuất user hiện tại
     */
    public function logout(): void {
        Yii::$app->user->logout();
    }
}
