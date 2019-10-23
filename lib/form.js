(function ($) {
    $.fn.getFormData = function(){
        var target = this[0];
        var results = "";

        $(target).find("input").each(function (i) {
            if(results) {
                results += "&";
            }
            results += $(this).attr("id") + "=" + $(this).val();
        })

        $(target).find("select").each(function (i) {
            if(results) {
                results += "&";
            }
            results += $(this).attr("id") + "=" + $(this).val();
        })

        $(target).find("textarea").each(function (i) {
            if(results) {
                results += "&";
            }
            results += $(this).attr("id") + "=" + $(this).val();
        })

        return results;
    }

    $.fn.initFormData = function(){
        var target = this[0];

        $(target).find("input").each(function (i) {
            $(this).val("");
        })

        $(target).find("select").each(function (i) {
            $(this).val("");
        })

        $(target).find("textarea").each(function (i) {
            $(this).val("");
        })
    }

    $.fn.loadingDiv = function(f){
        if(f == "hide") {
            $(this).find(".file_loading").remove();
        }else {
            $(this).html('<div class="file_loading"><ul type="loading"><li><div class="loading"><span></span><span></span><span></span><span></span><span></span></div></li></ul></div>');
        }
    }
}(jQuery));