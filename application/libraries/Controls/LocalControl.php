<?php
/**
 * connect server's local workspace
 * @author Rannk
 *
 */
require_once ("Control.php");

class LocalControl implements Control
{
    var $workspace_dir;
    var $tmp_dir;

    public function connect(){

    }

    public function setCI(& $CI) {
        $params['workspace_dir'] = $CI->config->item("workspace_dir");
        $params['temp_dir'] = $CI->config->item("temp_file_dir");

        $this->setConfiguration($params);
    }

    public function setConfiguration($params) {
        $this->workspace_dir = $params['workspace_dir'];
        $this->tmp_dir = $params['temp_dir'];
    }

    public function getFileContent($filename){
        if(!$this->tmp_dir)
            return;

        $local_file = $this->workspace_dir . $filename;
        if(substr($local_file, -1) == "/") {
            $local_file = substr($local_file, 0,-1);
        }

        if(file_exists($local_file)) {
            $handle = fopen($local_file, "r");
            $contents = '';
            while (!feof($handle)) {
                $contents .= fread($handle, 8192);
            }
            fclose($handle);
        }

        return $contents;
    }

    public function saveFile($from, $to) {
        if(!file_exists($from))
            return;

        $handle = fopen($from, "r");
        $contents = '';
        while (!feof($handle)) {
            $contents .= fread($handle, 8192);
        }
        fclose($handle);

        $handle = fopen($this->workspace_dir . $to, "w");
        fwrite($handle, $contents);
        fclose($handle);
    }

    public function getFolderLists($path = ""){
        if($path == "/") {
            $path = "";
        }

        $workspace = $this->workspace_dir . $path;
        $d = dir($workspace);
        $folder_arr = array();
        $file_arr = array();
        while (false !== ($entry = $d->read())) {
            if($entry == "." || $entry == ".." || $entry == ".git")
                continue;
            $input = array();

            if(is_dir($workspace . "/" . $entry)) {
                $input['name'] = $entry;
                $input['hasfile'] = 0;
                $input['type'] = "folder";
                $input['file_id'] = md5($path . "/" . $entry);
                $input['file'] = $path . "/" . $entry;

                $d_next = dir($workspace . "/" . $entry);
                while (false !== ($entry_next = $d_next->read())) {
                    if($entry_next == "." || $entry_next == "..")
                        continue;

                    $input['hasfile'] = 1;
                    break;
                }

                $folder_arr[] = $input;
            }else {
                $input['name'] = $entry;
                $input['hasfile'] = 0;
                $input['type'] = "file";
                $input['file_id'] = md5($path . "/" . $entry);
                $input['file'] = $path . "/" . $entry;
                $file_arr[] = $input;
            }
        }

        $d->close();
        return array_merge($folder_arr, $file_arr);
    }

    public function renameFile($file, $newfile_name)
    {
        if(!$file || !$newfile_name)
            return;

        $workspace = $this->workspace_dir;

        if(substr($file, -1) == "/") {
            $file = substr($file, 0, -1);
        }

        $file_arr = explode("/", $file);

        $file_arr[count($file_arr) - 1] = $newfile_name;
        $source_file = implode("/", $file_arr);
        return rename($workspace . $file, $workspace . $source_file);
    }

    public function deleteFile($file) {
        if(!$file)
            return;
        $workspace = $this->workspace_dir;

        if(substr($file, -1) == "/") {
            $file = substr($file, 0, -1);
        }

        // 这里使用系统命令来删除目录
        if(substr(PHP_OS, 0, 3) == "WIN") {
            $path = str_replace("/", "\\", $workspace . $file);
            $cmd = "rd /s /q " . $path;
        }else {
            $cmd = "rm -fR " . $workspace . $file;
        }

        if(file_exists($workspace . $file)) {
            if(is_dir($workspace . $file)) {
                return $this->cmd($cmd);
            }else {
                return unlink($workspace . $file);
            }
        }

       return;
    }

    public function addFile($file)
    {
        $workspace = $this->workspace_dir;

        $filepath = addQuoteForString($workspace . $file);
        outputLog($filepath);
        if(file_exists($workspace . $file)) {
            throw new Exception("file already exists");
            return;
        }

        // 这里使用系统命令来添加文件
        if(substr(PHP_OS, 0, 3) == "WIN") {
            $path = str_replace("/", "\\", $filepath);
            $cmd = "type nul > " . $path;
        }else {
            $cmd = "touch " . $filepath;
        }

        return $this->cmd($cmd);
    }

    public function addFolder($file)
    {
        $workspace = $this->workspace_dir;

        if(substr(PHP_OS, 0, 3) == "WIN") {
            $path = str_replace("/", "\\", $workspace . $file);
        }else {
            $path = $workspace . $file;
        }

        // 这里使用系统命令来添加目录
        $cmd = "mkdir " . addQuoteForString($path);

        return $this->cmd($cmd);
    }

    public function fileExists($file) {
        $workspace = $this->workspace_dir;
        return file_exists($workspace . $file);
    }

    /**
     * 执行一个系统命令
     * 如果命令执行失败则抛出异常，否则返回true
     * @param $cmd
     * @return bool
     * @throws Exception
     */
    public function cmd($cmd, &$content = "") {
        $descriptorspec = array(
            0 => array("pipe", "r"),  // 标准输入，子进程从此管道中读取数据
            1 => array("pipe", "w"),  // 标准输出，子进程向此管道中写入数据
            2 => array("pipe", "w")
        );

        $proc = proc_open($cmd, $descriptorspec, $pipes, null, null);

        if ($proc == false) {
            throw new Exception("command run failed:" . $cmd);
        } else {
            $content = trim(stream_get_contents($pipes[1]));
            fclose($pipes[1]);
            $stderr = trim(stream_get_contents($pipes[2]));
            fclose($pipes[2]);
            $status = proc_close($proc);

            if($stderr) {
                // 如果命令行编码格式是GBK的，则转成UTF8输出
                if(mb_check_encoding($stderr, "GBK")) {
                    $stderr = iconv("GBK", "UTF-8", $stderr);
                }
                throw new Exception($stderr);
            }else {
                return true;
            }
        }
    }
}