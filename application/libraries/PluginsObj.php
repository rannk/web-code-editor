<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/30
 * Time: 15:30
 */
class PluginsObj
{
    var $nav_menu_settings = array();
    var $folder_menu_settings = array();
    var $content_menu_settings = array();
    var $icons_menu_settings = array();
    var $plugin_config = array();
    var $plugins_modal = array();
    var $lang_temp;

    public function __construct()
    {
        $default_settings['file']['name'] = lang("file");
        $default_settings['tools']['name'] = lang("tools");
        $this->nav_menu_settings = $default_settings;
    }

    public function loadPlugins() {
        $d = dir(__DIR__ . "/../../plugins");
        while (false !== ($entry = $d->read())) {

            if($entry == "." || $entry == "..") {
                continue;
            }

            if(is_dir(__DIR__ . "/../../plugins/" . $entry))
                $plugins_dir[] = $entry;

        }
        $d->close();

        for($i=0;$i<count($plugins_dir);$i++) {
            $dir_name = $plugins_dir[$i];
            $config_file = __DIR__ . "/../../plugins/" . $dir_name . "/config.yml";

            if(file_exists($config_file)) {
                $config_arr = spyc_load_file($config_file);
                $this->loadPluginsLang($dir_name);
                $this->organizeConfig($this->setLangForConfig($config_arr), $dir_name);

                $this->nav_menu_settings = $this->addSectionLineClass($this->nav_menu_settings);
                $this->folder_menu_settings = $this->addSectionLineClass($this->folder_menu_settings);
                $this->content_menu_settings = $this->addSectionLineClass($this->content_menu_settings);
            }
        }
    }

    public function loadPluginsLang($dirname) {
        $lang_dir = get_instance()->config->item("language");
        if(!$lang_dir) {
            $lang_dir = "english";
        }

        if(!file_exists(__DIR__ . "/../../plugins/" . $dirname . "/language/" . $lang_dir)) {
            $lang_dir = "english";
        }

        $lang = "";
        if(is_dir(__DIR__ . "/../../plugins/" . $dirname . "/language/" . $lang_dir)) {
            $d = dir(__DIR__ . "/../../plugins/" . $dirname . "/language/" . $lang_dir);
            while (false !== ($entry = $d->read())) {

                if($entry == "." || $entry == ".." || !stripos($entry, "_lang.php")) {
                    continue;
                }

                include (__DIR__ . "/../../plugins/" . $dirname . "/language/" . $lang_dir . "/" . $entry);
            }

            $this->lang_temp = $lang;
            $d->close();
        }
    }

    /**
     * 为配置文件中的语言变量设置语言
     * @param $config_arr
     * @return array
     */
    public function setLangForConfig($config_arr) {
        if(count($config_arr) > 0 && is_array($config_arr)) {
            foreach ($config_arr as $k => $v) {
                $config_arr[$k] = $this->setLangForConfig($v);
            }

            return $config_arr;
        }

        if(is_string($config_arr)) {
            if(strlen($config_arr)>5 && substr($config_arr, 0, 5) == '$lang') {
                $arr = explode(":", $config_arr);
                if(count($arr) == 2) {
                    return $this->lang_temp[trim($arr[1])];
                }
            } else {
                return $config_arr;
            }
        }
    }

    /**
     * 整合Plugins 的配置文件
     * 重复key的配置
     * @param $config_arr
     */
    public function organizeConfig($config_arr, $plugin_dir) {
        if(count($config_arr) > 0 && is_array($config_arr)) {
            $default_plugins = "";

            foreach ($config_arr as $k => $v) {
                if($k == "plugin_config") {
                    $default_plugins = $v;
                    $v['plugin_dir'] = $plugin_dir;
                    $this->plugin_config[] = $v;
                    break;
                }
            }

            foreach($config_arr as $k => $v) {
                //设置每个plugin默认的配置信息
                if($default_plugins['version']) {
                    $v = $this->addDefaultAttr($v, "version", $default_plugins['version']);
                }
                $v = $this->addDefaultAttr($v, "plugin_dir", $plugin_dir);

                //设置顶部导航栏中菜单的配置信息
                if($k == "nav_menu") {
                    $this->nav_menu_settings = pluginConfigMerge($this->nav_menu_settings, $v);
                }

                //设置鼠标右键workspace中文件出来菜单的配置信息
                if($k == "folder_menu") {
                    $this->folder_menu_settings = pluginConfigMerge($this->folder_menu_settings, $v);
                }

                //设置鼠标右键出来的菜单配置信息
                if($k == "content_menu") {
                    $this->content_menu_settings = pluginConfigMerge($this->content_menu_settings, $v);
                }

                //设置编辑器上图标菜单的配置信息
                if($k == "icons_menu") {
                    $this->icons_menu_settings = pluginConfigMerge($this->icons_menu_settings, $v);
                }

                //设置modal的配置信息
                //如果配置文件modal是个数组，则设置多个modal记录，如果是字符串，则设置一个
                if($k == "modal" && $v) {
                    $modal_count = count($this->plugins_modal);
                    if(is_array($v)) {
                        if(count($v) > 0) {
                            foreach($v as $k_modal => $v_modal) {
                                $this->plugins_modal[$modal_count]['plugin_dir'] = $plugin_dir;
                                $this->plugins_modal[$modal_count]['file'] = checkFilePath($plugin_dir . "/modal", $v_modal);
                                $modal_count++;
                            }
                        }
                    }else{
                        $this->plugins_modal[$modal_count]['plugin_dir'] = $plugin_dir;
                        $this->plugins_modal[$modal_count]['file'] = checkFilePath($plugin_dir . "/modal", $v);;
                        $modal_count++;
                    }
                }
            }
        }
    }

    /**
     * 插件设置默认属性项
     * 比如version, 如果子项没有version, 则调用插件默认配置的version
     * @param $arr
     * @param $key
     * @param $value
     * @return array
     */
    public function addDefaultAttr($arr, $key, $value, $add_attr = true) {
        if(is_array($arr) && count($arr) > 0) {
            if($add_attr) {
                if(!$arr[$key]) {
                    $arr[$key] = $value;
                }
            }

            foreach($arr as $k => $v) {
                if($k == "submenu") {
                    $add_attr = false;
                }else {
                    $add_attr = true;
                }
                $arr[$k] = $this->addDefaultAttr($v, $key, $value, $add_attr);
            }
        }

        return $arr;
    }

    public function addSectionLineClass($arr) {
        if(!is_array($arr))
            return $arr;

        $dir = "";

        foreach($arr as $k => $v) {
            if(is_array($v)) {
                if($dir != "" && $v['plugin_dir'] != $dir) {
                    $v['section_class'] = 1;
                    $arr[$k] = $v;
                }

                if($v['plugin_dir'] != "")
                    $dir = $v['plugin_dir'];

                $arr[$k] = $this->addSectionLineClass($v);
            }
        }

        return $arr;
    }
}