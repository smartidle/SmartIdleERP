<?php

namespace app\model;

/**
 * 系统配置模型
 */
class SystemConfig extends \think\Model
{
    // 表名
    protected $name = 'system_config';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 获取配置值
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $config = self::where('key', $key)->find();
        return $config ? $config->value : $default;
    }

    /**
     * 设置配置值
     *
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @param string $group 分组
     * @param string $description 描述
     * @return bool
     */
    public static function set(string $key, $value, string $group = 'basic', string $description = ''): bool
    {
        $config = self::where('key', $key)->find();

        if ($config) {
            $config->value = is_array($value) ? json_encode($value) : $value;
            return $config->save();
        }

        self::create([
            'group' => $group,
            'key' => $key,
            'value' => is_array($value) ? json_encode($value) : $value,
            'description' => $description,
        ]);

        return true;
    }

    /**
     * 获取分组下的所有配置
     *
     * @param string $group 分组
     * @return array
     */
    public static function getByGroup(string $group): array
    {
        $configs = self::where('group', $group)->select();
        $result = [];

        foreach ($configs as $config) {
            $result[$config->key] = $config->value;
        }

        return $result;
    }
}
