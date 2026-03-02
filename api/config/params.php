<?php
return [
    'jwt' => [
        'accessTokenExpire'  => 3600,      // 1 giờ
        'refreshTokenExpire' => 604800,    // 7 ngày
    ],
    'rateLimit' => [
        'general' => [60, 60],   // 60 requests / 60 giây
        'auth'    => [5, 60],    // 5 requests / 60 giây (login/refresh)
    ],
    // CORS — danh sách origins được phép
    // Production: thay bằng domain thực tế
    'cors.allowedOrigins' => [
        'http://localhost',
        'http://localhost:8080/task-manager',
    ],
];