<?php
require_once (__DIR__ . "/../class/CMDControl.php");

switch ($_REQUEST['action']) {
    case "new":
        newCmd();
        break;
    case "lists":
        lists();
        break;
    case "run":
        runCmd();
        break;
    case "get_one":
        get_one();
        break;
}

function newCmd() {
    $ci = & get_instance();
    $ci->load->library("basic");

    $arr = $ci->basic->getParams("command");
    $id = count($arr) + 1;

    $r['status'] = "0";

    $cmd_id = $_REQUEST['cmd_id'];
    if($arr[$cmd_id]['title']) {
        $id = $cmd_id;
    }

    $arr[$id]['title'] = trim($_REQUEST['title']);
    $arr[$id]['script'] = trim($_REQUEST['script']);

    if($arr[$id]['title'] && $arr[$id]['script']) {
        try{
            $ci->basic->saveParams("command", $arr);
            $r['status'] = 1;
        }catch (Exception $e) {
            $r['msg'] = $e->getMessage();
        }
    }else {
        $r['msg'] = "need title or script content";
    }

    echo json_encode($r);
}

function lists() {
    $ci = & get_instance();
    $ci->load->library("basic");

    $arr = $ci->basic->getParams("command");

    echo json_encode($arr);
}

function get_one() {
    $ci = & get_instance();
    $ci->load->library("basic");
    $id = $_REQUEST['cmd_id'];
    $arr = $ci->basic->getParams("command");
    $r['status'] = "0";

    if($arr[$id]['title']) {
        $r['content'] = $arr[$id];
        $r['status'] = 1;
    }

    echo json_encode($r);
}

function runCmd() {
    $obj = new CMDControl();
    $r['status'] = "0";
    $content = "";
    try{
        $obj->runCmd($_REQUEST['cmd_id'], $content, $_POST);
        outputLog($content . "111");
        $r['status'] = 1;
        $r['content'] = $content;
    }catch (Exception $e) {
        $r['content'] = $content . "\n" . $e->getMessage();
    }

    echo json_encode($r);
}