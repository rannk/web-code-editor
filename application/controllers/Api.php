<?php
/**
 * operate file api
 * @author Rannk
 */
class api extends CI_Controller
{
    public function get_file_content() {
        if(!$_REQUEST['file'])
            return;

        $file = stringConvert($_REQUEST['file'], CONVERT_STR_SITE_TO_SYSTEM);
        $this->load->library('RemoteControl');
        echo $this->remotecontrol->getFileContent($file);
    }

    public function get_file_lists() {

        $this->load->library('RemoteControl');
        $output_arr = $this->remotecontrol->getFolderLists(stringConvert($_REQUEST['dir'], CONVERT_STR_SITE_TO_SYSTEM));

        /**
         * 对目录列表进行字符转译
         * 主要针对中文字符，编辑器采用的id，都是对utf8格式的字符串进行md5.
         * 一般系统中的中文字符都为gbk编码
         */
        for($i=0;$i<count($output_arr);$i++) {
            $output_arr[$i]['name'] = stringConvert($output_arr[$i]['name'], CONVERT_STR_SYSTEM_TO_SITE);
            $output_arr[$i]['file'] = stringConvert($output_arr[$i]['file'], CONVERT_STR_SYSTEM_TO_SITE);
            $output_arr[$i]['file_id'] = md5($output_arr[$i]['file']);
        }

        echo json_encode($output_arr);
    }

    public function save_file() {
        if(!$_POST['file'])
            return;

        $file = stringConvert($_POST['file'], CONVERT_STR_SITE_TO_SYSTEM);
        $this->load->library('RemoteControl');
        $this->remotecontrol->saveFile($file, $_POST['content']);
    }

    public function openingFiles() {
        $file_arr = getPathInfo($_REQUEST['file']);

        $file = "/" . $file_arr['basename'];
        if($file_arr['dirname'] != "/") {
            $file = $file_arr['dirname'] . $file;
        }


        $action = $_REQUEST['action'];
        $this->load->library('Basic');
        if($action == "open") {
            $this->basic->addOpenFile($file, $file_arr['basename']);
        }
    }

    public function closeFile() {
        $file_arr = getPathInfo($_REQUEST['file']);

        $file = "/" . $file_arr['basename'];
        if($file_arr['dirname'] != "/") {
            $file = $file_arr['dirname'] . $file;
        }

        $action = $_REQUEST['action'];

        $this->load->library('Basic');
        if($action == "close") {
            $this->basic->closeFile($file, $_REQUEST['n_file']);
        }
    }

    public function setActiveFile() {
        $this->load->library('Basic');
        $this->basic->setActiveFile($_REQUEST['file_id']);
    }

    /**
     * 重命名文件名
     */
    public function rename() {
        $file = anayFile($_REQUEST['file']);
        $new_filename = trim($_REQUEST['new_filename']);

        if(!$file || !$new_filename) {
            die("0");
        }

        $this->load->library('RemoteControl');

        if($this->remotecontrol->renameFile(stringConvert($file, CONVERT_STR_SITE_TO_SYSTEM),
            stringConvert($new_filename, CONVERT_STR_SITE_TO_SYSTEM))){
            $this->load->library('Basic');
            $this->basic->renameOpenFile($file, $new_filename);
            echo "1";
        }else {
            echo "-1";
        }
    }

    /**
     * 删除文件 API
     * @echo string
     */
    public function delete() {
        $file = anayFile($_REQUEST['file']);
        if(!$file) {
            die("0");
        }

        $file = stringConvert($file, CONVERT_STR_SITE_TO_SYSTEM);

        $this->load->library('RemoteControl');
        try {
            if($this->remotecontrol->deleteFile($file)) {
                echo 1;
            }else {
                echo -1;
            }
        }catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 添加新文件
     * @echo json
     */
    public function new_file() {
        $file = anayFile($_REQUEST['file']);
        if(!$file) {
            die("0");
        }

        $this->load->library('RemoteControl');
        try {
            $file_id = $this->remotecontrol->addFile($file);
            if($file_id) {
                $arr['state'] = 1;
                $arr['file_id'] = $file_id;
            }else {
                $arr['state'] = -1;
            }
        }catch(Exception $e) {
            $arr['state'] = -1;
            $arr['msg'] = $e->getMessage();
        }

        echo json_encode($arr);
    }

    /**
     * 添加新目录
     * @echo json
     */
    public function new_folder() {
        $file = anayFile($_REQUEST['file']);
        if(!$file) {
            die("0");
        }


        $this->load->library('RemoteControl');
        try {
            $file_id = $this->remotecontrol->addFolder($file);
            if($file_id) {
                $arr['state'] = 1;
                $arr['file_id'] = $file_id;
            }else {
                $arr['state'] = -1;
            }
        }catch(Exception $e) {
            $arr['state'] = -1;
            $arr['msg'] = $e->getMessage();
        }

        echo json_encode($arr);
    }
}