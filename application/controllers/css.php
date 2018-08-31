<?php
class css extends CI_Controller {

    public function minify() {
        $Etag = "";
        $query_str = $_SERVER["QUERY_STRING"];
        $arr = explode(":", $query_str);
        for($i=0; $i<count($arr);$i++) {
            $v = __DIR__ . "/../../" . $arr[$i];
            if(file_exists($v)) {
                $files[] = $v;
                $Etag .= filemtime($v);
            }
        }

        $Etag = md5($Etag);

        if(array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER) and $_SERVER['HTTP_IF_NONE_MATCH'] == $Etag){
            header("HTTP/1.1 304 Not Modified");
            exit();
        } else {
            header("Etag:" . $Etag);
        }
        header('Content-type: text/css');
        ob_start("compress");

        for($i=0; $i<count($files);$i++) {
            include($files[$i]);
        }

        ob_end_flush();
    }
}

/**
 * 压缩css文件
 */
function compress($buffer) {
    /* remove comments */
    $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
    /* remove tabs, spaces, newlines, etc. */
    $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
    return $buffer;
}