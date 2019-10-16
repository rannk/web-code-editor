<?php
require_once (__DIR__ . "/../../../application/libraries/RemoteControl.php");
class CMDControl extends RemoteControl
{

    public function __construct()
    {
        parent::__construct();
        setErrorAsException(__FILE__);
    }

    public function runCmd($id, &$content, $param = array(), $output_callback = false) {
        $CI = & get_instance();
        $CI->load->library("basic");
        $arr = $CI->basic->getParams("command");

        if($arr[$id]['title']) {
            $cache_dir =$CI->config->item("cache_file_dir");
            $cmd_filename = "editor_cmd_" . md5($CI->basic->workspace_unique_id . $id);
            try{
                $fp = fopen($cache_dir . "/" . $cmd_filename, "w");
                $file_content = $arr[$id]['script'];

                // 替换参数变量
                if($param['filename']) {
                    $file_content = str_replace('$file_name', $param['filename'], $file_content);
                }

                fwrite($fp, $file_content);
                fclose($fp);

                switch($this->connect_type) {
                    case "sftp":
                        return $this->runCmdBySsh($cache_dir . "/" . $cmd_filename, $cmd_filename, $content, $output_callback);
                        break;
                    case "local":
                        return $this->runCmdInLocal($cache_dir . "/" . $cmd_filename, $content, $output_callback);
                        break;
                }

            }catch (Exception $e) {
                throw new Exception($e->getMessage());
                return;
            }
        }
    }

    public function runCmdInLocal($script_full_path, &$output, $output_callback = false) {

        if(file_exists($script_full_path)) {

            if($this->getWorkspaceSysType() == "Win") {
                copy($script_full_path, $script_full_path . ".bat");
                $script_full_path = $script_full_path . ".bat";
            }else {
                copy($script_full_path, $script_full_path . ".sh");
                $script_full_path = $script_full_path . ".sh";
                chmod($script_full_path, 0777);
            }

            $cmd = $this->getGotoWorkspaceDirCmd() . $script_full_path;
            try{
                $this->connect_obj->cmd($cmd, $output, $output_callback);
                unlink($script_full_path);
            }catch (Exception $e){
                unlink($script_full_path);
                throw new Exception($e->getMessage());
            }

            return true;
        }else {
            throw new Exception("command file is not existed");
        }
    }

    public function runCmdBySsh($script_full_path, $filename, &$content,$output_callback = false) {
        $sftp = ssh2_sftp($this->connect_obj->connection);
        $workspace = $this->connect_obj->workspace_dir;
        if(@ssh2_sftp_realpath($sftp, "/tmp")) {
            $content = "";
            ssh2_scp_send($this->connect_obj->connection, $script_full_path, "/tmp/". $filename, 0777);
            try{
                $this->connect_obj->cmd($this->getGotoWorkspaceDirCmd() . "/tmp/". $filename, $content, $output_callback);
            }catch (Exception $e) {
                throw new Exception($e->getMessage());
            }

            return true;
        }else {
            throw new Exception("ssh: temp folder is not existed");
        }
    }
}