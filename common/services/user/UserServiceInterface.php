<?php
namespace common\services\user;

use common\models\User;
use common\forms\user\UserCreateForm;

/**
 * UserServiceInterface - Contract cho User Management operations
 * 
 * Facade pattern: Cung cấp interface đơn giản cho các operations phức tạp bên trong
 */
interface UserServiceInterface
{
    /**
     * Tạo user mới (signup hoặc admin tạo)
     * 
     * @param UserCreateForm $form Form data
     * @param string $creationType 'user' = tự đăng ký, 'admin' = admin tạo
     * @return User|null User đã tạo hoặc null nếu validation thất bại
     */
    public function createUser(UserCreateForm $form, string $creationType = 'user'): ?User;
    
    /**
     * Tìm user theo ID
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User;
    
    /**
     * Tìm user theo username
     * @param string $username
     * @return User|null
     */
    public function findByUsername(string $username): ?User;
}
