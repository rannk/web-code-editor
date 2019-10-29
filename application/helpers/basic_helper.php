<?php
/**
 * print the info on browser
 * @param $str
 */
function print_h($str) {
    $str = print_r($str, true);
    $str = str_replace("\n", "<br>", $str);
    $str = str_replace(" ", "&nbsp", $str);
    echo $str;
}

/**
 * 获取系统是否为开发模式
 * @return mixed
 */
function getDevModel() {
    return get_instance()->config->item("dev_model");
}

/**
 * 解析输入的文件路径，返回统一格式处理的文件路径
 * @param $filepath
 * @return string
 */
function anayFile($filepath) {
    $file_arr = getPathInfo($filepath);
    $file = "/" . $file_arr['basename'];
    if($file_arr['dirname'] != "/") {
        $file = $file_arr['dirname'] . $file;
    }

    return $file;
}

function getPathInfo($path) {
    $file_arr  = explode("/", $path);
    $arr['basename'] = $file_arr[count($file_arr)-1];
    unset($file_arr[count($file_arr)-1]);
    $arr['dirname'] = implode("/", $file_arr);

    if($arr['dirname'] == "") {
        $arr['dirname'] = "/";
    }
    return $arr;
}

/**
 * add attr on DOM
 * if the value is empty, will not add attr
 * @param $name
 * @param $value
 * @return string
 */
function setDomAttr($name, $value) {
    $value = trim($value);
    if($name && $value) {
        return $name . '="' . $value . '"';
    }
}

/**
 * 设置Plugins的DOM 项
 * 函数返回特定属性的DOM 元素
 * @param $tag DOM的标签
 * @param $arr 提供属性数据的数组
 * @return string
 */
function setPluginItem($tag, $arr) {
    $tag = trim($tag);
    if(!$tag)
        return;

    $content = "<" . $tag . "";
    $name = "";
    if(is_array($arr) && count($arr) > 0) {
        $plugin_dir = "";
        foreach ($arr as $k => $v) {
            if($k == "plugin_dir") {
                $plugin_dir = $v;
                break;
            }
        }
        $menu_icon = "";
        $sub = "";
        $sub_icon = "";

        foreach($arr as $k => $v) {
            switch($k) {
                case "executeJs":
                    $v = checkFileSitePath($plugin_dir, $v);
                    $content .= " op_file='{$v}'";
                    break;
                case "executeAction":
                    $content .= " action='{$v}'";
                    break;
                case "version":
                    $content .= " version='{$v}'";
                    break;
                case "id":
                    $content .= " id='{$v}'";
                    break;
                case "name":
                    $name = $v;
                    break;
                case "icon":
                    $menu_icon = "<label class='icon'>" . $v . "</label>";
                    break;
                case "submenu":
                    if(count($v)>0) {
                        foreach($v as $s_v) {
                            $sub .= setPluginItem($tag, $s_v);
                        }
                    }
                    break;
                case "section_class":
                    if($v == "1") {
                        $content .= "class='add_line'";
                    }

            }
        }

        if($sub) {
            $sub = '<ul class="menu_wrapper">' . $sub . "</ul>";
            $sub_icon = '<label class="sub_icon"><span class="fa fa-caret-right"></span></label>';
        }

        $name = '<div class="text">' . $name . "</div>";
        if($sub) {
            $content .= " has_sub='true'";
        }
        $content .= ">" . $menu_icon . $name . $sub_icon ."<div class='sub'>$sub</div>";
    }else {
        return;
    }

    $content .= "</" . $tag . ">";

    return $content;
}

/**
 * 读取plugins的Modal配置，并调用其模板到editor页面中
 * @param $plugin_dir
 * @param $modal_file
 */
function loadPluginsModal($plugin_dir, $modal_file) {
    $lang_dir = get_instance()->config->item("language");
    if(!file_exists(__DIR__ . "/../../plugins/" . $plugin_dir . "/language/" . $lang_dir)) {
        $lang_dir = "english";
    }

    if(is_dir(__DIR__ . "/../../plugins/" . $plugin_dir . "/language/" . $lang_dir) && file_exists($modal_file)) {
        $d = dir(__DIR__ . "/../../plugins/" . $plugin_dir . "/language/" . $lang_dir);
        while (false !== ($entry = $d->read())) {

            if($entry == "." || $entry == ".." || !stripos($entry, "_lang.php")) {
                continue;
            }
            include (__DIR__ . "/../../plugins/" . $plugin_dir . "/language/" . $lang_dir . "/" . $entry);

        }

        // 读取modal内容，判断语言变量是否存在值，如果不存在则把key转换测value
        if(file_exists($modal_file)) {
            $file_content = file_get_contents($modal_file);
            $matches = array();
            preg_match_all("/lang\[['\"](.*)['\"]\]/", $file_content, $matches);
            for($i=0;$i<count($matches[1]);$i++) {
                $key = $matches[1][$i];
                if(!$lang[$key]) {
                    $key = str_replace("_", " ", $key);
                    $key = str_replace("-", " ", $key);
                    $key_arr = explode(" ", $key);
                    $key_content = "";
                    foreach($key_arr as $key_v) {
                        $key_content .= ucfirst($key_v). " ";
                    }
                    $lang[$matches[1][$i]] = $key_content;
                }
            }
        }

        include ($modal_file);
        $d->close();
    }
}

function getStandardPath($path) {
    $path = str_replace("\\", "/", $path);
    if(substr($path, -1) == "/") {
        $path = substr($path, 0, -1);
    }

    return $path;
}
/**
 * 判断给定文件的路径
 * 如果给定的文件开头是以 / 开头，则认为是绝对路径，不做任何改变返回
 * 如果给定的文件开头不是/, 则认为是相对路径，赋值当前所在位置路径返回
 * @param $plugin_dir
 * @param $filename
 * @return string
 */
function checkFilePath($plugin_dir, $filename) {
    if($plugin_dir != "" && strlen($filename) > 0 && substr($filename, 0, 1) != "/") {
        $dir = str_replace("\\", "/", __DIR__);
        $dir = str_replace("application/helpers", "", $dir);
        return $dir . "plugins/" . $plugin_dir . "/" . $filename;
    }

    return $filename;
}

function checkFileSitePath($plugin_dir, $filename) {
    $site_path = str_replace("/index.php", "", $_SERVER['SCRIPT_NAME']);
    if($plugin_dir != "" && strlen($filename) > 0 && substr($filename, 0, 1) != "/") {
        return $site_path . "/plugins/" . $plugin_dir . "/" . $filename;
    }

    return $site_path . $filename;

}

/**
 * 判断文件是不是图片
 * @param $filename
 */
function isImage($filename) {
    $image_ext_arr = ["jpg", "jpeg", "png", "bmp", "gif"];
    $file_arr = pathinfo($filename);
    $ext = strtolower($file_arr['extension']);
    if(in_array($ext, $image_ext_arr)) {
        return $ext;
    }

    return false;
}

/**
 * 合并配置文件
 * 合并要求：如果原先没有元素，则增加新的元素
 *           如果新的元素下标是数字，则增加该元素
 *           如果原先有元素，但是数组，且要增加的元素也是数组，则再判断新元素下的元素是否符合上述规则，符合规则增加。
 * @param $original_config
 * @param $configuration_to_merge
 * @return array
 */
function pluginConfigMerge($original_config, $configuration_to_merge) {
    if(is_array($configuration_to_merge) && count($configuration_to_merge) > 0){
        foreach($configuration_to_merge as $k => $v) {
            if(is_int($k)) {
                $original_config[] = $v;
            } else {
                if(!$original_config[$k]) {
                    $original_config[$k] = $v;
                }elseif(is_array($original_config[$k]) && is_array($v)){
                    $original_config[$k] = pluginConfigMerge($original_config[$k], $v);
                }
            }
        }
    }

    return $original_config;
}

/**
 * 转换字符类型，如果检测到是中文字符，则对字符进行GBK 到 UTF8 的互转
 *
 * @param $str
 * @param $mode 1表示从from 到 to, 2代表 从 to 到 from
 * @return string
 */
function stringConvert($str, $mode) {
    $char_code['cn']['from'] = "gbk";
    $char_code['cn']['to'] = "utf-8";

    $char_type = "";
    for($i=0;$i<strlen($str);$i++) {
        $char = substr($str, $i, 1);
        $ascii = ord($char);
        if($ascii > 127) {
            $char_type = "cn";
            break;
        }
    }

    if($char_type) {
        if($mode == 1) {
            $str = iconv($char_code[$char_type]['from'], $char_code[$char_type]['to'], $str);
        }else {
            $str = iconv($char_code[$char_type]['to'], $char_code[$char_type]['from'], $str);
        }
    }

    return $str;
}

/**
 * 把信息记录到临时文件中
 * 主要用于调试后台执行数据的情况
 * @param $str
 */
function outputLog($str) {
    if(get_instance()->config->item("dev_model")) {
        $tmp_dir = get_instance()->config->item("temp_file_dir");
        if(file_exists($tmp_dir)) {
            $fp = fopen($tmp_dir . "/output_log.t", "a");
            fwrite($fp, $str . "\n");
            fclose($fp);
        }
    }
}

function asciiToChar($str) {
    eval("\$str = \"". $str ."\";");
    return $str;
}

/**
 * 如果输入的字符串有空格，给字符串打双引号
 * 主要用于文件名
 * @param $string
 * @return string
 */
function addQuoteForString($string) {
    $string = trim($string);
    if(stripos($string, " ")) {
        return '"' . $string . '"';
    }

    return $string;
}

/**
 * 设置PHP内置函数的错误信息作为异常抛出
 * 指定在特定程序文件中会生效，其它的按默认方式输出错误信息
 * @param $full_path_file 指定默认的生效的程序文件，需要绝对的路径，可以采用 __FILE__方法获取
 */
function setErrorAsException($full_path_file) {
    global $_REG_E_FILES;
    if(is_array($_REG_E_FILES)) {
        for($i=0;$i<count($_REG_E_FILES);$i++) {
            if($full_path_file == $_REG_E_FILES[$i])
                break;
        }

        if($i >= count($_REG_E_FILES)) {
            $_REG_E_FILES[] = $full_path_file;
        }
    }else {
        $_REG_E_FILES[] = $full_path_file;
    }

    set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
        global $_REG_E_FILES;
        if(is_array($_REG_E_FILES) && in_array($errfile, $_REG_E_FILES)) {
            if(in_array($errno, array(E_USER_WARNING, E_USER_ERROR, E_WARNING, E_ERROR))) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
                return;
            }
        }

        _error_handler($errno, $errstr, $errfile, $errline );
    });
}

function loadWorkspaceTypeJs() {
    $type = get_instance()->config->item("workspace_type");
    $type = strtolower($type);
    switch($type) {
        case "php":
            echo '<script src="addon/hint/phpclass-hint.js"></script><script src="addon/hint/php-hint-col.js"></script>';
            break;
        default:
    }
}
