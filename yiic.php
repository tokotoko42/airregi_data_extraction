<?php

@ob_end_clean();
$yiic = dirname(__FILE__).'/yii/framework/yiic.php';

switch (strtoupper($_SERVER['AIRREGI_ENV'])) {
case 'PRO': // 本番環境
    $config = dirname(__FILE__) . '/config/_console_r_product.php';
    break;
case 'LOC': // LOCAL環境
    $config = dirname(__FILE__) . '/config/_console_r_localhost.php';
    break;
default:
    throw new Exception('Failed to detect the environment.');
    break;
}

require_once($yiic);
