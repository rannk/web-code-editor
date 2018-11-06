<?php
require_once (__DIR__ . "/../../../application/libraries/RemoteControl.php");
class GitControl extends RemoteControl
{

    public function initConnect() {
        switch ($this->connect_type) {
            case "sftp":
                require_once ("Sftp.php");
                $this->connect_obj = new Sftp();
                break;
            case "local":
                require_once ("Local.php");
                $this->connect_obj = new Local();
                break;
        }

        if(is_object($this->connect_obj)) {
            $this->connect_obj->setCI(get_instance());
            $this->connect_obj->connect();
        }
    }

    public function getTrackFiles() {
        return $this->connect_obj->getTrackFiles();
    }
}