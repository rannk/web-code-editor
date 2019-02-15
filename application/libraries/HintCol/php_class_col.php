<?php
/**
 * 从项目文件中提取所有class，funciton，并输出成json格式
 */

$dir = $argv[1];
//$file_arr = getPHPfiles($dir);

//$test = 'E:\CommonLogin.php';
//$test = 'E:/workspace/web/code_editor/application/helpers/basic_helper.php';
//print_r(anayFile($test));


$files = getPHPfiles($dir);

$class_obj = array();

for($i=0;$i<count($files);$i++) {
    $filename = $files[$i];
    $arr = anayFile($filename);
    if(is_array($arr) && count($arr) > 0) {
        $class_obj = array_merge_recursive ($class_obj, $arr);
    }
}

echo json_encode($class_obj);

function anayFile($filename) {
    if(!file_exists($filename))
        return;

    if(filesize($filename) == 0) {
        return;
    }


    $handle = fopen($filename, "r");
    $contents = fread($handle, filesize($filename));
    fclose($handle);

    // 过滤注释
    $contents = removeComments($contents);
    $fun_arr = array();

    do{
        if(!preg_match('/(class|trait) (\w*) {0,}[^\{]{0,}\{/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            break;
        }

        $pos_s = $matches[0][1];
        $range_offsets = $pos_s + 1;

        $s = 0;
        $pos_end=0;
        $class_name = $matches[2][0];
        $fun_arr[$class_name] = array();

        do{
            if(!preg_match('/[\{\}]/', $contents, $range_matches, PREG_OFFSET_CAPTURE, $range_offsets)){
                break;
            }

            if($range_matches[0][0] == '{') {
                $s++;
            }

            if($range_matches[0][0] == '}' && $s <= 1) {
                $pos_end = $range_matches[0][1] + 1;
                break;
            }

            if($range_matches[0][0] == '}' && $s > 1) {
                $s--;
            }

            $range_offsets = $range_matches[0][1] + 1;

        }while(true);

        if($pos_end > $pos_s) {
            $new_contents = "";
            $fun_contents = substr($contents, $pos_s, $pos_end - $pos_s);
            $new_contents = substr($contents, 0, $pos_s);
            $contents = $new_contents . substr($contents, $pos_end);
            $fun_offsets = 0;
            do{
                if(!preg_match('/((public )|(private )){0,1}function {1,}(\w{1,}) {0,}\(/', $fun_contents, $fun_matches, PREG_OFFSET_CAPTURE, $fun_offsets)) {
                    break;
                }

                if(!$fun_matches[3][0]) {
                    $fun_arr[$class_name][] = $fun_matches[4][0];
                }

                $fun_offsets = $fun_matches[4][1];

            }while(true);

            // 寻找trait的文件

            if(preg_match('/use {1,}(\w{0,}\\\){0,}(\w{1,}) {0,};/',$fun_contents, $matches1)) {
                $fun_arr[$class_name]['trait'][] = $matches1[2];
            }
        }else {
            break;
        }

    }while(true);

    // 全局方法的寻找
    $offsets = 0;
    do {
        if(!preg_match('/function {0,}(\w{1,}) {0,}\(/', $contents, $matches, PREG_OFFSET_CAPTURE, $offsets)) {
            break;
        }

        $fun_arr['global_fun'][] = $matches[1][0];
        $offsets = $matches[1][1];
    }while(true);

    return $fun_arr;
}

function getPHPfiles($dir) {
    $d = dir($dir);
    $arr = array();

    while (false !== ($entry = $d->read())) {
        if($entry == "." || $entry == "..")
            continue;

        $filename = $dir . "/" . $entry;
        if(is_dir($filename)) {
            $arr = array_merge($arr, getPHPfiles($filename));
        }else {
           if(strlen($entry) > 4 && strtolower(substr($filename, -4)) == ".php") {
               $arr[] = $filename;
           }
        }
    }
    $d->close();

    return $arr;
}

/**
 * 移除所有注释以及引号内的内容
 * 避免与实际代码内容混淆
 * @param $contents
 * @return mixed|string
 */
function removeComments($contents) {
    $contents = preg_replace('/<<<\'{0,1}(\w*)\'{0,1}[\w\W]*\1;/', "", $contents);
    $contents = preg_replace('/\'.*\'/', "", $contents);
    $contents = preg_replace('/\".*\"/', "", $contents);
    do {
        if(!preg_match('/\/\*/', $contents, $matches, PREG_OFFSET_CAPTURE)){
            break;
        }

        $n_contents = substr($contents, 0, $matches[0][1]);

        // 确保注释结束符位置要大于开始位置
        $offset = 0;
        $endpos = 0;
        do{
            if(!preg_match('/\*\//', $contents, $e_matches, PREG_OFFSET_CAPTURE, $offset)){
                break;
            }else {
                if($e_matches[0][1] > $matches[0][1]) {
                    $endpos = $e_matches[0][1] + 2;
                    break;
                }else {
                    $offset = $e_matches[0][1] + 2;
                }
            }
        }while(true);

        if($endpos > 0)
            $contents = $n_contents . substr($contents, $endpos);

    }while(true);

    $contents = preg_replace('/\/\/.*/', "", $contents);
    return $contents;
}

