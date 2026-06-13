<?php
// JWT配置

return [
    // JWT密钥
    'secret' => 'erp_jwt_secret_key_2026',
    // 加密算法
    'algorithm' => 'HS256',
    // 过期时间（秒）
    'expire' => 86400 * 7,
    // Token前缀
    'prefix' => 'Bearer',
    // 用户标识字段
    'user_id' => 'id',
];
