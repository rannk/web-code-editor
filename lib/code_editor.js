/**
 * main program of this editor
 * @author Rannk
 */
var menu_click_event = false;
var event_modal_show = false;
var js_cache;

function ce_editor(){
    this.cm = [];
    this.content_id;
    this.action;

    var content_active_callback = [];
    var content_change_callback = [];

    /**
     * 获取当前编辑器的内容
     * @returns {*}
     */
    this.getContent = function(){
        if(typeof (this.cm[this.content_id].cm) == "object") {
            return this.cm[this.content_id].cm.getValue();
        }
    }

    /**
     * 获取当前活跃的编辑器
     * @returns {Array|*|null}
     */
    this.getCM = function () {
        return this.cm[this.content_id].cm;
    }

    //保存当前活跃编辑器的内容
    this.saveContent = function(){

        if(typeof (this.cm[this.content_id].cm) == "object") {

            if($("a[data-target='" + this.content_id + "']").parent("li").children("span").hasClass("saving") || $("a[data-target='" + this.content_id + "']").parent("li").children("span").length == 0) {
                return;
            }

            $("a[data-target='" + this.content_id + "']").parent("li").children("span").addClass("saving");
            var data = {file: $(this.content_id).attr("data-file"),content: this.getContent()};

            $.ajax({
                type: "post",
                url: "index.php/api/save_file",
                data: data,
                success: function(data){
                    console.log(data);
                    // 如果没有其它正在保存的操作，则去除保存中的标签
                    if($("a[data-target='" + $.editor.content_id + "']").parent("li").children("span").hasClass("saving")) {
                        $("a[data-target='" + $.editor.content_id + "']").parent("li").children("span").remove();
                    }
                }
            });
        }
    }

    /**
     * 根据content_id关闭内容页
     * @param content_id
     */
    this.closeContent = function (content_id) {
        var file_nav_obj = $("a[data-target='#"+content_id+"']").parent("li");
        // 如果给定的内容页不存在，直接退出
        if(!file_nav_obj)
            return;

        var content_id = "#" + content_id;
        var new_data_file = "";
        $("#filename_show").html("");
        if(file_nav_obj.attr("class") == "active") {
            var f_index = 0;
            $(".file-nav li").each(function (index) {
                if($(this).attr("class") == "active") {
                    f_index = index - 1;
                }
            });

            if(f_index == -1) {
                f_index = 1;
            }

            if($(".file-nav li")[f_index] != undefined) {
                $(".file-nav li:nth-child("+(f_index+1)+")").addClass("active");
                var new_content_id = $(".file-nav li:nth-child("+(f_index+1)+")").children("a[role='tab']").attr("data-target");
                new_data_file = $(new_content_id).attr("data-file");
            }
        }

        $.ajax({
            type: "post",
            url: "index.php/api/closeFile",
            data: {action:"close", file: $(content_id).attr("data-file"), n_file:new_data_file},
            success: function(data){

            }
        });

        $(content_id).remove();
        file_nav_obj.remove();
        showActiveContent();
    }

    /**
     * 根据content_id从目录中移除
     * @param content_id
     */
    this.removeFileFromFolder = function(content_id){
        var file_type = $("li[file_id='"+content_id+"']").attr("type");
        //如果删除的文件是目录，则遍历打开的文件
        //查看是否有属于该目录下的文件，如果有则进行关闭
        if(file_type == "folder") {
            var folder_path = $("li[file_id='"+content_id+"']").attr("file");
            $("#file_content_lists .tab-pane").each(function(){
                var data_file = $(this).attr("data-file");
                if(data_file.indexOf(folder_path) == 0) {
                    $.editor.closeContent($(this).attr("id"));
                }
            });
        }else {
            this.closeContent(content_id);
        }

        $("li[file_id='"+content_id+"']").remove();
    }
    /**
     * 添加新的文件到目录，并打开。
     */
    this.addNewFileToFolder = function (folder_id, file_id) {
        var folder_li_id;
        if(folder_id == "root" || !folder_id) {
            folder_li_id = "li[root='yes']";
        }else {
            folder_li_id = "li[file_id='" + folder_id + "']";
        }

        getFileLists(folder_li_id, function () {
            $("#file_selected").remove();
            $("li[file_id='"+file_id+"'] .filename").prepend('<div id="file_selected" class="selected" />');
            addNewTab(file_id);
        });
    }

    /**
     * 触发转换到活跃编辑器时的事件
     * @param f
     */
    this.onContentActive = function(f){
        content_active_callback[content_active_callback.length] = f;
    }

    /**
     * 触发编辑器内容变更时的事件
     * @param f
     */
    this.onContentChange = function(f){
        content_change_callback[content_change_callback.length] = f;
    }

    this.trigger = function(c) {
        if(c == "content.active") {
            for(var i=0;i<content_active_callback.length;i++) {
                content_active_callback[i]();
            }
        }

        if(c == "content.change") {
            for(var i=0;i<content_change_callback.length;i++) {
                content_change_callback[i]();
            }
        }
    }
}

function ce_editor_cm() {
    this.cm;
}

$.editor = new ce_editor();

$(document).ready(function(){
    $('#btn_workspace').click(function(){
        $('#workspace').animate({width:'toggle'},350);
    });

    if($("body").attr("dev_model") != "true") {
        js_cache = true;
    }

    $.ajaxSetup({
        cache: js_cache
    });

    // init editor section
    $("#editor_section").css("height", window.innerHeight - 60);
    $("#file_content_lists").css("height", window.innerHeight -105);
    addClick("#workspace label");

    showActiveContent();
    bindContentToglleClick();
    getFileLists("#workspace>ul>li");

    // add click event for content menu
    $(".menu_wrapper li").each(function (index) {
        var obj = $(this);
        obj.click(function(){
            var op_file = obj.attr("op_file");
            var action = obj.attr("action");
            var version = obj.attr("version");
            $.editor.action = action;
            if(op_file) {
                $.getScript(op_file + "?" + version);
            }
        });
    });

    // main menu click event
    $("#main_menu li").each(function () {
        $(this).click(function () {
            $(".menu_wrapper").hide();
            $(".menu_wrapper").attr("display_menu", "");
            $($(this).attr("data-target")).show();
            $($(this).attr("data-target")).attr("display_menu", "true");

            // 计算菜单应该出现的位置
            if($($(this).attr("data-target")).width() + $(this).offset().left > window.innerWidth){
                var left_pos = window.innerWidth - $($(this).attr("data-target")).width();
                if(left_pos < 0) {
                    left_pos = 0;
                }

                $($(this).attr("data-target")).parent().css("left", left_pos);
            }else {
                $($(this).attr("data-target")).parent().css("left", $(this).offset().left);
            }

            menu_click_event = true;
        })
    })

    $(".menu_wrapper li").each(function () {
        var obj = $(this);
        obj.mouseover(function () {
            obj.addClass("active");
        });

        obj.mouseout(function () {
            obj.removeClass("active");
        });
    })

    //取消右键点击的默认事件
    document.oncontextmenu = function(e){
        return false;
    };

    // 所有单击响应事件默认处理
    $(function(){
        $(document).bind("click",function(e){
            var target = $(e.target);

            if(event_modal_show) {
                if(target.closest(".modal[active='true']").length == 0){
                    $(".modal[active='true']").modal("blink");
                    return;
                }
            }

            if(menu_click_event) {
                menu_click_event = false;
                return;
            }
            if(target.closest(".menu_wrapper[display_menu='true']").length == 0){
                $(".menu_wrapper[display_menu='true']").hide();
                $(".menu_wrapper[display_menu='true']").attr("display_menu", "");
            }
        })
    })

    //读取所有插件自动加载的JS
    $("code_script").each(function (index) {
        var obj = $(this);
        var op_file = obj.attr("op_file");
        var version = obj.attr("version");
        $.getScript(op_file + "?" + version);
    });

    //点击空白页关闭workspace folder
    $("#file_content_lists").click(function () {
        if($("#workspace").css("display") == "block") {
            $('#workspace').animate({width:'hide'},350);
        }
    });
});



function getFileContent(content_id, code_editor) {
    var obj = $(content_id);
    var file = obj.attr("data-file");
    var element = obj.attr("data-id");
    $.ajax({
        type: 'get',
        url: "index.php/api/get_file_content?file="+file,
        success: function(data){
            $("#" + element).val(data);
            $("#" + element).parent("div").children(".file_loading").hide();
            var ext = getFileExt(file);

            var mode_name =  getFileModeNameByExt(ext);
            if(!mode_name) {
                mode_name = "php";
            }

            var myCodeMirror = CodeMirror.fromTextArea(document.getElementById(element), {
                lineNumbers: true,
                mode: mode_name
            });

            code_editor.cm = myCodeMirror;
            var content_id = element.replace("text_", "");

            myCodeMirror.on("change", function (cm, obj) {
                // add saving icon for saving file
                if($("a[data-target='#" + content_id + "']").parent("li").children("span").length == 0) {
                    $("a[data-target='#" + content_id + "']").parent("li").prepend('<span class="fa fa-circle" />');
                }else {
                    $("a[data-target='#" + content_id + "']").parent("li").children("span").removeClass("saving");
                }

                // auto complete operation
                var reg = /^[a-zA-Z]$/;
                if(obj.text.toString().length == 1 && reg.exec(obj.text.toString()) != null) {
                    cm.showHint();
                }

                $.editor.trigger("content.change");
            })

            myCodeMirror.setOption("extraKeys", {
                'Ctrl-S': function(cm) {
                    $.editor.saveContent();
                }
            });
        }
    });
}

function openContentMenu() {
    var e = event || window.event;
    $(".menu_wrapper").hide();
    $(".menu_wrapper").attr("display_menu", "");
    var wrap = $("#content_menu");
    wrap.show();
    wrap.attr("display_menu", "true");
    wrap.css("left", e.clientX+'px');
    wrap.css("top" , e.clientY +'px');
}

function openFolderMenu() {
    var e = event || window.event;
    $(".menu_wrapper").hide();
    $(".menu_wrapper").attr("display_menu", "");
    var wrap = $("#folder_menu");
    wrap.show();
    wrap.attr("display_menu", "true");
    wrap.css("left", e.clientX+'px');
    wrap.css("top" , e.clientY +'px');
}

function showActiveContent() {
    $(".tab-content .tab-pane").hide();
    var content_id = $(".file-nav .active a[role='tab']").attr("data-target");
    if(content_id) {
        $(content_id).show();

        $.editor.content_id = content_id;
        if($( content_id + " .CodeMirror").length == 0) {
            var code_editor = new ce_editor_cm();
            getFileContent(content_id, code_editor);

            $.editor.cm[content_id] = code_editor;

            $(content_id).contextmenu(function (e) {
                openContentMenu();
            });
        }
        var f = $(content_id).attr("data-file");
        $("#filename_show").html("workspace: " + f);
        setActiveFile(content_id.replace("#", ""));
        $.editor.trigger("content.active");
    }
}

function setActiveFile(file_id) {
    var data = "file_id="+file_id;
    $.ajax({
        type: 'post',
        url: "index.php/api/setActiveFile",
        data: data,
        success: function (data) {
        }
    });
}

function bindContentToglleClick() {
    $(".file-nav li a[role='tab']").each(function(index){
        var objEvt = $._data($(this)[0], 'events');
        if (objEvt && objEvt['click']) {
        } else {
            $(this).click(function(e){
                $(".file-nav li").removeClass("active");
                $(this).parent("li").addClass("active");
                showActiveContent();
            });
        }
    });

    $(".file-nav li a.close").each(function(index){
        var objEvt = $._data($(this)[0], 'events');
        if (objEvt && objEvt['click']) {
        } else {
            $(this).click(function(e){
                var file_nav_obj = $(this).parent("li");
                var content_id = file_nav_obj.children("a[role='tab']").attr("data-target");
                content_id = content_id.replace("#", "");
                $.editor.closeContent(content_id);
            });
        }
    });
}

function getFileLists(name, callback) {
    var data = "dir=" + $(name).attr("file");
    $.ajax({
        type: 'post',
        url: "index.php/api/get_file_lists",
        data: data,
        success: function(data) {
            var content = "<ul>";
            $.each(data, function(idx, item){
                var left = "";
                content += "<li type='" + item.type + "' name='"+item.name+"' file_id='"+item.file_id+"' file='"+item.file+"'><div class='filename'>";
                if(item.hasfile == "1") {
                    content += "<span class='fa fa-chevron-right tag' />";
                }else {
                    left = "left20";
                }

                if(item.type == "folder") {
                    content += "<span class='fa fa-folder "+left+"' />";
                }

                if(item.type == "file") {
                    content += "<span class='fa fa-file-code-o "+left+"' />";
                }

                content += "<label>" + item.name + "</label>";
                content += "</div>";

                if(item.hasfile == "1") {
                    content += "<ul style='display: none' type='loading'><li><div class='loading'><span></span><span></span><span></span><span></span><span></span></div></li></ul>";
                }
                content += "</li>";
            });
            content += "</ul>";
            $(name).children("ul").remove();
            $(name).append(content);

            if(name instanceof $) {
                addClick(name.find("ul label"));
            }else {
                addClick(name + " ul label");
            }

            if(callback) {
                callback();
            }

        },
        dataType: "json"
    });
}

function addClick(element) {
    $(element).each(function(index){
        var check_double = 0;

        // select the file or folder
        $(this).click(function(e){
            check_double++;
            $("#file_selected").remove();
            $(this).parent().prepend('<div id="file_selected" class="selected" />');

            setTimeout(function () {
                check_double = 0;
            }, 500);

            // 双击事件
            if(check_double > 1) {
                var obj = $(this).parent().parent();
                // add folder click operation
                if(obj.attr("type") == "folder") {
                    var child_obj = obj.children("ul");
                    if(child_obj.is(":hidden")){
                        obj.children("div").children(".tag").removeClass("fa-chevron-right");
                        obj.children("div").children(".tag").addClass("fa-chevron-down");
                        child_obj.show();

                        if(child_obj.attr("type") == "loading") {
                            getFileLists(obj)
                        }
                    }else {
                        obj.children("div").children(".tag").removeClass("fa-chevron-down");
                        obj.children("div").children(".tag").addClass("fa-chevron-right");
                        child_obj.hide();
                    }
                }

                // add file click operation
                if(obj.attr("type") == "file") {
                    addNewTab(obj.attr("file_id"));
                    $('#workspace').animate({width:'toggle'},350);
                }
                e.stopPropagation();
            }
        });
        $(this).contextmenu(function (e) {
            $("#file_selected").remove();
            $(this).parent().prepend('<div id="file_selected" class="selected" />');
            openFolderMenu();
        });
    });
}

function addNewTab(file_id) {
    var obj = $("li[file_id='"+file_id+"']" );
    if(obj.attr("type") != "file") {
        return;
    }

    var file_path = obj.attr("file");
    var content_id = file_id;
    var text_id = "text_" + content_id;
    // 移除所有active
    $(".file-nav li").removeClass("active");

    if($("#" + content_id).length == 0) {
        //添加新的文件tab,并设置为活跃
        var tab = '<li class="active"><a href="###" data-target="#'+content_id+'" role="tab">'+obj.attr("name")+'</a><a class="close">X</a></li>';
        var text_content = '<div class="tab-pane" id="'+content_id+'" data-file="'+file_path+'" data-id="'+text_id+'"><div class="file_loading"><ul type="loading"><li><div class="loading"><span></span><span></span><span></span><span></span><span></span></div></li></ul></div><textarea id="'+text_id+'" ></textarea></div>';
        $(".file-nav").prepend(tab);
        $("#file_content_lists").prepend(text_content);
        bindContentToglleClick();

        // record open file into backend
        var data = "action=open&file="+file_path;
        $.ajax({
            type: 'post',
            url: "index.php/api/openingFiles",
            data: data,
            success: function(data) {

            }
        });

    }else {
        $("a[data-target='#"+content_id+"']").parent("li").addClass("active");
        $(".file-nav").scrollLeft($("a[data-target='#"+content_id+"']").offset().left);
    }
    showActiveContent();
}

function getFileExt(file) {
    var ext_arr = file.split(".");
    return ext = ext_arr[ext_arr.length-1].replace("/", "");
}

function getFileModeNameByExt(ext) {
    var arr = [];
    arr["php"] = "php";
    arr['java'] = "text/x-java";
    arr['py'] = "python";
    arr['c'] = "text/x-csrc";
    arr['h'] = "text/x-csrc";
    arr['sh'] = "shell";
    arr['cpp'] = "text/x-c++src";
    arr['cc'] = "text/x-c++src";
    arr['cxx'] = "text/x-c++src";
    arr['m'] = "text/x-objectivec";
    arr['mm'] = "text/x-objectivec";
    arr['sql'] = "text/x-sql";

    return arr[ext];
}

function getFileModeByExt(ext) {
    var arr = [];
    arr["php"] = ['/php/php.js'];
    arr["py"] = ['/python/python.js'];
    arr['sql'] = ["/sql/sql.js"];

    return arr[ext];
}

// these are extended jquery function
// set button style as waiting status.
$.fn.btn_wait = function(f){
    $(this).attr("disabled", "disabled");
    $(this).attr("data-v", $(this).html());
    $(this).attr("data-color", $(this).css("background"));
    $(this).html("<div class='loading'><span></span><span></span><span></span></div>");
    $(this).css("background", "#ccc");
}

// set button style back to normal when button as waiting status
$.fn.btn_wait_recover = function (f) {
    $(this).html($(this).attr("data-v"));
    $(this).css("background", $(this).attr("data-color"));
    $(this).attr("disabled", false);
}