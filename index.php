<?php
/**
 * 一句话日志系统
 * @author: horsley
 * @version: 2014-01-28
 */
date_default_timezone_set('Asia/Shanghai');
define('APP_ROOT', dirname(__FILE__));
define('DATA_DIR', APP_ROOT . '/.data');
define('ASSET_DIR', APP_ROOT . '/asset');
define('CONFIG_FILE', DATA_DIR.'/config.json');
define('DATA_FILE', DATA_DIR.'/data.log');
define('DATA_INDEX', DATA_DIR.'/data.idx');

require APP_ROOT.'/func.php';
require APP_ROOT.'/log.php';


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
        'save', 'load'
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
    $data = paged_read_lines(0, config()->page_size, $last_page);
    tmpl_render(ASSET_DIR.'/tmpl.html',array(
            'logs' => $data,
            'last_page' => $last_page,
        ));
}

/**
 * 日志写入
 */
function m_save() {
    if (is_ajax() && is_post()) {
        $_POST['log_line'] = isset($_POST['log_line']) ? trim($_POST['log_line']) : '';
        $_POST['log_line'] = mb_substr($_POST['log_line'], 0, 300, 'UTF-8'); //限制单行长度
        if (!empty($_POST['log_line'])) {
            $time = date('[Y/m/d H:i:s] ');
            $log_line = "{$time}{$_POST['log_line']}\n"; //追加日期格式
            if(write_line($log_line)) {
                //广播过程
                $ch = curl_init(get_baseurl().":8421/pub?line=".urlencode(htmlspecialchars($log_line)));
                curl_setopt_array($ch, array(
                    CURLOPT_RETURNTRANSFER => true
                ));
                curl_exec($ch);

                die(json_encode(array('ok' => true, 'info' => htmlspecialchars($log_line))));
            } else {
                die(json_encode(array('ok' => false, 'info' => $time.'Write File Error!')));
            }
        }
    } else {
        die('Invalid Request');
    }
}

/**
 * ajax加载前一页
 */
function m_load() {
    if (is_ajax()) {
        $page = intval($_GET['p']);
        if (empty($page)) {
            die('Invalid Page Number');
        }

        $data = paged_read_lines($page, config()->page_size, $last_page);
        echo json_encode(array(
            'data' => $data,
            'last_page' => $last_page,
        ));
    }
}


