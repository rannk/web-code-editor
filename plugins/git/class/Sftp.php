<?php
require_once (__DIR__ . "/../../../application/libraries/Controls/SftpControl.php");
class Sftp extends SftpControl
{

    public function getTrackFiles() {
        $cmd = "cd {$this->workspace_dir} && git status";

        try{
            $this->cmd($cmd, $content);
        }catch (Exception $e) {
            return $e->getMessage();
        }

        $lines = explode("\n", $content);
        $j = 0;
        for($i=0;$i<count($lines);$i++) {
            $line = $lines[$i];
            if(stripos($line, "not staged for commit")) {
                $track = "m";
            }
            if(stripos($line, "ntracked files")) {
                $track = "n";
            }

            if(stripos($line, "changes added to commit")) {
                $track = "";
            }

            if($track == "m") {
                preg_match("/(modified|deleted):[ \"]*([\w\-.\/x80-xff]{1,})/", $line, $matches);
                if($matches[2]) {
                    $lists[$j]['name'] = stringConvert(asciiToChar($matches[2]) , 1);
                    $lists[$j]['status'] = $matches[1];
                    $j++;
                }
            }

            if($track == "n") {
                if(stripos($line, "ntracked files") || stripos($line, "what will be committed)")) {
                    continue;
                }
                preg_match("/([\w\-.\/x80-xff]{1,})/", $line, $matches);
                if($matches[0]) {
                    if(substr($matches[0], -1) == "/") {
                        $file_lists = $this->getFolderFiles("/" . substr($matches[0], 0, -1));
                        for($f_i=0;$f_i<count($file_lists);$f_i++) {
                            $lists[$j]['name'] = substr($file_lists[$f_i], 1);
                            $lists[$j]['status'] = "New";
                            $j++;
                        }
                    }else {
                        $lists[$j]['name'] = stringConvert(asciiToChar($matches[0]), 1);
                        $lists[$j]['status'] = "New";
                        $j++;
                    }
                }
            }

        }

        return $lists;
    }

    public function getFolderFiles($path) {
        $arr = array();
        $file_lists = $this->getFolderLists($path);
        for($i=0;$i<count($file_lists);$i++) {
            $file = $file_lists[$i];
            if($file['type'] == "folder") {
                $arr = array_merge($arr, $this->getFolderFiles($file['file']));
            }else {
                $arr[] = $file['file'];
            }
        }

        return $arr;
    }

    public function commitFiles($file, $file_del, $message) {
        $error = "";


        if($file == "" && $file_del == "") {
            return "can't cmmit without files";
        }

        if($message == "") {
            return "the message can't be empty";
        }

        if($file) {
            $cmd = "cd {$this->workspace_dir} && git add " . $file;
            try{
                $this->cmd($cmd, $content);
            }catch (Exception $e) {
                $error .= $e->getMessage() . "\n";
            }
        }

        if($file_del) {
            $cmd = "cd {$this->workspace_dir} && git rm " . $file_del;
            try{
                $this->cmd($cmd, $content);
            }catch (Exception $e) {
                $error .= $e->getMessage() . "\n";
            }
        }

        if($error) {
            return $error;
        }


        $cmd = "cd {$this->workspace_dir} && git commit -m \"" . $message . "\"";

        try{
            $this->cmd($cmd, $content);
            return $content;
        }catch (Exception $e) {
            return $e->getMessage();
        }
    }
}