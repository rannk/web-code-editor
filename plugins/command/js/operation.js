if($.editor.action == "command.new") {
    $("#modal_command_new #cmd_id").val(0);
    $("#modal_command_new #command_title").val("");
    $("#modal_command_new #script").val("");
    $("#modal_command_new #cmd_content").show();
    $("#modal_command_new #content_loading").hide();
    showNewModal();
}

if($.editor.action == "command.lists") {
    $("#modal_command_lists").modal("show");

    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/command/api?action=lists",
        dataType: "json",
        success: function (data) {
            var ele = "";
            $.each(data, function(idx, item){
                if(item.title != "") {
                    ele += "<li idx='"+idx+"'>" + item.title + "</li>";
                }
            })

            if(ele.length > 0) {
                $("#modal_command_lists ul").html(ele);
                $("#modal_command_lists li").click(function () {
                    $("#modal_command_lists li").removeClass("active");
                    $(this).addClass("active");
                });
            }
        }
    });
}

function showNewModal() {
    $("#modal_command_new").modal("show");
}
