<?php
require_once (__DIR__ . "/../../../application/libraries/RemoteControl.php");
class GitControl extends RemoteControl
{
    var $workspace_dir;

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
            $this->workspace_dir = $this->connect_obj->workspace_dir;
        }
    }

    public function checkGitActived() {
        $cmd = $this->getGotoWorkspaceDirCmd() . "git branch";
        $content = "";
        try{
            $this->connect_obj->cmd($cmd, $content);
            if(stripos($content, "Not a git repository")) {
                return false;
            }
        }catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function getTrackFiles() {
        $cmd = $this->getGotoWorkspaceDirCmd() . "git status";
        $content = "";

        try{
            $this->connect_obj->cmd($cmd, $content);
        }catch (Exception $e) {
            return $e->getMessage();
        }

        $lines = explode("\n", $content);
        $j = 0;
        $branch = "";
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

            if($branch == "") {
                preg_match("/On branch ([\w\-.\/x80-xff]{1,})/", $line, $matches);
                if($matches[1]) {
                    $branch = $matches[1];
                }
            }

            if($track == "m") {
                preg_match("/(modified|deleted):[ \"]*([\w\-.\/x80-xff ]{1,})/", $line, $matches);
                if($matches[2]) {
                    $lists[$j]['name'] = asciiToChar($matches[2]);
                    $lists[$j]['status'] = $matches[1];
                    $j++;
                }
            }

            if($track == "n") {
                if(stripos($line, "ntracked files") || stripos($line, "what will be committed)") || stripos($line, "../")) {
                    continue;
                }
                preg_match("/([\w\-.\/x80-xff ]{1,})/", $line, $matches);
                if($matches[0]) {
                    if(substr($matches[0], -1) == "/") {
                        $file_lists = $this->getFolderFiles("/" . substr($matches[0], 0, -1));
                        for($f_i=0;$f_i<count($file_lists);$f_i++) {
                            $lists[$j]['name'] = stringConvert(substr($file_lists[$f_i], 1), 1);
                            $lists[$j]['status'] = "New";
                            $j++;
                        }
                    }else {
                        $lists[$j]['name'] = asciiToChar($matches[0]);
                        $lists[$j]['status'] = "New";
                        $j++;
                    }
                }
            }
        }

        $r['branch'] = $branch;
        $r['lists'] = $lists;

        return $r;
    }

    /**
     * commit 代码
     * 首先会add 代码，然后进行commit
     * @param $file
     * @param $file_del
     * @param $message
     * @return string
     */
    public function commitFiles($file, $file_del, $message, $name, $email) {
        $error = "";
        $content = "";

        if($file == "" && $file_del == "") {
            return "can't cmmit without files";
        }

        if($message == "") {
            return "the message can't be empty";
        }

        if(!$name || !$email) {
            return "please fill the author and email";
        }

        $cmd = $this->getGotoWorkspaceDirCmd() . "git config --global user.name " . addQuoteForString($name);
        try{
            $this->connect_obj->cmd($cmd);
        }catch (Exception $e) {
            return $e->getMessage();
        }

        $cmd = $this->getGotoWorkspaceDirCmd() . "git config --global user.email " . addQuoteForString($email);
        try{
            $this->connect_obj->cmd($cmd);
        }catch (Exception $e) {
            return $e->getMessage();
        }

        if($file) {
            $cmd = $this->getGotoWorkspaceDirCmd() . "git add " . $file;
            try{
                $this->connect_obj->cmd($cmd, $content);
            }catch (Exception $e) {
                $error .= $e->getMessage() . "\n";
            }
        }

        if($file_del) {
            $cmd = $this->getGotoWorkspaceDirCmd() . "git rm " . $file_del;
            try{
                $this->connect_obj->cmd($cmd, $content);
            }catch (Exception $e) {
                $error .= $e->getMessage() . "\n";
            }
        }

        if($error) {
            return $error;
        }


        $cmd = $this->getGotoWorkspaceDirCmd() . "git commit -m \"" . $message . "\"";

        try{
            $this->connect_obj->cmd($cmd, $content);
            return $content;
        }catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 获取git branch列表
     * @return array|string
     */
    public function getBranchs() {
        $cmd =  $this->getGotoWorkspaceDirCmd() . "git branch";
        $content = "";
        try{
            $this->connect_obj->cmd($cmd, $content);
        }catch (Exception $e) {
            return $e->getMessage();
        }

        $lines = explode("\n", $content);

        $lists = array();
        for($i=0;$i<count($lines);$i++) {
            $lists[$i]['selected'] = "";
            if(substr($lines[$i], 0, 1) == "*") {
                $lines[$i] = str_replace("*", "", $lines[$i]);
                $lists[$i]['selected'] = "selected";
            }
            $lists[$i]['name'] = trim($lines[$i]);
        }

        return $lists;
    }

    public function getCurrentBranch() {
        $lists = $this->getBranchs();
        if(is_array($lists)) {
            foreach($lists as $v) {
                if($v['selected'] == "selected") {
                    return $v['name'];
                }
            }
        }else{
            throw new Exception($lists);
        }
    }

    public function getLastestCommit($n = 1) {
        $cmd = $this->getGotoWorkspaceDirCmd() . "git log -n " . $n;
        $content = "";

        try{
            $this->connect_obj->cmd($cmd, $content);
        }catch (Exception $e) {
            $msg = $e->getMessage();
            return $msg;
        }

        return $content;
    }

    public function getRemote() {
        $cmd = $this->getGotoWorkspaceDirCmd() . "git remote";
        $content = "";

        $arr = array();
        try{
            $this->connect_obj->cmd($cmd, $content);
        }catch (Exception $e) {
            $msg = $e->getMessage();
            return $msg;
        }

        if($content) {
            $arr = explode("\n", $content);
        }

        return $arr;
    }

    public function checkout($branch, $new_branch = "") {
        $cmd = $this->getGotoWorkspaceDirCmd() . "git checkout " . $branch;
        $content = "";

        try{
            $this->connect_obj->cmd($cmd, $content);
        }catch (Exception $e) {
            $msg = $e->getMessage();
            if(stripos($msg, "Already on") === 0 ||
                stripos($msg, "Switched to branch") === 0){
                $content = $msg;
            }
            else{
                return $msg;
            }
        }
        if($new_branch) {
            $cmd = $this->getGotoWorkspaceDirCmd() . "git branch " . $new_branch;
            try{
                $this->connect_obj->cmd($cmd);
            }catch (Exception $e) {
                return $e->getMessage();
            }

            try{
                $this->connect_obj->cmd($this->getGotoWorkspaceDirCmd() . "git checkout " . $new_branch, $content);
            }catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return $content;
    }

    public function pull() {
        $cmd = $this->getGotoWorkspaceDirCmd() . "git pull";
        $content = "";

        try{
            $this->connect_obj->cmd($cmd, $content);
        }catch (Exception $e) {
            $msg = $e->getMessage();
            return $msg;
        }

        return $content;
    }


    public function push ($remote, $force = false) {
        $content = "";
        $f = "";
        if($force) {
            $f = "-f ";
        }
        $branch = "";
        $branchs = $this->getBranchs();
        if(is_array($branchs)) {
            foreach($branchs as $v) {
                if($v['selected'] == "selected") {
                    $branch = $v['name'];
                }
            }
        }else{
            throw new Exception($branchs);
        }

        if($branch) {
            $cmd = $this->getGotoWorkspaceDirCmd() . "git push $f $remote $branch";
            try{
                $this->connect_obj->cmd($cmd, $content);
            }catch (Exception $e) {
                $msg = $e->getMessage();
                throw new Exception($msg);
            }
        }

        return $content;
    }

    /**
     * 获取全局用户信息
     * @return mixed
     * @throws Exception
     */
    public function getGlobal() {
        $cmd = $this->getGotoWorkspaceDirCmd() . "git config --global user.name";
        $content = "";

        try{
            $this->connect_obj->cmd($cmd, $content);
        }catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $arr['name'] = trim($content);

        $cmd = $this->getGotoWorkspaceDirCmd() . "git config --global user.email";
        $content = "";

        try{
            $this->connect_obj->cmd($cmd, $content);
        }catch (Exception $e) {
            throw new Exception($e->getMessage());
        }


        $arr['email'] = trim($content);

        return $arr;
    }

    /**
     * 获取目录文件列表
     * @param $path
     * @return array
     */
    public function getFolderFiles($path) {
        $path = asciiToChar($path);
        $arr = array();
        $file_lists = $this->connect_obj->getFolderLists($path, array(), array(".git"));

        // git文件中不应该有.git, node_moduless(NODE repo)文件
        for($i=0;$i<count($file_lists);$i++) {
            if($file_lists[$i]['name'] == ".git" || $file_lists[$i]['name'] == "node_modules") {
                return $arr;
            }
        }

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
}