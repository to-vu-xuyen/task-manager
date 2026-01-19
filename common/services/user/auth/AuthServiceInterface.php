<?php
namespace common\services\user\auth;

/**
 * AuthServiceInterface - Contract cho Authentication operations
 * 
 * Chỉ xử lý: Login, Logout, Session management
 * KHÔNG xử lý: User creation (thuộc UserService)
 */
interface AuthServiceInterface
{
    /**
     * Đăng nhập user
     * @param \common\forms\user\UserLoginForm $form
     * @return bool
     */
    public function login(\common\forms\user\UserLoginForm $form): bool;
    
    /**
     * Đăng xuất user hiện tại
     */
    public function logout(): void;
}
