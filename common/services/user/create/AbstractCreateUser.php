<?php
namespace common\services\user\create;

use common\models\User;
use common\forms\user\UserCreateForm;

/**
 * AbstractCreateUser - Base class cho User Creation (Template Method Pattern)
 * 
 * Flow: create() → loadData() → beforeSave() → validate() → save() → assignRole() → afterSave()
 * 
 * Child classes chỉ cần override assignRole() để gán role khác nhau
 */
abstract class AbstractCreateUser
{
    protected User $user;
    protected UserCreateForm $form;

    /**
     * Factory method để tạo User instance
     * Override nếu cần custom User subclass
     */
    protected function createUserInstance(): User
    {
        return new User();
    }

    /**
     * Tạo user mới
     * 
     * @param UserCreateForm $form Form data từ input
     * @return User User đã được tạo và lưu
     * @throws \RuntimeException Nếu validation hoặc save thất bại
     */
    public function create(UserCreateForm $form): User
    {
        $this->form = $form;
        $this->user = $this->createUserInstance();
        
        $this->loadData();
        $this->beforeSave();
        
        if (!$this->user->validate()) {
            $errors = json_encode($this->user->getErrors());
            throw new \RuntimeException("User validation failed: {$errors}");
        }
        
        if (!$this->user->save()) {
            throw new \RuntimeException('Cannot save user');
        }
        
        $this->assignRole();
        $this->afterSave();

        return $this->user;
    }

    /**
     * Load data từ form vào User model
     */
    protected function loadData(): void
    {
        $this->user->username = $this->form->username;
        $this->user->email = $this->form->email;
        $this->user->setPassword($this->form->password);
        $this->user->generateAuthKey();
    }

    /**
     * Hook: Chạy trước khi save
     * Override để thêm logic (vd: set status, timestamps...)
     */
    protected function beforeSave(): void {}

    /**
     * Hook: Chạy sau khi save
     * Override để thêm logic (vd: send email, log...)
     */
    protected function afterSave(): void {}

    /**
     * Gán role cho user
     * Must be implemented by child classes
     */
    abstract protected function assignRole(): void;
}
