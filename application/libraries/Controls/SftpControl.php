<?php
/**
 * connect reomte server's workspace by SFTP
 * @author Rannk Deng
 *
 */
require_once ("Control.php");

class SftpControl implements Control
{
    var $username;
    var $workspace_host;
    var $password;
    var $port;
    var $workspace_dir;
    var $connection;
    var $tmp_dir;

    public function connect(){
        $this->connection = ssh2_connect($this->workspace_host, $this->port);
        ssh2_auth_password($this->connection,  $this->username, $this->password);
    }

    public function setCI(& $CI) {
        $params['username'] = $CI->config->item("conn_username");
        $params['workspace_host'] = $CI->config->item("conn_host");
        $params['password'] = $CI->config->item("conn_password");
        $params['workspace_dir'] = $CI->config->item("workspace_dir");
        $params['port'] = $CI->config->item("conn_port");
        $params['temp_dir'] = $CI->config->item("temp_file_dir");

        $this->setConfiguration($params);
    }

    public function setConfiguration($params) {
        $this->username = $params['username'];
        $this->workspace_host = $params['workspace_host'];
        $this->password = $params['password'];
        $this->workspace_dir = $params['workspace_dir'];
        $this->port = $params['port'];
        $this->tmp_dir = $params['temp_dir'];
    }

    public function getFileContent($filename){
        if(!$this->tmp_dir)
            return;

        $filename = $this->workspace_dir . $filename;
        $local_file = $this->tmp_dir . "/" .md5($filename);
        ssh2_scp_recv($this->connection, $filename, $local_file);
        if(file_exists($local_file)) {
            $handle = fopen($local_file, "r");
            $contents = '';
            while (!feof($handle)) {
                $contents .= fread($handle, 8192);
            }
            fclose($handle);
            unlink($local_file);
        }

        return $contents;
    }

    public function saveFile($from, $to) {
        if(!file_exists($from))
            return;

        ssh2_scp_send($this->connection, $from, $this->workspace_dir . $to);
    }

    public function getFolderLists($path = "/"){
        $workspace = $this->workspace_dir . $path;

        $stream = ssh2_exec($this->connection, 'ls -la ' . $workspace);

        $folder_arr = array();
        $file_arr = array();
        $folder_str = "";
        while (!feof($stream)) {
            $content = fgets($stream, 8192);
            if($content && substr($content, 0, 5) != "total") {
                $input = array();

                $arr = explode(" ", $content);
                $filename = $arr[count($arr) - 1];
                $filename = str_replace("\n", "", $filename);
                if($filename == "." || $filename == "..")
                    continue;

                $input['name'] = $filename;
                $input['hasfile'] = 0;
                $input['type'] = "";
                $type = substr($content, 0, 1);
                if($type == "d") {
                    $input['type'] = "folder";
                    $folder_arr[] = $input;
                    $folder_str .= $filename ." ";
                }

                if($type == "-") {
                    $input['type'] = "file";
                    $file_arr[] = $input;
                }

            }
        }

        $this->connect();

        $stream = ssh2_exec($this->connection, 'cd ' . $workspace . " && ls " . $folder_str);

        $i = 0;
        if($stream) {
            $point = false;
            while (!feof($stream)) {
                $content = fgets($stream, 8192);
                if($content) {
                    if($point) {
                        if(strlen($content) > 1) {
                            $folder_arr[$i]['hasfile'] = 1;

                        }
                        $i++;
                        $point = false;
                    }

                    $findme = $folder_arr[$i]['name'];
                    $content = str_replace("\n", "", $content);
                    if($content == $findme . ":"){
                        $point = true;
                    }
                }
            }
        }

        return array_merge($folder_arr, $file_arr);
    }
}