<?php
/**
 * 日志读写相关函数
 * @author: horsley
 * @version: 2014-01-29
 */

/**
 * 为日志建立索引
 * @return bool|int
 */
function make_index() {
    $handle = @fopen(DATA_FILE, 'r');
    if ($handle) {
        $buffer = '';$index = '';$offset = 0;
        do {
            $offset += strlen($buffer);
            $index .= pack('L', $offset);
        } while (($buffer = fgets($handle)) !== false);

        if (!feof($handle)) {
            return false;
        }
        fclose($handle);

        return file_put_contents(DATA_INDEX, $index, LOCK_EX);
    } else {
        return false;
    }
}

/**
 * 分页读取
 * @param int $page_num
 * @param int $page_size
 * @return bool
 */
function paged_read_lines($page_num = 0, $page_size = 300) {
    //利用索引定位
    $handle = @fopen(DATA_INDEX, 'r');
    if (!$handle) {return false;}
    fseek($handle, - ($page_num + 1) * $page_size * 4 - 4, SEEK_END);
    $pos = unpack('Loffset', fread($handle, 4));
    $pos = array_pop($pos);
    fclose($handle);
    var_dump($pos);
    //读取单页数据
    $handle = @fopen(DATA_FILE, 'r');
    if ($handle) {
        fseek($handle, $pos, SEEK_SET);
        $line_count = 0;$ret = array();
        while ((($buffer = fgets($handle)) !== false) && $line_count < $page_size) {
            $ret[] = $buffer;
            $line_count++;
        }
        fclose($handle);

        return $ret;
    } else {
        return false;
    }
}