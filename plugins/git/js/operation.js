if($.editor.action == "git.commit") {
    showGitCommitModal();
}



function showGitCommitModal() {
    $("#modal_git_commit #commit_btn").attr("disabled", false);
    $("#modal_git_commit #message").val("");
    $("#modal_git_commit").modal("show");
    $("#commit_files").html('<div class="file_loading"><ul type="loading"><li><div class="loading"><span></span><span></span><span></span><span></span><span></span></div></li></ul></div>');
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/git/git?action=add",
        success: function (data) {
            if(data.code == "1") {
                $("#modal_git_commit .file_loading").remove();
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
                $("#modal_git_commit").modal("hide");
                alert(data.msg);
            }
            console.log(data);
        },
        dataType: "json"
    });
}
