<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Captcha;
use App\Models\SystemConfig;
use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    /**
     * 生成验证码
     */
    public function generate()
    {
        // 获取验证码长度配置
        $length = (int) SystemConfig::getValue('captcha_length', 4);
        $length = max(3, min(6, $length));
        
        // 生成验证码并获取key
        $captchaKey = Captcha::generate($length);
        
        // 生成图片
        $code = Captcha::where('key', $captchaKey)->first()->code;
        $image = $this->createCaptchaImage($code);
        
        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Captcha-Key' => $captchaKey,
        ]);
    }

    /**
     * 验证验证码
     */
    public function verify(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'code' => 'required|string',
        ]);

        $result = Captcha::verify($request->input('key'), $request->input('code'));
        
        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        }
        
        return $this->success(true, 'Captcha verified');
    }

    /**
     * 创建验证码图片
     */
    private function createCaptchaImage($text)
    {
        $width = 120;
        $height = 40;
        
        $image = imagecreatetruecolor($width, $height);
        
        // 颜色
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $lineColor = imagecolorallocate($image, 200, 200, 200);
        
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // 干扰线
        for ($i = 0; $i < 5; $i++) {
            imageline($image, 0, mt_rand(0, $height), $width, mt_rand(0, $height), $lineColor);
        }
        
        // 干扰点
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($image, mt_rand(0, $width), mt_rand(0, $height), $lineColor);
        }
        
        // 绘制文字
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $x = ($width - $textWidth) / 2;
        $y = ($height - imagefontheight($fontSize)) / 2;
        imagestring($image, $fontSize, $x, $y, $text, $textColor);
        
        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);
        
        return $data;
    }
}
