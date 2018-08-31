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

    public function getFolderLists($path = "/"){
        $workspace = $this->workspace_dir . $path;
        $d = dir($workspace);
        $folder_arr = array();
        $file_arr = array();
        while (false !== ($entry = $d->read())) {
            if($entry == "." || $entry == "..")
                continue;
            $input = array();

            if(is_dir($workspace . "/" . $entry)) {
                $input['name'] = $entry;
                $input['hasfile'] = 0;
                $input['type'] = "folder";

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
                $file_arr[] = $input;
            }
        }
        $d->close();
        return array_merge($folder_arr, $file_arr);
    }
}