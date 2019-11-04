<html>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="stylesheet" href="index.php/css/minify?lib/codemirror.css:lib/code_editor.css:lib/editor_modal.css:lib/ide_icon.css">
<link rel="stylesheet" href="lib/font-awesome.min.css">
<link rel="stylesheet" href="addon/hint/show-hint.css">
<script src="index.php/js/minify?lib/jquery.js:lib/md5.js:lib/code_editor.js:lib/editor_modal.js:lib/form.js:lib/float_div.js"></script>
<script src="lib/codemirror.js"></script>
<script src="mode/clike/clike.js"></script>
<script src="mode/xml/xml.js"></script>
<script src="mode/javascript/javascript.js"></script>
<script src="mode/htmlmixed/htmlmixed.js"></script>
<script src="mode/shell/shell.js"></script>
<script src="mode/sql/sql.js"></script>
<script src="addon/hint/show-hint.js"></script>
<script src="addon/hint/xml-hint.js"></script>
<script src="addon/hint/html-hint.js"></script>
<script src="lib/drag_div.js"></script>
<?php loadWorkspaceTypeJs()?>
<body <?php if (getDevModel()) echo 'dev_model="true"'?> project_title="<?=$project_title?>">
<div id="top_nav">
    <div class="nav_item" id="btn_workspace">
        <?=lang("workspace")?>
    </div>
    <div class="nav_item font_gray">
        |
    </div>
    <div class="nav_item">
        <?php
        /**
         * load navigation menu data from plugins
         * all data from /plugins directory
         */
        ?>
        <ul id="main_menu">
            <?php
            if(count($nav_menu) > 0) {
                $target_id = 1;
                foreach($nav_menu as $menus) {
                    $target = "";
                    if(is_array($menus) && count($menus['submenu']) > 0) {
                        $target = 'data-target="#d'.$target_id++.'"';
                        echo '<li '.$target.'>'.$menus['name'].'</li>';
                    }
                }
            }
            ?>
        </ul>
        <?php
        if(count($nav_menu) > 0) {
            $target_id = 1;
            foreach($nav_menu as $menus) {
                if(is_array($menus) && count($menus['submenu']) > 0) {
                    $content = '<div class="sub_menu"><ul class="menu_wrapper" id="d'.$target_id++.'">';
                    foreach($menus['submenu'] as $submenus) {
                        $content .= setPluginItem("li", $submenus);
                    }
                    echo $content . "</ul></div>";
                }
            }
        }
        // load menu data end
        ?>
    </div>
    <div id="touch_menu_icon" style="display: none"><span class="fa fa-tablet"></span></div>
</div>
<div style="width: 100%;position:relative;" id="editor_section">
    <div id="workspace" style="padding-top: 15px;">
        <div id="workspace_handle">|</div>
        <ul>
            <li type="folder" root="yes" name="workspace" file="/">
                <div class="filename"><span class="ide-icon ide-caret-right tag"></span><span class="fa fa-folder"></span><label>worksapce</label></div>
            </li>
        </ul>
    </div>
    <ul class="file-nav">
        <?php
       if(count($files)>0){
            foreach($files as $k => $v){
                echo '<li class="'.$v['active'].'"><a href="###" data-target="#'.$k.'" role="tab">'.$v['showname'].'</a><a class="close">X</a></li>';
            }
        }
       ?>
    </ul>
    <div class="tab-content" id="file_content_lists">
        <?php
        if(count($files)>0){
            foreach($files as $k => $v){
                ?>
                <div class="tab-pane" id="<?=$k?>" data-file="<?=$v['file']?>" data-id="text_<?=$k?>">
                    <div class="file_loading"><ul type="loading"><li><div class="loading"><span></span><span></span><span></span><span></span><span></span></div></li></ul></div>
                    <textarea id="text_<?=$k?>"></textarea>
                </div>
                <?php
            }
        }
        ?>

    <div style="min-height: 20px; min-width: 100%"></div>
</div>

    <div id="tab_bottom">
        <label id="filename_show"></label>
        <label id="icons_show">
        </label>
    </div>
</div>
<div id="touch_menu">
    <ul>
        <li><span class="fa fa-window-close" id="minimize_icon"></span></li>
        <!--<li><span class="fa fa-tasks" id="touch_menu_sub_btn"></span></li>-->
        <?php
        if(count($icons_menu) > 0) {
            foreach($icons_menu as $k => $v) {
                if(!is_array($v))
                    continue;
                echo setPluginItem("li", $v);
            }
        }
        ?>
    </ul>
    <div id="touch_menu_sub" style="display: none">
        <ul>
        </ul>
    </div>
</div>
<div id="modal_cover"></div>
<div id="code_declaration" style="display: none">
    <div class="header">Declaration</div>
    <ul class="list">
    </ul>
</div>
<?php
/**
 * load content menu data from plugins
 */
if(count($content_menu) > 0) {
    echo '<ul id="content_menu" class="menu_wrapper">';
    foreach($content_menu as $menus) {
        echo setPluginItem("li", $menus);
    }
    echo '</ul>';
}

/**
 * load folder menu data from plugins
 */
if(count($folder_menu) > 0) {
    echo '<ul id="folder_menu" class="menu_wrapper">';
    foreach($folder_menu as $menus) {
        echo setPluginItem("li", $menus);
    }
    echo '</ul>';
}

/**
 * 读取plugins的modal dialog
 */

if(is_array($plugins_modal) && count($plugins_modal) > 0) {
    foreach($plugins_modal as $v) {
        loadPluginsModal($v['plugin_dir'], $v['file']);
    }
}

/**
 * 读取plugins的基本配置信息
 * 会生成code_script元素在页面上，该元素设置的js文件会在页面加载完成后加载
 * 也就是说每个plugins的启动js文件在这里加载
 */
if(is_array($plugins_config) && count($plugins_config)>0) {
    foreach($plugins_config as $v) {
        echo setPluginItem("code_script", $v);
    }
}
?>

</body>
</html>