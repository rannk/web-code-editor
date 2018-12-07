<?php
require_once ("PluginsObj.php");
/**
 * Class Basic: basic functions for Editor
 * @author: Rannk Deng
 * @email: rannk@163.com
 */
class Basic
{
    var $records_file;
    var $workspace_unique_id;

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
        $this->workspace_unique_id = md5($workspace .$host);
        $this->records_file = $cache_dir . "/editor_{$this->workspace_unique_id}.ed";
        setErrorAsException(__FILE__);
    }

    /**
     * get plugins configuration
     */
    function getPluginsData() {
        $pluginsObj = new PluginsObj();
        $pluginsObj->loadPlugins();
        $data['nav_menu'] = $pluginsObj->nav_menu_settings;
        $data['folder_menu'] = $pluginsObj->folder_menu_settings;
        $data['content_menu'] = $pluginsObj->content_menu_settings;
        $data['icons_menu'] = $pluginsObj->icons_menu_settings;
        $data['plugins_config'] = $pluginsObj->plugin_config;
        $data['plugins_modal'] = $pluginsObj->plugins_modal;

        return $data;
    }


    /**
     * get current workspace opening files from cache lists
     * @return mixed
     */
    public function getOpenFiles() {
        return $this->getParams("openfiles");
    }

    public function getParams($key) {
        if(file_exists($this->records_file)) {
            $handle = fopen($this->records_file, "r");
            if ($handle) {
                $contents = '';
                while (!feof($handle)) {
                    $contents .= fread($handle, 8192);
                }

                fclose($handle);

                $arr = json_decode($contents,true);

                return $arr[$key];
            }
        }
    }

    public function saveParams($key, $values) {
        try{
            $contents = "";
            if(file_exists($this->records_file)) {
                $handle = fopen($this->records_file, "r");
                while (!feof($handle)) {
                    $contents .= fread($handle, 8192);
                }

                fclose($handle);
            }

            $arr = json_decode($contents,true);

            $arr[$key] = $values;
            $handle = fopen($this->records_file, "w");
            if ($handle) {
                fwrite($handle, json_encode($arr));
            }
        }catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * get current workspace opening files and these files should exist in workspace.
     * @return array
     */
    public function getExistsOpenFile() {
        $arr = $this->getOpenFiles();
        $new_arr = array();

        //estimate the opening files exist in workspace
        //if the file not exist, remove it from cache list.
        $CI = & get_instance();
        $CI->load->library('RemoteControl');
        $RemoteControl = $CI->remotecontrol;

        if(count($arr) > 0) {
            foreach($arr as $k => $v) {
                if(!$v['file'])
                    continue;

                if(!$RemoteControl->fileExists(stringConvert($v['file'], CONVERT_STR_SITE_TO_SYSTEM))) {
                    $this->closeFile($v['file']);
                }else {
                    $new_arr[$k] = $v;
                }
            }
        }

        return $new_arr;
    }


    /**
     * add a file to opening lists
     * it will used by get opening files
     * @param $file full file path and file name, not have '/' at the end
     * @param $showname file name
     */
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

        $this->saveParams("openfiles", $records);
    }

    /**
     * rename the open file name in list cache
     * @param $file full file path and file name, not have '/' at the end
     * @param $new_filename
     */
    public function renameOpenFile($file, $new_filename) {
        $arr = $this->getOpenFiles();
        $id = md5($file);

        $file_arr = explode("/", $file);
        $file_arr[count($file_arr) - 1] = $new_filename;
        $new_file = implode("/", $file_arr);

        if(count($arr) > 0) {
            $new_arr = array();
            foreach($arr as $k => $v) {
                if($k == $id) {
                    $v['showname'] = $new_filename;
                    $v['file'] = $new_file;
                    $new_arr[md5($new_file)] = $v;
                }else {
                    $new_arr[$k] = $v;
                }
            }

            $this->saveParams("openfiles", $new_arr);
        }
    }

    /**
     * close a opening file, and set next opening file as active
     * @param $file full file path and file name, not have '/' at the end
     * @param $n_file next active file
     */
    public function closeFile($file, $n_file = "") {
        $id = md5($file);

        $old_records = $this->getOpenFiles();
        $active = $old_records[$id]['active'];
        unset($old_records[$id]);

        // if the closed file is active, set other file as active
        if($active) {
            if($n_file) {
                if(count($old_records) > 0) {
                    foreach($old_records as $k => $v) {
                        $old_records[$k]['active'] = "";
                    }
                }
                $n_id = md5($n_file);

                $old_records[$n_id]['active'] = "active";
            }else {
                // if not set which file as active
                // choice the first file as active
                if(count($old_records) > 0) {
                    foreach($old_records as $k => $v) {
                        $old_records[$k]['active'] = "active";
                        break;
                    }
                }
            }
        }

        $this->saveParams("openfiles", $old_records);
    }

    /**
     * 记录当前正在编辑的文件
     * @param $file_id
     */
    public function setActiveFile($file_id) {
        $records = $this->getOpenFiles();
        if($records[$file_id]['file']) {
            foreach($records as $k => $v) {
                if(!$v['file']) {
                    unset($records[$k]);
                    continue;
                }

                $records[$k]['active'] = "";
            }
            $records[$file_id]['active'] = "active";

            $this->saveParams("openfiles", $records);
        }
    }
}