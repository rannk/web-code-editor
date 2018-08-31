<?php

/**
 * Class Basic: basic functions for Editor
 * @author: Rannk Deng
 * @email: rannk@163.com
 */
class Basic
{
    var $records_file;
    function __construct(){
        $CI = & get_instance();
        $cache_dir = $CI->config->item("cache_file_dir");
        if($CI->config->item("connect_type") == "local") {
            $host = "local";
        }else {
            $host = $CI->config->item("conn_host");
        }

        $workspace = str_replace("\\", "/", $CI->config->item("workspace_dir"));
        $workspace = preg_replace('/(\/{0,}$)/', "", $workspace);
        $workspace_unique = md5($workspace .$host);
        $this->records_file = $cache_dir . "/editor_{$workspace_unique}.ed";
    }

    public function getOpenFiles() {

        if(file_exists($this->records_file)) {
            $handle = fopen($this->records_file, "r");
            if ($handle) {
                $contents = '';
                while (!feof($handle)) {
                    $contents .= fread($handle, 8192);
                }

                fclose($handle);

                return json_decode($contents,true);
            }
        }
    }

    public function addOpenFile($file, $showname) {
        $id = md5($file);
        $old_records = $this->getOpenFiles();

        $records[$id]['file'] = $file;
        $records[$id]['active'] = "active";
        $records[$id]['showname'] = $showname;

        if(count($old_records) > 0) {
            foreach($old_records as $k => $v) {
                $records[$k]['file'] = $v['file'];
                $records[$k]['showname'] = $v['showname'];
            }
        }

        $content = json_encode($records);

        $handle = fopen($this->records_file, "w");
        if ($handle) {
            fwrite($handle, $content);
        }

    }

    public function closeFile($file, $n_file) {
        $id = md5($file);

        $old_records = $this->getOpenFiles();
        unset($old_records[$id]);

        if($n_file) {
            if(count($old_records) > 0) {
                foreach($old_records as $k => $v) {
                    $old_records[$k]['active'] = "";
                }
            }
            $n_id = md5($n_file);

            $old_records[$n_id]['active'] = "active";
        }

        $content = json_encode($old_records);

        $handle = fopen($this->records_file, "w");
        if ($handle) {
            fwrite($handle, $content);
            fclose($handle);
        }
    }
}