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

    var message = $("#modal_git_commit #message").val();

    if(!message) {
        alert("please fill the commit message");
        return;
    }

    data = "files=" + files + "&message=" + message;
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

$(".git_modal .close").click(function () {
    $("#modal_git_commit").modal("hide");
});