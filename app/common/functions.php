<?php

/**
 * 公共函数库
 * 包含项目中常用的辅助函数
 */

/**
 * 移动加权平均成本计算
 *
 * @param float $oldCost 原成本价
 * @param float $oldQty 原库存数量
 * @param float $newCost 新入库成本价
 * @param float $newQty 新入库数量
 * @return float
 */
if (!function_exists('weight_avg')) {
    function weight_avg(float $oldCost, float $oldQty, float $newCost, float $newQty): float
    {
        $totalQty = $oldQty + $newQty;
        if ($totalQty <= 0) {
            return 0;
        }
        return round(($oldCost * $oldQty + $newCost * $newQty) / $totalQty, 4);
    }
}

/**
 * 生成唯一订单号
 *
 * @param string $prefix 前缀
 * @return string
 */
if (!function_exists('generate_order_no')) {
    function generate_order_no(string $prefix = 'NO'): string
    {
        return $prefix . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

/**
 * 生成SKU编码
 *
 * @param string $prefix 产品前缀
 * @param string $specHash 规格组合Hash
 * @param int $seq 序号
 * @return string
 */
if (!function_exists('generate_sku_code')) {
    function generate_sku_code(string $prefix, string $specHash, int $seq): string
    {
        return strtoupper($prefix) . '-' . strtoupper(substr($specHash, 0, 8)) . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}

/**
 * 计算规格组合的Hash值
 *
 * @param array $specs 规格组合 ['颜色'=>'红色','尺码'=>'XL']
 * @return string
 */
if (!function_exists('calc_spec_hash')) {
    function calc_spec_hash(array $specs): string
    {
        ksort($specs);
        return md5(json_encode($specs, JSON_UNESCAPED_UNICODE));
    }
}

/**
 * 格式化金额
 *
 * @param float $amount 金额
 * @param int $decimals 小数位数
 * @return string
 */
if (!function_exists('format_amount')) {
    function format_amount(float $amount, int $decimals = 2): string
    {
        return number_format($amount, $decimals, '.', '');
    }
}

/**
 * 生成随机字符串
 *
 * @param int $length 长度
 * @param string $chars 字符集
 * @return string
 */
if (!function_exists('random_string')) {
    function random_string(int $length = 16, string $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'): string
    {
        $str = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, $max)];
        }
        return $str;
    }
}

/**
 * 安全获取数组值
 *
 * @param array $array 数组
 * @param string $key 键名（支持点语法，如 user.name）
 * @param mixed $default 默认值
 * @return mixed
 */
if (!function_exists('array_get')) {
    function array_get(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

/**
 * 获取客户端IP地址
 *
 * @return string
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip(): string
    {
        $ip = '';
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip ? explode(',', $ip)[0] : '0.0.0.0';
    }
}

/**
 * 判断是否为空（排除0和'0'）
 *
 * @param mixed $value
 * @return bool
 */
if (!function_exists('empty_ext')) {
    function empty_ext($value): bool
    {
        return empty($value) && !is_numeric($value);
    }
}

/**
 * 数组转换为下拉选项格式
 *
 * @param array $array 数据源
 * @param string $idField ID字段名
 * @param string $nameField 名称字段名
 * @return array
 */
if (!function_exists('to_select_options')) {
    function to_select_options(array $array, string $idField = 'id', string $nameField = 'name'): array
    {
        $options = [];
        foreach ($array as $item) {
            $options[] = [
                'value' => $item[$idField],
                'label' => $item[$nameField],
            ];
        }
        return $options;
    }
}

/**
 * 递归获取树形结构
 *
 * @param array $data 数据源
 * @param int $parentId 父级ID
 * @param string $idField ID字段名
 * @param string $parentField 父级字段名
 * @param string $childrenField 子级字段名
 * @return array
 */
if (!function_exists('build_tree')) {
    function build_tree(array $data, int $parentId = 0, string $idField = 'id', string $parentField = 'parent_id', string $childrenField = 'children'): array
    {
        $tree = [];
        
        foreach ($data as $item) {
            if ($item[$parentField] == $parentId) {
                $children = build_tree($data, $item[$idField], $idField, $parentField, $childrenField);
                if (!empty($children)) {
                    $item[$childrenField] = $children;
                }
                $tree[] = $item;
            }
        }
        
        return $tree;
    }
}

/**
 * 获取树形结构的ID列表（包含所有子级）
 *
 * @param array $data 数据源
 * @param int $parentId 父级ID
 * @param string $idField ID字段名
 * @param string $parentField 父级字段名
 * @return array
 */
if (!function_exists('get_tree_ids')) {
    function get_tree_ids(array $data, int $parentId, string $idField = 'id', string $parentField = 'parent_id'): array
    {
        $ids = [$parentId];
        
        foreach ($data as $item) {
            if ($item[$parentField] == $parentId) {
                $ids = array_merge($ids, get_tree_ids($data, $item[$idField], $idField, $parentField));
            }
        }
        
        return $ids;
    }
}

/**
 * 验证手机号格式
 *
 * @param string $phone
 * @return bool
 */
if (!function_exists('is_valid_phone')) {
    function is_valid_phone(string $phone): bool
    {
        return preg_match('/^1[3-9]\d{9}$/', $phone) === 1;
    }
}

/**
 * 验证邮箱格式
 *
 * @param string $email
 * @return bool
 */
if (!function_exists('is_valid_email')) {
    function is_valid_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * 驼峰转下划线
 *
 * @param string $str
 * @return string
 */
if (!function_exists('camel_to_underline')) {
    function camel_to_underline(string $str): string
    {
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $str));
    }
}

/**
 * 下划线转驼峰
 *
 * @param string $str
 * @return string
 */
if (!function_exists('underline_to_camel')) {
    function underline_to_camel(string $str): string
    {
        return lcfirst(str_replace('_', '', ucwords($str, '_')));
    }
}

/**
 * 记录操作日志
 *
 * @param string $module 模块
 * @param string $action 动作
 * @param string $description 描述
 * @param array $data 附加数据
 */
if (!function_exists('log_operation')) {
    function log_operation(string $module, string $action, string $description, array $data = []): void
    {
        $log = [
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'employee_id' => request()->employee_id ?? 0,
            'ip' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
        ];
        
        \think\facade\Db::name('operation_log')->insert($log);
    }
}
