<?php
/**
 * 函数库
 * @author: horsley
 * @version: 2014-01-28
 */

/**
 * 获取本系统存放的目录 对应的url
 * 当本系统部署在非站点根目录的时候 需要使用本函数获取系统根目录对应url
 * 其后没有斜杠
 * @access public
 * @return string
 */
function get_baseurl() {
    static $baseURL;
    if (!empty($baseURL)) return $baseURL;
    $baseURL = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "https://" : "http://";

    if (!$host = $_SERVER['HTTP_HOST']) {
        if (!$host = $_SERVER['SERVER_NAME']) {
            $host = !empty($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
        }
    }
    $baseURL .= $host. (preg_match('/(?:.*?):\d+/', $host) ? '': //如果host里面没有端口号，才考虑拼合
            ($_SERVER["SERVER_PORT"] == "80" ? '' : ':'.$_SERVER["SERVER_PORT"]));
    $baseURL .= get_basedir(); //去掉root目录和末尾的/index.php
    return $baseURL;
}

/**
 * 获取本系统存放的目录
 * 相对于站点根目录的相对目录
 * 只能是通过统一入口进入的调用
 * 返回的目录路径前面有杠，后面没杠
 * 如果部署在站点根目录，返回空文本
 * @access public
 * @return string
 */
function get_basedir() {
    $base_dir = substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen($_SERVER['DOCUMENT_ROOT']));
    //这里windows和linux环境可能存在差异导致丢失前面的斜杠，下面做兼容性处理
    if (!empty($base_dir) && $base_dir{0} != '/') {
        $base_dir = '/'.$base_dir;
    }
    return $base_dir;
}

/**
 * 向浏览器输出错误信息，并停止解析
 * @param $ErrMsg
 */
function err($ErrMsg = 'Access denied!') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Content-Type:text/plain; charset=utf-8');
    echo $ErrMsg;
    exit;
}

/**
 * 简单身份验证
 * @param array $available_user 用户密码数组array('username' => 'pass)
 * @param string $realm 可选
 */
function simple_auth($available_user, $realm= 'My Realm') {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="'.$realm.'"');
        header('HTTP/1.0 401 Unauthorized');
        die('Restricted Area!');
    } else {
        if (!isset($available_user[$_SERVER['PHP_AUTH_USER']])
            || $available_user[$_SERVER['PHP_AUTH_USER']] != $_SERVER['PHP_AUTH_PW']
        ) {
            die('Authentication Failed.');
        }
    }
}

/**
 * 全局配置对象获取
 * @return mixed
 */
function config() {
    static $_obj;
    if (empty($_obj)) { //单例
        $_obj = json_decode(file_get_contents(CONFIG_FILE));
    }
    return $_obj;
}


/**
 * 简单模板渲染
 * @param $tmpl_file 文件中
 * @param array $data
 */
function tmpl_render($tmpl_file, $data = null) {
    if(is_array($data)) {
        extract($data);
    }
    if (file_exists($tmpl_file)) {
        include($tmpl_file);
    } else {
        die('tmpl file not found!');
    }
}

/**
 * 判断ajax请求
 * @return bool
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * 判断post请求
 */
function is_post() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}