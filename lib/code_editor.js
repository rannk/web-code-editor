/**
 * main program of this editor
 * @author Rannk
 */

$(document).ready(function(){
    $('#btn_workspace').click(function(){
        $('#workspace').animate({width:'toggle'},350);
    });

    // init editor section
    $("#editor_section").css("height", window.innerHeight - 40);
    $("#file_content_lists").css("height", window.innerHeight - 90);
    addClick("#workspace label");

    showActiveContent();
    bindContentToglleClick();
    getFileLists("#workspace>ul>li");
});



function getFileContent(file,element) {
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

            var content_id = element.replace("text_", "");

            myCodeMirror.on("cursorActivity", function(e){
                $('#workspace').animate({width:'hide'},350);
            })

            myCodeMirror.on("change", function (cm, obj) {
                // 添加需要保存的标签
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
            })

            myCodeMirror.setOption("extraKeys", {
                'Ctrl-S': function(cm) {
                    if($("a[data-target='#" + content_id + "']").parent("li").children("span").hasClass("saving") || $("a[data-target='#" + content_id + "']").parent("li").children("span").length == 0) {
                        return;
                    }

                    $("a[data-target='#" + content_id + "']").parent("li").children("span").addClass("saving");
                    var data = {file: $("#"+content_id).attr("data-file"),content: cm.getValue()};
                    $.ajax({
                        type: "post",
                        url: "index.php/api/save_file",
                        data: data,
                        success: function(data){
                            // 如果没有其它正在保存的操作，则去除保存中的标签
                            if($("a[data-target='#" + content_id + "']").parent("li").children("span").hasClass("saving")) {
                                $("a[data-target='#" + content_id + "']").parent("li").children("span").remove();
                            }
                        }
                    });
                }

            });
        }
    });
}


function showActiveContent() {
    $(".tab-content .tab-pane").hide();
    var content_id = $(".file-nav .active a[role='tab']").attr("data-target");
    if(content_id) {
        $(content_id).show();
        if($( content_id + " .CodeMirror").length == 0) {
            getFileContent($(content_id).attr("data-file"), $(content_id).attr("data-id"));
        }
        var f = $(content_id).attr("data-file");
        $("#filename_show").html("workspace: " + f.substr(0, f.length-1));
    }
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
            });
        }
    });
}

function getFileLists(name) {
    var data = "dir=" + getFolderPath(name);
    $.ajax({
        type: 'post',
        url: "index.php/api/get_file_lists",
        data: data,
        success: function(data) {
            var content = "<ul>";
            $.each(data, function(idx, item){
                var left = "";
                content += "<li type='" + item.type + "' name='"+item.name+"'><div class='filename'>";
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

        },
        dataType: "json"
    });
}

function addClick(element) {
    $(element).each(function(index){
        // select the file or folder
        $(this).click(function(e){
            $("#file_selected").remove();
            $(this).parent().prepend('<div id="file_selected" class="selected" />');
        });
        // open the file or folder
        $(this).dblclick(function(e){
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
                var file_path = getFolderPath(obj);
                var content_id = hex_md5(file_path);
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
                    var data = "action=open&file="+file_path+"&showname="+obj.attr("name");
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
                $('#workspace').animate({width:'toggle'},350);
            }
            e.stopPropagation();
        });
    });
}

function getFolderPath(currentPath) {
    var obj = $(currentPath);

    if(obj.attr("root") == "yes") {
        return "/";
    }else {
        return getFolderPath(obj.parents("li"))  + obj.attr("name") + "/";
    }
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
