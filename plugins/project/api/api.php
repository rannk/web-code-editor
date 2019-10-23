<?php

switch ($_REQUEST['action']) {
    case "add":
        addNewProject();
        break;
    case "lists":
        listProject();
        break;
    case "open":
        openProject();
        break;
    case "remove":
        removeProject();
        break;
}

function removeProject() {
    $ci = & get_instance();
    $ci->load->library("basic");
    $r['status'] = 1;

    $projects = $ci->basic->getGlobalConfig("projects");

    $p = array();
    for($i=0;$i<count($projects);$i++) {
        if($projects[$i]['project_title'] == $_POST['title']) {
            if($projects[$i]['active'] == "1") {
                if($i<count($projects)-1) {
                    $projects[$i+1]['active'] = 1;
                }elseif(count($p)>0) {
                    $p[count($p) - 1]['active'] = 1;
                }
            }
            continue;
        }
        $p[] = $projects[$i];
    }

    try{
        $ci->basic->saveGlobalConfig("projects", $p);
        $ci->basic->setCurrentActiveProject();
    }catch (Exception $e) {
        $r['msg'] = $e->getMessage();
        $r['status'] = 0;
    }

    echo json_encode($r);
}

function openProject() {
    $ci = & get_instance();
    $ci->load->library("basic");
    $r['status'] = 1;

    $ci->basic->setCurrentActiveProject($_POST['title']);
    echo json_encode($r);
}

function listProject() {
    $ci = & get_instance();
    $ci->load->library("basic");
    $projects = $ci->basic->getGlobalConfig("projects");
    echo json_encode($projects);
}

function addNewProject() {
    $ci = & get_instance();
    $ci->load->library("basic");
    $r['status'] = 1;

    foreach($_POST as $k => $v) {
        if(!$v) {
            $r['msg'] .= "$k,";
            $r['status'] = 0;
        }
    }

    if($r['status'] == 0) {
        $r['msg'] .= " didn't have value";
    }

    if($r['status'] == 1) {
        $projects = $ci->basic->getGlobalConfig("projects");

        for($i=0;$i<count($projects);$i++) {
            $p = $projects[$i];
            if($p['project_title'] == $_POST['project_title']) {
                $_POST['project_title'] = $_POST['project_title'] . "_copy";
            }
            $projects[$i]['active'] = "";
        }

        $_POST['active'] = "1";

        $projects[] = $_POST;
        try{
            $ci->basic->saveGlobalConfig("projects", $projects);
        }catch (Exception $e) {
            $r['msg'] = $e->getMessage();
            $r['status'] = 0;
        }
    }

    echo json_encode($r);
}
