if($.editor.action == "project.new") {
    $("#modal_project_new").modal("show");
}

if($.editor.action == "project.open") {
    $("#modal_project_open").modal("show");
    $("#modal_project_open #project_open_lists").loadingDiv();
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/project/api?action=lists",
        dataType: "json",
        success: function (data) {
            $("#modal_project_open #project_open_lists").loadingDiv("hide");
            var ele = "";
            $.each(data, function(idx, item){
                if(item.title != "") {
                    ele += "<li idx='"+idx+"' title='"+item.project_title+"' openning='"+item.active+"'>" + item.project_title + "<span>"+item.server_ip+":"+item.project_dir+"</span></li>";
                }
            })

            if(ele.length > 0) {
                $("#modal_project_open #project_open_lists").html(ele);
                $("#project_open_lists li").click(function () {
                    $("#project_open_lists li").removeClass("active");
                    $(this).addClass("active");
                });
            }
        },
        error: function (e) {
            $("#modal_project_open #project_open_lists").loadingDiv("hide");
            $("#modal_project_open ul").html(e.responseText);
            console.log(e);
        }
    });
}