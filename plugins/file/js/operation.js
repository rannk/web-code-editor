if($.editor.action == "save") {
    $.editor.saveContent();
}

if($.editor.action == "newfile") {
    showNewfileModal($("#workspace .selected").parent().parent().attr("file_id"), "file");
}

if($.editor.action == "newfolder") {
    showNewfileModal($("#workspace .selected").parent().parent().attr("file_id"), "folder");
}

if($.editor.action == "rename") {
    if($("#workspace").css("display") == "block") {
        showRenameModal($("#workspace .selected").parent().parent().attr("file_id"));
    }else if($.editor.content_id != undefined) {
        showRenameModal($.editor.content_id.toString().replace("#", ""));
    }
}

if($.editor.action == "delete") {
    if($("#workspace").css("display") == "block") {
        showDeleteModal($("#workspace .selected").parent().parent().attr("file_id"));
    }else if($.editor.content_id != undefined) {
        showDeleteModal($.editor.content_id.toString().replace("#", ""));
    }
}


function showRenameModal(id) {
    var name = $("#workspace li[file_id='"+id+"']").attr("name");
    if(!name) {
        name = $("a[data-target='#"+id+"'][role='tab']").html();
    }

    $("#modal_file_rename").modal("show");
    // content_id 包含"#"符号，需要去掉
    $("#modal_file_rename").attr("rename_id", id);
    $("#modal_file_rename input").val(name);
}

function showDeleteModal(id) {
    var name = $("#workspace li[file_id='"+id+"']").attr("name");
    if(!name) {
        name = $("a[data-target='#"+id+"'][role='tab']").html();
    }

    $("#modal_file_delete").modal("show");
    // content_id 包含"#"符号，需要去掉
    $("#modal_file_delete").attr("file_id", id);
    $("#modal_file_delete #delete_file_name").html(name);
}

function showNewfileModal(id, action) {

    var type = $("#workspace li[file_id='"+id+"']").attr("type");
    var folder, file_id;
    if(type == "folder") {
        folder = $("#workspace li[file_id='"+id+"']").attr("file");
        file_id = id;
    }else if($("#workspace li[file_id='"+id+"']").attr("file")) {
        folder = $("#workspace li[file_id='"+id+"']").attr("file");
        folder = folder.substr(0, folder.length-1-$("#workspace li[file_id='"+id+"']").attr("name").length);
        file_id = $("#workspace li[file_id='"+id+"']").parent().parent().attr("file_id");
    }

    if(!file_id) {
        file_id = "root";
    }
    if(!folder) {
        folder = "/";
    }

    $("#modal_"+action+"_new #new_"+action+"_folder_info").html(folder);
    $("#modal_"+action+"_new").attr("file_id", file_id);
    $("#modal_"+action+"_new input").val("");
    $("#modal_"+action+"_new").modal("show");
}
