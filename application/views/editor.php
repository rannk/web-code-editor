<html>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="stylesheet" href="index.php/css/minify?lib/codemirror.css:lib/code_editor.css">
<link rel="stylesheet" href="lib/font-awesome.min.css">
<link rel="stylesheet" href="addon/hint/show-hint.css">
<script src="index.php/js/minify?lib/jquery.js:lib/md5.js:lib/code_editor.js"></script>
<script src="lib/codemirror.js"></script>
<script src="mode/clike/clike.js"></script>
<script src="mode/xml/xml.js"></script>
<script src="mode/javascript/javascript.js"></script>
<script src="mode/htmlmixed/htmlmixed.js"></script>
<script src="mode/php/php.js"></script>
<script src="mode/shell/shell.js"></script>
<script src="mode/sql/sql.js"></script>
<script src="addon/hint/show-hint.js"></script>
<script src="addon/hint/xml-hint.js"></script>
<script src="addon/hint/html-hint.js"></script>
<body>
<div id="top_nav">
    <div class="nav_item" id="btn_workspace">
       workspace
    </div>
    <div class="nav_item font_gray">
        |
    </div>
</div>

<div style="width: 100%;position:relative;" id="editor_section">
    <div id="workspace" style="padding-top: 15px;">
        <ul>
            <li type="folder" root="yes" name="workspace">
                <div class="filename"><span class="fa fa-chevron-right tag"></span><span class="fa fa-folder"></span><label>worksapce</label></div>
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
    <label id="filename_show">xx</label>
</div>
</body>
</html>