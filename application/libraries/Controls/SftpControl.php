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

    public function getFolderLists($path = ""){
        if($path == "/") {
            $path = "";
        }

        $workspace = $this->workspace_dir . $path;

        $stream = "";

        $this->cmd( 'ls -la ' . $workspace, $stream);

        $folder_arr = array();
        $file_arr = array();
        $folder_str = "";
        $stream_arr = explode("\n", $stream);
        for($i=0;$i<count($stream_arr);$i++){
            $content = trim($stream_arr[$i]);
            if($content && substr($content, 0, 5) != "total") {
                $input = array();


                $arr = explode(" ", $content);
                $filename = $arr[count($arr) - 1];
                $filename = str_replace("\n", "", $filename);
                if($filename == "." || $filename == "..")
                    continue;

                $file_path = $path . "/" . $filename;

                $input['name'] = $filename;
                $input['hasfile'] = 0;
                $input['type'] = "";
                $input['file_id'] = md5($file_path);
                $input['file'] = $file_path;
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

        $this->cmd('cd ' . $workspace . " && ls ./ " . $folder_str, $stream);

        $i = 0;
        if($stream) {
            $stream_arr = explode("\n", $stream);
            for($ii=0;$ii<count($stream_arr);$ii++){
                $content = trim($stream_arr[$ii]);
                if($content) {
                    $findme = $folder_arr[$i]['name'];
                    $content = str_replace("\n", "", $content);
                    if($content == $findme . ":"){
                        if(trim(strlen($stream_arr[$ii+1])) > 1) {
                            $folder_arr[$i]['hasfile'] = 1;
                        }
                        $ii++;
                        $i++;
                    }
                }
            }
        }

        return array_merge($folder_arr, $file_arr);
    }

    public function renameFile($file, $newfile_name)
    {
        if(!$file || !$newfile_name)
            return;

        if(substr($file, -1) == "/") {
            $file = substr($file, 0, -1);
        }

        $file_arr = explode("/", $file);

        $file_arr[count($file_arr) - 1] = $newfile_name;
        $source_file = implode("/", $file_arr);

        $sftp = ssh2_sftp($this->connection);

        return  ssh2_sftp_rename($sftp, $this->workspace_dir . $file, $this->workspace_dir . $source_file);
    }

    public function deleteFile($file)
    {
        $workspace = $this->workspace_dir;
        $sftp = ssh2_sftp($this->connection);
        $fileinfo = @ssh2_sftp_stat($sftp, $workspace . $file);

        if($fileinfo['mode']) {
            // 判断是文件还是目录
            if(substr($fileinfo['mode'], 0, 2) == "16") {
                $sys = $this->getOsType();

                if($sys == "win") {
                    $path = str_replace("/", "\\", $workspace . $file);
                    $cmd = "rd /s /q " . $path;
                }else {
                    $cmd = "rm -fR " . $workspace . $file;
                }

                return $this->cmd($cmd);
            }else {
                return ssh2_sftp_unlink($sftp, $workspace . $file);
            }
        }
    }

    public function addFile($file) {
        $workspace = $this->workspace_dir;
        $sftp = ssh2_sftp($this->connection);
        $fileinfo = @ssh2_sftp_stat($sftp, $workspace . $file);

        // 判断文件不存在
        if(!$fileinfo) {
            $sys = $this->getOsType();

            if($sys == "win") {
                $path = str_replace("/", "\\", $workspace . $file);
                $cmd = "type nul > " . $path;
            }else {
                $cmd = "touch " . $workspace . $file;
            }

            return $this->cmd($cmd);
        }else {
            throw new Exception("The file already exists");
        }

        return false;
    }

    public function fileExists($file) {
        $workspace = $this->workspace_dir;

        $sftp = ssh2_sftp($this->connection);
        $realpath = @ssh2_sftp_realpath($sftp, $workspace . $file);

        if($realpath) {
            return true;
        }else {
            return false;
        }
    }

    public function addFolder($file)
    {
        if(!$this->fileExists($file)) {
            $workspace = $this->workspace_dir;
            $sftp = ssh2_sftp($this->connection);
            if(ssh2_sftp_mkdir($sftp, $workspace . $file)){
                return true;
            }else {
                throw new Exception("can't create the directory");
            }
        }else {
            throw new Exception("The file already exists");
        }
    }

    public function getOsType() {
        // 判断系统类型
        // 如果是win系统，则用win的命令删除目录,否则用rm
        $stream = ssh2_exec($this->connection, 'uname');
        stream_set_blocking($stream, true);
        $content = stream_get_contents($stream);
        $sys = "win";
        if(trim($content) == "Linux") {
            $sys = "linux";
        }
        // 如果是mac系统，也执行linux命令
        if(trim($content) == "Darwin") {
            $sys = "linux";
        }

        return $sys;
    }

    /**
     * 执行cmd操作
     * 如果cmd执行有错误，则把该错误以异常形式抛出
     * @param $cmd
     * @return bool
     * @throws Exception
     */
    public function cmd($cmd, &$content = "") {
        $stream = ssh2_exec($this->connection, $cmd);
        $stderr_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stream, true);
        stream_set_blocking($stderr_stream, true);
        $content = trim(stream_get_contents($stream));
        $err_msg = trim(stream_get_contents($stderr_stream));
        if(!$err_msg) {
            return true;
        }else {
            throw new Exception($err_msg);
        }
    }

}