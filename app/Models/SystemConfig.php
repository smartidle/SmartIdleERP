<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $table = 'system_configs';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'options',
        'sort',
        'status',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * 根据key获取配置值
     */
    public static function getValue(string $key, $default = null)
    {
        $config = self::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * 根据分组获取配置
     */
    public static function getByGroup(string $group)
    {
        return self::where('group', $group)
            ->where('status', 1)
            ->orderBy('sort')
            ->get();
    }

    /**
     * 批量设置配置
     */
    public static function setValues(array $values)
    {
        foreach ($values as $key => $value) {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    /**
     * 获取所有配置（键值对形式）
     */
    public static function getAllKeyValues(): array
    {
        $configs = self::where('status', 1)->get();
        $result = [];
        foreach ($configs as $config) {
            $result[$config->key] = $config->value;
        }
        return $result;
    }
}
