$("#modal_project_new #save").click(function () {
    var data = $("#project_content").getFormData();

    $(this).btn_wait();

    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/project/api?action=add",
        data: data,
        dataType: "json",
        success: function (data) {
            if(data.status == "0") {
                console.log(data.msg);
            }else {
                location.href="/";
            }
        }
    });
})

$("#modal_project_open #open").click(function () {
    var title = $("#modal_project_open li.active").attr("title");
    var data = "title=" + title;
    $(this).btn_wait();
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/project/api?action=open",
        data: data,
        dataType: "json",
        success: function (data) {
            location.href="/";
        },
        error: function (e) {
            $("#modal_project_open #open").btn_wait_recover();
            console.log(e);
        }
    });
})

$("#modal_project_open #remove").click(function () {
    var title = $("#modal_project_open li.active").attr("title");
    var data = "title=" + title;
    $(this).btn_wait();
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/project/api?action=remove",
        data: data,
        dataType: "json",
        success: function (data) {
            if($("#modal_project_open li[openning='1']").attr("title") == title) {
                location.href="/";
            }else {
                $("#modal_project_open #remove").btn_wait_recover();
                $("#modal_project_open").modal("hide");
            }

        },
        error: function (e) {
            $("#modal_project_open #remove").btn_wait_recover();
            $("#modal_project_open").modal("hide");
            console.log(e);
        }
    });
})

$("#modal_project_new #connect_type").on("change", function () {
    if($(this).val() == "sftp") {
        $("#modal_project_new #project_sftp_info").show();
        $("#modal_project_new #server_ip").val("");
    }

    if($(this).val() == "local") {
        $("#modal_project_new #project_sftp_info").hide();
        $("#modal_project_new #server_ip").val("local");
        $("#modal_project_new #connect_user").val(" ");
        $("#modal_project_new #user_password").val(" ");
    }
})
