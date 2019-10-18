if($.editor.action == "git.commit") {
    showGitCommitModal();
}

if($.editor.action == "git.switch") {
    showGitSwitchModal();
}

if($.editor.action == "git.pull") {
    showGitMsgModal("pull");
}

if($.editor.action == "git.push") {
    showGitMsgModal("push");
}

function showGitSwitchModal() {
    // init the modal
    $("#modal_git_switch #content_loading").show();
    $("#modal_git_switch #switch_content").hide();
    $("#modal_git_switch").modal("show");
    $("#modal_git_switch #switch_btn").show();
    $("#git_create_checkbox").attr("checked",false);
    $("#git_new_branch_name").val("");
    $("#git_new_branch_name").attr("disabled", "disabled");
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/git/git?action=branch",
        success: function (data) {
            if(data.code == "1") {
                var option;
                $.each(data.data, function (idx, item) {
                    option += '<option value="'+item.name+'" '+item.selected+' >' + item.name + '</option>';
                })
                $("#modal_git_switch #branch_lists").html(option);
                $("#modal_git_switch #content_loading").hide();
                $("#modal_git_switch #switch_content").show();
            }else{
                $("#modal_git_switch").modal("hide");
                alert(data.msg);
            }
        },
        dataType: "json"
    });
}

function showGitMsgModal(type) {
    var title, btn_msg;
    switch(type) {
        case "pull":
            title = "Git Pull Info";
            btn_msg = "Pull";
            break;
        case "push":
            title = "Git Push Info";
            btn_msg = "Push";
            break;
    }

    $("#modal_git_msg #msg_btn").html(btn_msg);
    $("#modal_git_msg .header").html(title);
    $("#modal_git_msg").show();
    $("#modal_git_msg .body").hide();
    $("#modal_git_msg #msg_btn").attr("disabled", false);
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/git/git?action=" + type,
        success: function (data) {
            var content = "";
            if(data.code == 1) {
                if(type == "pull") {
                    content = "Pulling code to this branch: " + data.msg + "?";
                }

                if(type == "push") {
                    var reg = new RegExp("\n","g")
                    content = "<label>The lastest commit info:</label><br>" + data.msg.replace(reg, "<br>");
                    content += "<br>Remote: <select id='git_remote'>";
                    $.each(data.remote, function (idx, item) {
                        content += "<option value='" + item + "'>" + item + "</option>";
                    })
                    content += "<br><input type='checkbox' id='force_push'>Force Push";
                }
            }else {
                content = data.msg;
            }

            $("#modal_git_msg .body").html(content);
            $("#modal_git_msg #content_loading").hide();
            $("#modal_git_msg .body").show();
        },
        dataType: "json"
    });
}

function showGitCommitModal() {
    $("#modal_git_commit #commit_btn").attr("disabled", false);
    $("#modal_git_commit #message").val("");
    $("#modal_git_commit #git_branch").html("");
    $("#modal_git_commit").modal("show");
    $("#commit_files").html('<div class="file_loading"><ul type="loading"><li><div class="loading"><span></span><span></span><span></span><span></span><span></span></div></li></ul></div>');
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/git/git?action=add",
        success: function (data) {

            $("#modal_git_commit #author").val(data.name);
            $("#modal_git_commit #email").val(data.email);

            if(data.code == "1") {
                $("#commit_files").html("");
                var modified_str = '<tr><td colspan="3" class="tag">Modified Files:</td></tr>';
                var new_str = '<tr><td colspan="3" class="tag">Not Versioned Files:</td></tr>';
                var has_modified_data, has_new_data, has_data;
                $.each(data.data, function(idx, item){
                    has_data = true;
                    if(item.status == "New") {
                        has_new_data = true;
                        new_str += '<tr><td><input type="checkbox"></td><td>'+item.name+'</td><td>New</td></tr>'
                    }else {
                        has_modified_data = true;
                        modified_str += '<tr class="'+item.status+'"><td><input type="checkbox"></td><td>'+item.name+'</td><td>'+item.status+'</td></tr>'
                    }
                });

                if(has_data) {
                    $("#commit_files").append('<thead><td></td><td>Path</td><td>Status</td></thead>');
                }
                if(has_modified_data) {
                    $("#commit_files").append(modified_str);
                }

                if(has_new_data) {
                    $("#commit_files").append(new_str);
                }

            }

            if(data.code == "0") {
                if(!data.msg){
                    $("#modal_git_commit .file_loading").remove();
                    $("#commit_files").html("No files were changed or added since the last commit.");
                    $("#modal_git_commit #commit_btn").attr("disabled", "disabled");
                }else {
                    $("#modal_git_commit").modal("hide");
                    alert(data.msg);
                }
            }
            $("#modal_git_commit #git_branch").html(data.branch);
        },
        dataType: "json"
    });
}
