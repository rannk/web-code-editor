<?php
/**
 * operate file api
 * @author Rannk
 */
class api extends CI_Controller
{
    public function get_file_content() {

        $this->load->library('RemoteControl');
        echo $this->remotecontrol->getFileContent($_REQUEST['file']);
    }

    public function get_file_lists() {

        $this->load->library('RemoteControl');
        $output_arr = $this->remotecontrol->getFolderLists($_REQUEST['dir']);

        echo json_encode($output_arr);
    }

    public function save_file() {
        if(!$_POST['file'])
            return;

        $this->load->library('RemoteControl');
        $this->remotecontrol->saveFile($_POST['file'], $_POST['content']);
    }

    public function openingFiles() {
        $file = $_REQUEST['file'];
        $action = $_REQUEST['action'];
        $showname = $_REQUEST['showname'];
        $this->load->library('Basic');
        if($action == "open") {
            $this->basic->addOpenFile($file, $showname);
        }
    }

    public function closeFile() {
        $file = $_REQUEST['file'];
        $action = $_REQUEST['action'];

        $this->load->library('Basic');
        if($action == "close") {
            $this->basic->closeFile($file, $_REQUEST['n_file']);
        }
    }
}