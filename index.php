<?php
/**
 * 一句话日志系统
 * @author: horsley
 * @version: 2014-01-28
 */

define('APP_ROOT', dirname(__FILE__));
define('DATA_DIR', APP_ROOT . '/.data');
define('ASSET_DIR', APP_ROOT . '/asset');
define('CONFIG_FILE', DATA_DIR.'/config.json');
define('DATA_FILE', DATA_DIR.'/data.log');

require APP_ROOT.'/func.php';


if (!file_exists(CONFIG_FILE) || !is_readable(CONFIG_FILE)) {
    err('Config file not exist or permission denied');
}

//简单身份验证
//simple_auth(array(
//    config()->user->username => config()->user->password
//), config()->realm);

//////////////////////////////////////////////////////////////////////
//                           路由分发                               //
//////////////////////////////////////////////////////////////////////
if (empty($_GET['m']) || !in_array($_GET['m'], array(
        'save'
    ))) {
    $module = 'index'; //默认路由
} else {
    $module = $_GET['m'];
}

$func_name = 'm_'.$module;
if (function_exists($func_name) && is_callable($func_name)) {
    call_user_func($func_name);
} else {
    die('405 Method Not Allowed');
}
/////////////////// 路由分发结束，下面是各个模块 /////////////////////

/**
 * 入口页
 */
function m_index() {
    tmpl_render(ASSET_DIR.'/tmpl.html',array(
            'logs' => file_exists(DATA_FILE) ? file(DATA_FILE) : array(),
        ));
}

/**
 * 日志写入
 */
function m_save() {
    if (is_ajax() && is_post()) {
        $_POST['log_line'] = isset($_POST['log_line']) ? trim($_POST['log_line']) : '';
        if (!empty($_POST['log_line'])) {
            $time = date('[Y/m/d H:i:s] ');
            $log_line = "{$time}{$_POST['log_line']}\n";

            if(file_put_contents(DATA_FILE, $log_line, FILE_APPEND|LOCK_EX)) {
                die(json_encode(array('ok' => true, 'info' => htmlspecialchars($log_line))));
            } else {
                die(json_encode(array('ok' => false, 'info' => $time.'Write File Error!')));
            }
        }
    } else {
        die('Invalid Request');
    }
}




