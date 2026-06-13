<?php
require 'E:\AiWork\SmartIdleERP\Source-Code\vendor\autoload.php';

$r = new ReflectionClass('think\App');
echo "Methods:\n";
foreach ($r->getMethods() as $method) {
    echo $method->getName() . "\n";
}
