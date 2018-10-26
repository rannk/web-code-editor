<?php
class RemoteControl
{

    private $connect_type = "sftp";
    private $connect_obj;

    function __construct()
    {
        $CI = & get_instance();
        $this->connect_type = $CI->config->item("connect_type");

        $this->initConnect();
    }

    public function initConnect() {
        switch ($this->connect_type) {
            case "sftp":
                require_once ("Controls/SftpControl.php");
                $this->connect_obj = new SftpControl();
                $this->connect_obj->setCI(get_instance());
                $this->connect_obj->connect();
                break;
            case "local":
                require_once ("Controls/LocalControl.php");
                $this->connect_obj = new LocalControl();
                $this->connect_obj->setCI(get_instance());
                $this->connect_obj->connect();
                break;
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
}