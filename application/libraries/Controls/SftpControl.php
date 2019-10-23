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
    var $workspace_type;
    var $connection;
    var $tmp_dir;

    public function __construct()
    {
        setErrorAsException(__FILE__);
    }

    public function connect(){
        try{
            $this->connection = ssh2_connect($this->workspace_host, $this->port);
        }catch (Exception $e) {
            throw new Exception("sftp connect failed");
        }


        if($this->connection)
            ssh2_auth_password($this->connection,  $this->username, $this->password);
    }

    public function setCI(& $CI) {
        $params['username'] = $CI->config->item("conn_username");
        $params['workspace_host'] = $CI->config->item("conn_host");
        $params['password'] = $CI->config->item("conn_password");
        $params['workspace_dir'] = $CI->config->item("workspace_dir");
        $params['port'] = $CI->config->item("conn_port");
        $params['temp_dir'] = $CI->config->item("temp_file_dir");
        $params['workspace_type'] = $CI->config->item("workspace_type");

        $this->setConfiguration($params);
    }

    public function setConfiguration($params) {
        $this->username = $params['username'];
        $this->workspace_host = $params['workspace_host'];
        $this->password = $params['password'];
        $this->workspace_dir = $params['workspace_dir'];
        $this->port = $params['port'];
        $this->tmp_dir = $params['temp_dir'];
        $this->workspace_type = $params['workspace_type'];
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
        if(!$this->connection)
            return;

        if(!file_exists($from))
            return;

        ssh2_scp_send($this->connection, $from, $this->workspace_dir . $to);
    }

    public function getFolderLists($path = "", $ignore_files = array(), $keep_files = array()){
        if(!$this->connection)
            return;

        if($path == "/") {
            $path = "";
        }

        $workspace = $this->workspace_dir . $path;

        $workspace = str_replace(" ", "\ ", $workspace);

        $stream = "";

        $this->cmd( 'ls -la ' . $workspace, $stream);

        $folder_arr = array();
        $file_arr = array();
        $folder_str = "";
        $stream_arr = explode("\n", $stream);

        $ignore_files[] = ".";
        $ignore_files[] = "..";
        $ignore_files[] = ".git";

        for($i=0;$i<count($stream_arr);$i++){
            $content = trim($stream_arr[$i]);
            if($content && substr($content, 0, 5) != "total") {
                $input = array();


                $arr = explode(" ", $content);

                $filename = "";
                $key_i = 0;
                for($f_i=0;$f_i<count($arr);$f_i++) {
                    if($arr[$f_i] != "") {
                        $key_i++;
                    }
                    if($key_i > 8) {
                        if($f_i >= count($arr) - 1) {
                            $filename .= $arr[$f_i];
                        }else {
                            $filename .= $arr[$f_i] . " ";
                        }
                    }
                }

                $filename = str_replace("\n", "", $filename);

                if(in_array($filename, $ignore_files) && !in_array($filename, $keep_files))
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
                    $folder_str .= addQuoteForString($filename) ." ";
                }

                if($type == "-") {
                    $input['type'] = "file";
                    $file_arr[] = $input;
                }

            }
        }

        try{
            $this->cmd('cd ' . $workspace . " && ls ./ " . $folder_str, $stream);
        }catch (Exception $e){

        }

        if($stream) {
            $stream_arr = explode("\n", $stream);

            for($i=0;$i<count($folder_arr);$i++) {
                $findme = $folder_arr[$i]['name'];
                for($j=0;$j<count($stream_arr);$j++) {
                    $content = trim($stream_arr[$j]);
                    if($content) {
                        $content = str_replace("\n", "", $content);
                        if($content == $findme . ":"){
                            if(trim(strlen($stream_arr[$j+1])) > 0) {
                                $folder_arr[$i]['hasfile'] = 1;
                            }
                            break;
                        }
                    }
                }
            }
        }

        return array_merge($folder_arr, $file_arr);
    }

    public function renameFile($file, $newfile_name)
    {
        if(!$this->connection)
            return;

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
        if(!$this->connection)
            return;

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
        if(!$this->connection)
            return;

        $workspace = $this->workspace_dir;
        $sftp = ssh2_sftp($this->connection);
        try{
            $fileinfo = ssh2_sftp_stat($sftp, $workspace . $file);
        }catch (Exception $e){}

        // 判断文件不存在
        if(!$fileinfo) {
            $sys = $this->getOsType();

            if($sys == "win") {
                $path = str_replace("/", "\\", $workspace . $file);
                $cmd = "type nul > " . str_replace(" ", "\ ", $path);
            }else {
                $cmd = "touch " . str_replace(" ", "\ ", $workspace . $file);
            }

            return $this->cmd($cmd);
        }else {
            throw new Exception("The file already exists");
        }

        return false;
    }

    public function fileExists($file) {
        if(!$this->connection)
            return;

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
        if(!$this->connection)
            return;

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
        if(!$this->connection)
            return;

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
    public function cmd($cmd, &$content = "", $output_callback = false) {
        if(!$this->connection)
            return;

        $stream = ssh2_exec($this->connection, $cmd);
        $stderr_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stream, true);
        stream_set_blocking($stderr_stream, true);

        $content = "";
        while (($buffer = fgets($stream, 4096)) !== false) {
            if($output_callback) {
                call_user_func($output_callback, $buffer);
            }
            $content .= $buffer;
        }

        $err_msg = trim(stream_get_contents($stderr_stream));
        if(!$err_msg) {
            return true;
        }else {
            throw new Exception($err_msg);
        }
    }

    public function getHintContent() {
        if(!$this->connection)
            return;

        switch($this->workspace_type) {
            case "php":
                ssh2_scp_send($this->connection, __DIR__ . '/../HintCol/php_class_col.php', '/tmp/php_class_col.php');
                $this->cmd('php /tmp/php_class_col.php "'.$this->workspace_dir.'"', $content);
                $this->cmd("rm -f /tmp/php_class_col.php");
                return $content;
                break;
            default:
                return;
        }
    }
}