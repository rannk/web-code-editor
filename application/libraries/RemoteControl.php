<?php
class RemoteControl
{

    protected $connect_type = "sftp";
    protected $connect_obj;

    function __construct()
    {
        $CI = & get_instance();
        $this->connect_type = $CI->config->item("connect_type");

        $this->initConnect();
    }

    /**
     * 获取工作空间系统类型
     */
    public function getWorkspaceSysType() {
        $cmd = "uname";
        $content = "";
        try{
            $this->connect_obj->cmd($cmd, $content);
            if(!(stripos($content, "MSYS") === false)) {
                return "Win";
            }
        }catch (Exception $e) {
            return "Win";
        }

        return "Linux";
    }

    /**
     * 获取移动到工作空间的命令
     * 主要与其它命令相衔接，如果有命令需要移动到工作空间下执行，会用到这个
     */
    public function getGotoWorkspaceDirCmd() {
        $workspace = $this->connect_obj->workspace_dir;
        $cmd = "";
        if($this->getWorkspaceSysType() == "Win") {
            $arr = explode(":", $workspace);
            if($arr[0]){
                $cmd .= $arr[0] . ": &";
            }

            if($arr[1]) {
                $cmd .= " cd " . $arr[1] . " & ";
            }
        }else {
            $cmd = "cd " . $workspace . " && ";
        }

        return $cmd;
    }

    public function initConnect() {
        switch ($this->connect_type) {
            case "sftp":
                require_once ("Controls/SftpControl.php");
                $this->connect_obj = new SftpControl();
                break;
            case "local":
                require_once ("Controls/LocalControl.php");
                $this->connect_obj = new LocalControl();
                break;
        }

        if(is_object($this->connect_obj)) {
            $this->connect_obj->setCI(get_instance());
            $this->connect_obj->connect();
        }
    }

    public function getType() {
        return $this->connect_type;
    }

    public function getFileContent($filename) {
        if(!$this->connect_obj) {
            $this->initConnect();
        }

        $content = $this->connect_obj->getFileContent($filename);

        return $content;
    }

    public function getFolderLists($path) {
        if(!$this->connect_obj) {
            $this->initConnect();
        }

        return $this->connect_obj->getFolderLists($path);
    }

    public function saveFile($filename, $content) {
        if(!$this->connect_obj) {
            $this->initConnect();
        }

        if(substr($filename, -1) == "/") {
            $filename = substr($filename, 0,  -1);
        }

        $CI = & get_instance();
        $cache_dir =$CI->config->item("cache_file_dir");
        $local_filename = "editor_cache_" . md5($filename);
        $fp = fopen($cache_dir . "/" . $local_filename, "w");
        fwrite($fp, $content);
        fclose($fp);

        return $this->connect_obj->saveFile($cache_dir . "/" . $local_filename, $filename);
    }

    public function renameFile($filename, $new_filename) {
        if(!$this->connect_obj) {
            $this->initConnect();
        }

        if(substr($filename, -1) == "/") {
            $filename = substr($filename, 0,  -1);
        }

        return $this->connect_obj->renameFile($filename, $new_filename);
    }

    public function fileExists($filename) {
        if(!$this->connect_obj) {
            $this->initConnect();
        }

        if(substr($filename, -1) == "/") {
            $filename = substr($filename, 0,  -1);
        }

        return $this->connect_obj->fileExists($filename);
    }

    public function deleteFile($filename) {
        if(!$this->connect_obj) {
            $this->initConnect();
        }

        if(substr($filename, -1) == "/") {
            $filename = substr($filename, 0,  -1);
        }

        return $this->connect_obj->deleteFile($filename);
    }

    public function addFile($filename) {
        if(!$this->connect_obj) {
            $this->initConnect();
        }

        if(substr($filename, -1) == "/") {
            $filename = substr($filename, 0,  -1);
        }

        if($this->connect_obj->addFile(stringConvert($filename, CONVERT_STR_SITE_TO_SYSTEM))) {
            return md5($filename);
        }
    }

    public function addFolder($filename) {
        if(!$this->connect_obj) {
            $this->initConnect();
        }

        if(substr($filename, -1) == "/") {
            $filename = substr($filename, 0,  -1);
        }

        if($this->connect_obj->addFolder(stringConvert($filename, CONVERT_STR_SITE_TO_SYSTEM))) {
            return md5($filename);
        }
    }

    public function getHintContent() {
        if(!$this->connect_obj) {
            $this->initConnect();
        }

        return $this->connect_obj->getHintContent();
    }
}