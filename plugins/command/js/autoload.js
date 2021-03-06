$("#modal_command_new #save").click(function () {
    var title = $("#modal_command_new #command_title").val();
    var script = $("#modal_command_new #script").val();
    var cmd_id = $("#modal_command_new #cmd_id").val();

    var data = "title=" + title + "&script=" + script + "&cmd_id=" + cmd_id;

    if(title == "") {
        alert($("#command_lang_alert_title").html());
        return;
    }

    if(script == "") {
        alert($("#command_lang_alert_script").html());
        return;
    }

    $(this).btn_wait();

    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/command/api?action=new",
        data: data,
        dataType: "json",
        success: function (data) {
            $("#modal_command_new #save").btn_wait_recover();
            $("#modal_command_new").modal("hide");
            if(data.status == "0") {
                console.log(data.msg);
            }
            setCommandContMenu();
        }
    });
});

$("#modal_command_lists #run").click(function () {
    var cmd_id = $("#modal_command_lists li.active").attr("idx");
    var cmd_name = $("#modal_command_lists li.active").html();

    if(!cmd_id) {
        alert("Please select your command");
        return;
    }

    runCommand(cmd_id, cmd_name);
});

$("#modal_command_display #cmd_min").click(function () {
    var cmd_id = $("#cmd_display").attr("run_id");
    var cmd_name = $("#modal_command_display .header").html();

    if(!cmd_id) {
        return;
    }

    var obj = $("#tab_bottom #icons_show #cmd_min_" + cmd_id);
    $("#cmd_display #cmd_" + cmd_id).hide();

    if(!obj.length) {
        var ele_min = '<span class="fa fa-window-maximize" id="cmd_min_'+ cmd_id +'" cmd_id="'+cmd_id+'" cmd_name="'+cmd_name+'"></span>';
        $("#tab_bottom #icons_show").append(ele_min);

        $("#cmd_min_" + cmd_id). click(function () {
            $("#cmd_display").attr("run_id", $("#cmd_min_" + cmd_id).attr("cmd_id"));
            $("#cmd_display .display").hide();
            var show_cmd_id = "#cmd_display #cmd_" + $("#cmd_min_" + cmd_id).attr("cmd_id");
            if($(show_cmd_id).length > 0) {
                $("#cmd_display #cmd_" + $("#cmd_min_" + cmd_id).attr("cmd_id")).show();
                //$("#cmd_display").modal("loading_end");
            }else {
                //$("#cmd_display").modal("loading");
            }

            $("#modal_command_display").modal("show");
            $("#modal_command_display .header").html($("#cmd_min_" + cmd_id).attr("cmd_name"));
            $("#cmd_min_" + cmd_id).remove();
        });
    }
    $("#modal_command_display").modal("hide");
});

$("#modal_command_lists #edit").click(function () {
    var cmd_id = $("#modal_command_lists li.active").attr("idx");

    if(!cmd_id) {
        alert("Please select your command");
        return;
    }

    $("#modal_command_lists").modal("hide");
    $("#modal_command_new").modal("show");
    $("#cmd_content").modal("loading");

    var data = "cmd_id=" + cmd_id;
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/command/api?action=get_one",
        data: data,
        dataType: "json",
        success: function (data) {
            if(data.status == 1) {
                $("#modal_command_new #command_title").val(data.content.title);
                $("#modal_command_new #script").val(data.content.script);
                $("#modal_command_new #cmd_id").val(cmd_id);
                $("#cmd_content").modal("loading_end");
                setCommandContMenu();
            }
        }
    });

});

$("#modal_command_display .all_close").click(function () {
    var cmd_id = $("#cmd_display").attr("run_id");
    $("#modal_command_display").modal("hide");
    if(cmd_id) {
        $("#cmd_" + cmd_id).remove();
        $("#cmd_min_" + cmd_id).remove();
    }
});

function runCommand(cmd_id, cmd_name) {
    var index = $("#cmd_display").attr("index"); //用来区分相同命令同时运行

    // 获取当前打开的文件名，传递参数用
    var content_id = $.editor.content_id;
    var open_file_name = $("a[data-target='"+content_id+"']").html();

    $("#cmd_display_iframe").attr("src", "");
    $("#modal_command_lists").modal("hide");
    $("#modal_command_display").modal("show");
    $("#modal_command_display .header").html(cmd_name);
    //$("#cmd_display").modal("loading");

    var data = "cmd_id=" + cmd_id + "&filename=" + open_file_name;

    cmd_id = cmd_id + "_" + index;
    $("#cmd_display").attr("run_id", cmd_id);
    $("#cmd_display").attr("index", parseInt(index)+1);
    $("#cmd_display").prepend('<iframe  id="cmd_'+cmd_id+'" class="display" src="index.php/api/plugins/command/api?action=real_time&'+data+'"></iframe>');
}

function setCommandContMenu() {
    $("#command_cont_menu").addClass("disabled");
    var content = '<label class="icon"><span class="ide-icon ide-command"></span></label><div class="text">Command</div>';
    $("#command_cont_menu").html(content);
    $.ajax({
        type: 'post',
        url: "index.php/api/plugins/command/api?action=lists",
        dataType: "json",
        success: function (data) {
            var ele = "";
            $.each(data, function(idx, item){
                if(item.title != "") {
                    ele += "<li idx='"+idx+"'><div class='text'>" + item.title + "</div></li>";
                }
            })

            if(ele.length > 0) {
                content += '<label class="sub_icon"><span class="fa fa-caret-right"></span></label><div class="sub"><ul class="menu_wrapper" style="display: none">' + ele + '</ul></div>';
                $("#command_cont_menu").html(content);
                $("#command_cont_menu").removeClass("disabled");
                $("#command_cont_menu").attr("has_sub", "true");
                $("#command_cont_menu li").each(function () {
                    var obj = $(this);
                    obj.mouseover(function () {
                        obj.addClass("active");
                    });

                    obj.mouseout(function () {
                        obj.removeClass("active");
                    });

                    obj.click(function () {
                        var cmd_id = $(this).attr("idx");
                        var cmd_name = $(this).find("div").text();
                        runCommand(cmd_id, cmd_name);
                    })
                })
            }
        }
    });
}

setCommandContMenu();