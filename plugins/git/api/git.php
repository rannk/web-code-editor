<?php
require_once (__DIR__ . "/../class/GitControl.php");

$gObj = new GitControl();

if($_REQUEST['action'] == "add") {
    $arr['code'] = 0;
    $results = $gObj->getTrackFiles();
    $arr['branch'] = $results['branch'];
    if(is_string($results['lists'])) {
        $arr['msg'] =  $results['lists'];
    }

    if(is_array($results['lists'])) {
        $arr['code'] = 1;
        $arr['data'] = $results['lists'];
    }

    $results = $gObj->getGlobal();
    $arr['name'] = $results['name'];
    $arr['email'] = $results['email'];
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

    $content = $gObj->commitFiles($files, $files_del, str_replace('"', '\"', $_REQUEST['message']), $_REQUEST['name'], $_REQUEST['email']);

    if(stripos($content, "no changes added to commit")) {
        $content = "no changes added to commit";
    }

    echo $content;
}

if($_REQUEST['action'] == "branch") {
    $arr['code'] = 0;
    $results = $gObj->getBranchs();
    if(is_string($results)) {
        $arr['msg'] =  $results;
    }

    if(is_array($results)) {
        $arr['code'] = 1;
        $arr['data'] = $results;
    }

    echo json_encode($arr);
}

if($_REQUEST['action'] == "checkout") {
    $branch = $_REQUEST['branch'];
    $new_branch = $_REQUEST['new_branch'];

    echo $gObj->checkout($branch, $new_branch);
}

if($_REQUEST['action'] == "pull") {
    $arr['code'] = 1;
    try{
        $branch = $results = $gObj->getCurrentBranch();
    }catch(Exception $e) {
        $arr['code'] = 0;
        $branch = $e->getMessage();
    }

    $arr['msg'] = $branch;
    echo json_encode($arr);
}

if($_REQUEST['action'] == "push") {
    $arr['code'] = 1;
    $arr['msg'] = htmlentities($gObj->getLastestCommit());
    $arr['remote'] = $gObj->getRemote();
    echo json_encode($arr);
}

if($_REQUEST['action'] == "pull_op") {
    echo htmlentities($gObj->pull());
}

if($_REQUEST['action'] == "push_op") {
    $force = $_REQUEST['f'];
    $remote = $_REQUEST['remote'];

    try{
        $content = $gObj->push($remote, $force);
    }catch (Exception $e) {
        $content = $e->getMessage();
    }

    echo htmlentities($content);
}

if($_REQUEST['action'] == "revert") {
    $arr['status'] = 0;
    $files = str_replace("|;|", " ", $_POST['files']);

    if(!$files) {
        $arr['msg'] = "no revert files";
    }else {
        $arr = $gObj->revertFiles($files);
    }

    echo json_encode($arr);
}
