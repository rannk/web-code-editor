(function ($) {
    $.fn.dragDivResize = function () {
        var deltaX, deltaY, _startX, _startY;
        var resizeW, resizeH, move_div;
        var size = 5;
        var minSize = 10;
        var scroll = getScrollOffsets();
        var _this = this;
        var target = this[0];
        var act = false;
        var target_header = $(target).find(".header")[0];
        $(target).on("mouseover mousemove", overHandler);
        $(target_header).on("mousedown", function (e) {
            move_div = true;
            downHandler(e);
        });

        function overHandler(event) {
            if(act) {
                return;
            }
            var startX = event.clientX + scroll.x;
            var startY = event.clientY + scroll.y;
            var w = $(target).width();
            var h = $(target).height();
            var offsetLeft = $(target).offset().left;
            var offsetTop = $(target).offset().top;

            _startX = parseInt(startX);
            _startY = parseInt(startY);

            if ((0 < offsetLeft + w - _startX && offsetLeft + w - _startX < size) || (0 < offsetTop + h - _startY && offsetTop + h - _startY < size)) {
                if ((0 > offsetLeft + w - _startX || offsetLeft + w - _startX > size) && 0 < offsetTop + h - _startY && offsetTop + h - _startY < size) {
                    resizeW = false;
                    resizeH = true;
                    document.body.style.cursor = "s-resize";
                }
                if (0 < offsetLeft + w - _startX && offsetLeft + w - _startX < size && (0 > offsetTop + h - _startY || offsetTop + h - _startY > size)) {
                    resizeW = true;
                    resizeH = false;
                    document.body.style.cursor = "w-resize";

                }
                if (0 < offsetLeft + w - _startX && offsetLeft + w - _startX < size && 0 < offsetTop + h - _startY && offsetTop + h - _startY < size) {
                    resizeW = true;
                    resizeH = true;
                    document.body.style.cursor = "se-resize";
                }
                $(target).on('mousedown', downHandler);
            } else {
                $(target).off('mousedown', downHandler);
                document.body.style.cursor = "default";
            }
        }

        function downHandler(event) {
            var startX = event.clientX + scroll.x;
            var startY = event.clientY + scroll.y;
            _startX = parseInt(startX);
            _startY = parseInt(startY);
            act = true;
            if (document.addEventListener) {
                document.addEventListener("mousemove", moveHandler, true);
                document.addEventListener("mouseup", upHandler, true);
            } else if (document.attachEvent) {
                target.setCapture();
                target.attachEvent("onlosecapeture", upHandler);
                target.attachEvent("onmouseup", upHandler);
                target.attachEvent("onmousemove", moveHandler);
            }
            if (event.stopPropagation) {
                event.stopPropagation();
            } else {
                event.cancelBubble = true;
            }
            if (event.preventDefault) {
               event.preventDefault();
            } else {
               event.returnValue = false;
            }
        }
        function moveHandler(e) {

            if (!e) e = window.event;
            var w, h;
            var startX = parseInt(e.clientX + scroll.x);
            var startY = parseInt(e.clientY + scroll.y);

            if (target == document.body) {
                return;
            }

            if (resizeW) {
                deltaX = startX - _startX;
                w = $(target).width() + deltaX < minSize ? minSize : $(target).width() + deltaX;
                $(target).width(w);
                if(($(target).offset().left > (window.innerWidth-w)/2 && deltaX > 0) || ($(target).offset().left < (window.innerWidth-w)/2 && deltaX < 0) ) {
                    $(target).offset({"left":(window.innerWidth-w)/2});
                }

                _startX = startX;
            }
            if (resizeH) {
                deltaY = startY - _startY;
                h = $(target).height() + deltaY < minSize ? minSize : $(target).height() + deltaY;
                $(target).height(h);
                $(target).children(".content").height(h-45-8);
                if(($(target).offset().top > (window.innerHeight-h)/2 && deltaY > 0) || ($(target).offset().top < (window.innerHeight-h)/2 && deltaY < 0) ) {
                    $(target).offset({"top":(window.innerHeight-h)/2});
                }

                _startY = startY;
            }

            if(move_div && !resizeW && !resizeH) {
                deltaX = startX - _startX;
                deltaY = startY - _startY;
                _startX = startX;
                _startY = startY;
                $(target).offset({"left":( $(target).offset().left + deltaX)});
                $(target).offset({"top":( $(target).offset().top + deltaY)});
            }

            if (e.stopPropagation) {
                e.stopPropagation();
            } else {
                e.cancelBubble = true;
            }

            $(target).trigger("div_change", target);
        }
        function outHandler() {
            for (var i = 0; i < _this.length; i++) {
                target.style.outline = "none";
            }
            document.body.style.cursor = "default";
        }
        function upHandler(e) {
            act = false;
            move_div = false;
            if (!e) {
                e = window.event;
            }
            resizeW = false;
            resizeH = false;
            $(target).on("mouseout", outHandler);
            if (document.removeEventListener) {
                document.removeEventListener("mousemove", moveHandler, true);
                document.removeEventListener("mouseup", upHandler, true);
            } else if (document.detachEvent) {
                target.detachEvent("onlosecapeture", upHandler);
                target.detachEvent("onmouseup", upHandler);
                target.detachEvent("onmousemove", moveHandler);
                target.releaseCapture();
            }
            if (e.stopPropagation) {
                e.stopPropagation();
            } else {
                e.cancelBubble = true;
            }
        }

        function getScrollOffsets(w) {
            w = w || window;
            if (w.pageXOffset != null) {
                return { x: w.pageXOffset, y: w.pageYOffset };
            }
            var d = w.document;
            if (document.compatMode == "CSS1Compat") {
                return { x: d.documentElement.scrollLeft, y: d.documentElement.scrollTop };
            }
            return { x: d.body.scrollLeft, y: d.body.scrollTop };
        }

    }
}(jQuery));

$(document).ready(function(){
   $(".modal.drag_div").each(function () {
       $(this).dragDivResize();
   })
});
