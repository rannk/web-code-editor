$.editor.onContentActive(function () {
    checkIconsStatus();
});

$.editor.onContentChange(function () {
    checkIconsStatus();
});


function checkIconsStatus() {
    $("#file_redo_icon").addClass("cantdo");
    $("#file_undo_icon").addClass("cantdo");
    $("#file_save_icon").addClass("cantdo");

    if(typeof($.editor.getCM()) == "object" ){
        if($.editor.getCM().historySize().redo > 0) {
            $("#file_redo_icon").removeClass("cantdo");
        }else{
            $("#file_redo_icon").addClass("cantdo");
        }

        if($.editor.getCM().historySize().undo > 0) {
            $("#file_undo_icon").removeClass("cantdo");
        }else{
            $("#file_undo_icon").addClass("cantdo");
        }
    }

    if($("a[data-target='" + $.editor.content_id + "']").parent("li").children("span").length > 0) {
        $("#file_save_icon").removeClass("cantdo");
    }else{
        $("#file_save_icon").addClass("cantdo");
    }
}

$("#file_redo_icon").click(function () {
    if(typeof($.editor.getCM()) == "object" ){
        $.editor.getCM().redo();
    }
})

$("#file_undo_icon").click(function () {
    if(typeof($.editor.getCM()) == "object" ){
        $.editor.getCM().undo();
    }
})

$("#file_save_icon").click(function () {
    if(typeof($.editor.getCM()) == "object" ){
        $.editor.saveContent();
        $("#file_save_icon").addClass("cantdo");
    }
})

$(".file_modal .close").click(function () {
    $("#modal_file_rename").modal("hide");
    $("#modal_file_delete").modal("hide");
    $("#modal_file_new").modal("hide");
    $("#modal_folder_new").modal("hide");
});

// 更名modal提交后的处理
$("#modal_file_rename #file_rename").click(function () {

    var file_id = $("#modal_file_rename").attr("rename_id");
    var newname = $.trim($("#modal_file_rename input").val());

    if(!file_id) {
        console.log("file id is not found");
        return;
    }

    if(!newname) {
        alert("The file name can't empty");
        return;
    }

    // check file full path, if the file path not exist, return directly
    var file;
    if($("#" + file_id).attr("data-file")) {
        file = $("#" + file_id).attr("data-file");
    }else if ($("#workspace li[file_id='"+file_id+"']").attr("file")) {
        file = $("#workspace li[file_id='"+file_id+"']").attr("file");
    }

    if(!file) {
        console.log("file path is not found");
        return;
    }

    var data = "file=" + file + "&new_filename=" + newname;

    $(this).btn_wait();

    $.ajax({
        type: 'post',
        url: "index.php/api/rename",
        data: data,
        success: function (data) {
            if(data == "1") {
                //update file name that in workspace section
                $("#workspace li[file_id='"+file_id+"']>.filename label").html(newname);
                $("#workspace li[file_id='"+file_id+"']").attr("name", newname);

                //update file name that in tab
                $("a[data-target='#"+file_id+"'][role='tab']").html(newname);

                //update filepath
                var new_path = "/";
                var path_arr = file.split("/");

                for(var i=path_arr.length-1;i>0;i--) {
                    if(!path_arr[i])
                        continue;

                    path_arr[i] = newname;
                    break;
                }

                for(i=1;i<path_arr.length;i++) {
                    if(!path_arr[i])
                        continue;

                    new_path += path_arr[i] + "/";
                }
                new_path = new_path.substr(0, new_path.length-1);

                $("#" + file_id).attr("data-file", new_path);
                $("#workspace li[file_id='"+file_id+"']").attr("file", new_path);
                $("#filename_show").html("workspace: " + new_path.substr(0, new_path.length-1));


            } else {
                console.log(data);
            }
            $("#modal_file_rename #file_rename").btn_wait_recover();
            $("#modal_file_rename").modal("hide");
        }
    });
});

//删除modal提交后的处理
$("#modal_file_delete #file_delete").click(function () {

    var file_id = $("#modal_file_delete").attr("file_id");

    if(!file_id) {
        $("#modal_file_delete").modal("hide");
        alert("can't delete the whole workspace");
        console.log("file id is not found");
        return;
    }

    // check file full path, if the file path not exist, return directly
    var file;
    if($("#" + file_id).attr("data-file")) {
        file = $("#" + file_id).attr("data-file");
    }else if ($("#workspace li[file_id='"+file_id+"']").attr("file")) {
        file = $("#workspace li[file_id='"+file_id+"']").attr("file");
    }

    if(!file) {
        console.log("file path is not found");
        return;
    }

    var data = "file=" + file;

    $(this).btn_wait();

    $.ajax({
        type: 'post',
        url: "index.php/api/delete",
        data: data,
        success: function (data) {
            if(data == "1") {
                $.editor.removeFileFromFolder(file_id);
            } else {
                console.log(data);
            }
            $("#modal_file_delete #file_delete").btn_wait_recover();
            $("#modal_file_delete").modal("hide");
        }
    });
});

//新增文件modal提交后的处理
$("#modal_file_new #file_new_btn").click(function () {
    newFileAction("file");
});

$("#modal_folder_new #folder_new_btn").click(function () {
    newFileAction("folder");
});

function newFileAction(action) {

    var file_id = $("#modal_"+action+"_new").attr("file_id");
    var newname = $.trim($("#modal_"+action+"_new input").val());

    if(!file_id) {
        console.log("file id is not found");
        return;
    }

    if(!newname) {
        alert("The "+action+" name can't empty");
        return;
    }

    // check file full path, if the file path not exist, return directly
    var file;
    file = $("#modal_"+action+"_new #new_"+action+"_folder_info").html();

    if(!file) {
        console.log(""+action+" path is not found");
        return;
    }

    if(file == "/") {
        file = "/" + newname;
    }else {
        file = file + "/" + newname;
    }

    var data = "file=" + file;

    $("#modal_"+action+"_new #"+action+"_new_btn").btn_wait();

    $.ajax({
        type: 'post',
        url: "index.php/api/new_"+action,
        data: data,
        dataType: "json",
        success: function (data) {
            if(data.state == "1") {
                $.editor.addNewFileToFolder(file_id, data.file_id, function () {
                    $("#modal_"+action+"_new #"+action+"_new_btn").btn_wait_recover();
                    $("#modal_"+action+"_new").modal("hide");
                });
            } else {
                alert(data.msg);
                $("#modal_"+action+"_new #"+action+"_new_btn").btn_wait_recover();
                $("#modal_"+action+"_new").modal("hide");
            }
        }
    });
}
