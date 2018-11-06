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

