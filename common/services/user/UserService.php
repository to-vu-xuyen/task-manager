<?php
namespace common\services\user;

use common\models\User;
use common\forms\user\UserCreateForm;
use common\services\user\create\CreateByUser;
use common\services\user\create\CreateByAdmin;

/**
 * UserService - Facade cho User Management
 * 
 * Facade pattern: 
 * - Controller chỉ cần biết UserService
 * - Không cần biết chi tiết CreateByAdmin, CreateByUser bên trong
 * 
 * Ví dụ sử dụng:
 *   $userService = new UserService();
 *   $user = $userService->createUser($form, 'user');  // Signup
 *   $user = $userService->createUser($form, 'admin'); // Admin creates
 */
class UserService implements UserServiceInterface
{
    /**
     * Tạo user mới
     * 
     * @param UserCreateForm $form Form data từ input
     * @param string $creationType 'user' hoặc 'admin'
     * @return User|null User đã tạo, null nếu form không valid
     * @throws \RuntimeException Nếu save thất bại
     */
    public function createUser(UserCreateForm $form, string $creationType = 'user'): ?User
    {
        if (!$form->validate()) {
            return null;
        }
        
        // Chọn strategy dựa trên creationType
        $createService = match ($creationType) {
            'admin' => new CreateByAdmin(),
            default => new CreateByUser(),
        };
        
        return $createService->create($form);
    }
    
    /**
     * Tìm user theo ID
     */
    public function findById(int $id): ?User
    {
        return User::findOne(['id' => $id]);
    }
    
    /**
     * Tìm user theo username
     */
    public function findByUsername(string $username): ?User
    {
        return User::findByUsername($username);
    }
}
