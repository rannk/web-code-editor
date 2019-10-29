$("#commit_btn").click(function () {
    var files = "", data;
    $("#modal_git_commit input[type='checkbox']").each(function () {
        if($(this).is(":checked")) {
            var file = $(this).parents("tr").find("td:nth-child(2)").html();
            var status = $(this).parents("tr").find("td:nth-child(3)").html();
            // 对删除的文件加标示DeL::
            if(status == "deleted") {
                file = "DeL::" + file;
            }
            files += file + "|;|";
        }
    });

    if(!files) {
        alert("please select the file");
        return;
    }

    var message = $.trim($("#modal_git_commit #message").val());
    var name = $.trim($("#modal_git_commit #author").val());
    var email = $.trim($("#modal_git_commit #email").val());

    if(!message) {
        alert("please fill the commit message");
        return;
    }

    if(!name) {
        alert("please fill the author name");
        return;
    }

    if(!email) {
        alert("please fill the email name");
        return;
    }

    data = "files=" + files + "&message=" + message + "&name=" + name + "&email=" + email;
    $(this).btn_wait();
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/git/git?action=commit",
        data: data,
        success: function (data) {
            $("#modal_git_commit #commit_btn").btn_wait_recover();
            $("#modal_git_commit #commit_btn").attr("disabled", "disabled");
            $("#commit_files").html(data.replace("\n", "<br>"));
        }
    });
});

$("#git_create_checkbox").change(function () {
    if($(this).is(":checked")) {
        $("#git_new_branch_name").attr("disabled", false);
    }else {
        $("#git_new_branch_name").attr("disabled", "disabled");
    }
});
$("#switch_btn").click(function () {
    var branch = $("#branch_lists").val();
    var new_branch = "";
    if($("#git_create_checkbox").is(":checked")) {
        new_branch = $("#git_new_branch_name").val();
        if(new_branch == "") {
            alert("please fill the new branch name");
            return;
        }
    }

    var data = "branch=" + branch + "&new_branch=" + new_branch;

    $("#modal_git_switch #content_loading").show();
    $("#modal_git_switch #switch_content").hide();
    $("#modal_git_switch #switch_btn").hide();

    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/git/git?action=checkout",
        data: data,
        success: function (data) {
            alert(data);
            $("#modal_git_switch").hide();
            window.location.reload();
        }
    });
});

$("#modal_git_msg #msg_btn").click(function () {
    $("#modal_git_msg #content_loading").show();
    $("#modal_git_msg .body").hide();
    $("#modal_git_msg #msg_btn").addClass("disabled");

    var type = $("#modal_git_msg #msg_btn").html().toLowerCase();

    var data = "";
    if(type == "push") {
        data = "remote=" + $("#modal_git_msg #git_remote").val();
        if($("#modal_git_msg #force_push").is(":checked")) {
            data += "&f=1";
        }
    }

    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/git/git?action=" + type + "_op",
        data: data,
        success: function (data) {
            var reg = new RegExp("\n","g")
            $("#modal_git_msg .body").html(data.replace(reg, "<br>"));
            $("#modal_git_msg #content_loading").hide();
            $("#modal_git_msg .body").show();
        }
    });
});

$("#revert_btn").click(function () {
    var files = "", data;
    $("#revert_files input[type='checkbox']").each(function () {
        if($(this).is(":checked")) {
            var file = $(this).parents("tr").find("td:nth-child(2)").html();
            files += file + "|;|";
        }
    });

    if(!files) {
        alert("please select the file");
        return;
    }

    data = "files=" + files;
    $(this).btn_wait();
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/git/git?action=revert",
        data: data,
        success: function (result) {
            if(result.status == "1") {
                location.href = "/";
            }else {
                $("#modal_git_revert #revert_btn").btn_wait_recover();
                alert(result.msg);
            }
        },
        error: function (e) {
            console.log(e);
        },
        dataType: "json"
    });
});