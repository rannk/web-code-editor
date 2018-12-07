/**
 * Created by Rannk on 2018/9/21.
 */

$.fn.modal = function (f, m) {
    if(f == "show") {
        $("#modal_cover").show();
        $(this).show();
        $(this).attr("active", "true");
        event_modal_show = true;
    }

    if(f == "hide") {
        $("#modal_cover").hide();
        $(this).hide();
        $(this).attr("active", "");
        event_modal_show = false;
    }

    if(f == "blink") {
        modalBlink($(this), 7);
    }

    if(f == "loading") {
        if(!$(this).attr("id"))
            return;

        var close_str = "Close";

        if(m) {
            close_str = m;
        }

        var ele = $(this).parent();

        if($(ele).children("#content_loading").length > 0) {
            $(ele).children("#content_loading").show();
            $(this).hide();
        }else {
            $(this).hide();
            $(ele).append('<div id="content_loading"><div class="file_loading"><ul type="loading"><li><div class="loading"><span></span><span></span><span></span><span></span><span></span></div></li></ul></div><button  class="btn loading_close">'+close_str+'</button></div>');
        }

        $(ele).find("#content_loading .loading_close").unbind("click", null);
        $(ele).find("#content_loading .loading_close").click(function () {
            $(ele).parents(".modal").modal("hide");
        });
    }

    if(f == "loading_end") {
        if(!$(this).attr("id"))
            return;


        var ele = $(this).parent();
        $(ele).children("#content_loading").hide();
        $(this).show();
    }
}


function modalBlink(obj, time){
    if(time < 1)
        return;

    if(obj.attr("bg") == "1") {
        obj.attr("bg", "2");
        obj.css("background", "#ffffff");
    }else {
        obj.attr("bg", "1");
        obj.css("background", "#2e3436");
    }

    time--;

    if(time == 0) {
        obj.css("background", "#2e3436");
    }

    setTimeout(function(){
        modalBlink(obj, time)
    }, 50);
}

$(document).ready(function(){
    $(".modal .btn.close").click(function () {
        $(this).parents("div.modal").modal("hide");
    });
});
