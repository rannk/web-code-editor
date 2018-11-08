<?php
require_once (__DIR__ . "/../class/GitControl.php");

$gObj = new GitControl();

if($_REQUEST['action'] == "add") {
    $arr['code'] = 0;
    $lists = $gObj->getTrackFiles();
    if(is_string($lists)) {
        $arr['msg'] =  $lists;
    }

    if(is_array($lists)) {
        $arr['code'] = 1;
        $arr['data'] = $lists;
    }

    echo json_encode($arr);
}

if($_REQUEST['action'] == "commit") {
    $file_arr = explode("|;|", $_REQUEST['files']);
    $files = "";
    $files_del = "";

    for($i=0;$i<count($file_arr);$i++) {
        // 万一文件中有双引号，需要转译
        $file = str_replace('"', '\"',$file_arr[$i]);
        if($file) {
            if(strlen($file)>5 && substr($file, 0, 5) == "DeL::") {
                $files_del .= '"'.stringConvert(str_replace("DeL::", "", $file), 2).'" ';
            }else {
                $files .= '"'.stringConvert($file, 2).'" ';
            }
        }
    }

    $content = $gObj->commitFiles($files, $files_del, $_REQUEST['message']);

    if(stripos($content, "no changes added to commit")) {
        $content = "no changes added to commit";
    }

    echo $content;
}

