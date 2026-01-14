<?php

namespace common\services\user;

interface AuthServiceInterface{
	public function login(UserLoginForm $form): bool;
    public function signup(UserSignupForm $form): ?User;
    public function logout(): void;
}