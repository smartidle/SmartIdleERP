<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Captcha extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'code', 'expires_at', 'attempts'];

    /**
     * 生成验证码
     */
    public static function generate($length = 4)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        $key = 'c_' . uniqid() . time();
        
        self::create([
            'key' => $key,
            'code' => strtolower($code),
            'expires_at' => time() + 300,
            'attempts' => 0,
        ]);

        return $key;
    }

    /**
     * 验证验证码
     */
    public static function verify($key, $code)
    {
        $captcha = self::where('key', $key)->first();
        
        if (!$captcha) {
            return ['valid' => false, 'error' => 'Captcha not found'];
        }

        if ($captcha->expires_at < time()) {
            $captcha->delete();
            return ['valid' => false, 'error' => 'Captcha expired'];
        }

        if ($captcha->attempts >= 5) {
            $captcha->delete();
            return ['valid' => false, 'error' => 'Too many attempts'];
        }

        // 增加尝试次数
        $captcha->attempts++;
        $captcha->save();

        if ($captcha->code !== strtolower($code)) {
            return ['valid' => false, 'error' => 'Invalid captcha'];
        }

        // 验证成功，删除验证码
        $captcha->delete();
        
        return ['valid' => true];
    }

    /**
     * 清理过期验证码
     */
    public static function cleanup()
    {
        self::where('expires_at', '<', time())->delete();
    }
}
