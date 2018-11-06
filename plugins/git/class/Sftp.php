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
                    $lists[$j]['name'] = stringConvert(asciiToChar($matches[0]), 1);
                    $lists[$j]['status'] = "New";
                    $j++;
                }
            }

        }

        return $lists;
    }
}