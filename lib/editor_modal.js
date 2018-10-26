/**
 * Created by Rannk on 2018/9/21.
 */

$.fn.modal = function (f) {
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
