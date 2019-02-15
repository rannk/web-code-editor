(function(mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror"], mod);
    else // Plain browser env
        mod(CodeMirror);
})
(function(CodeMirror) {
    var count;
    var operating = false;
    var class_arr = [];
    var val_arr = [];
    var op;
    CodeMirror.defineExtension("HintCol", function (cm) {
        count = 2;
        if(operating == true)
            return;

        operating = true;
        col(cm);
    });

    function col(cm) {
        if(count == 0) {
            operating = false;
            collectFun(cm.getValue());
            collectVar(cm.getValue());
            if(typeof $.hint_class_arr == "object") {
                for(var key in class_arr) {
                    $.hint_class_arr[key] = class_arr[key];
                }
            }else {
                $.hint_class_arr = class_arr;
            }
            cm.val_arr = val_arr;
            return;
        }
        setTimeout(function(){
                count--;
                col(cm);
            }, 500);
    }


    function collectFun(content) {
        var reg = /class (\w*) {0,}[\w ]{0,}\{/g;
        var class_range = /[\{\}]/g;
        reg.lastIndex = 0;
        var class_tag;
        do{
            class_tag = reg.exec(content);

            if(class_tag) {
                class_range.lastIndex = class_tag['index'];
                var s = 0, pos_s=0, pos_end=0, r;
                do{
                    r = class_range.exec(content);

                    if(r[0] == "{" && s > 0) {
                        s++;
                    }

                    if(r[0] == "{" && s == 0) {
                        pos_s = r['index'] + 1;
                        s++;
                    }

                    if(r[0] == "}" && s <= 1) {
                        pos_end = r['index'];
                        break;
                    }

                    if(r[0] == "}" && s > 1) {
                        s--;
                    }

                    class_range.lastIndex = r['index'] + 1;
                }while(r);

                if(pos_end > pos_s) {
                    var c_fun_content = content.substr(pos_s, pos_end - pos_s);
                    if(c_fun_content) {
                        var class_name = class_tag[1];
                        class_arr[class_name] = [];
                    }
                    var f_reg = /function {0,}(\w*) {0,}\(/g;
                    var f_lists = c_fun_content.match(f_reg);
                    var get_fun_name_reg = /function {0,}(\w*) {0,}\(/;
                    for(var i=0;i<f_lists.length;i++) {
                        if(get_fun_name_reg.exec(f_lists[i])[1]) {
                            class_arr[class_name][i] = get_fun_name_reg.exec(f_lists[i])[1];
                        }
                    }
                }

                reg.lastIndex = class_tag['index'] + 1;
            }
        }while(class_tag)
    }

    function collectVar(content) {
        var reg = /\$(\w*)[ \W]{1,}=([\w\W]*);/g;
        var obj_reg = / {0,}new {1,}(\w*) {0,}\(/;
        var find_obj, arr_l;
        val_arr = [];

        reg.lastIndex = 0;
        var lists;
        do{
            lists = reg.exec(content);
            if(lists) {
                arr_l = val_arr.length;
                find_obj = obj_reg.exec(lists[2]);
                val_arr[arr_l] = getLinePosByIndex(content, lists['index']);
                val_arr[arr_l]['n'] = lists[1];

                if(find_obj){
                    val_arr[arr_l]['o'] = find_obj[1];
                }else{
                    val_arr[arr_l]['o'] = "";
                }

                reg.lastIndex = lists['index'] + 1;
            }
        }while(lists)
    }

    function getLinePosByIndex(content, index) {
        var lines = content.split("\n");
        var pos = 0;
        var r = [];
        for(var i=0;i<lines.length;i++) {
            pos += lines[i].length;
            if(index < pos) {
                r['line'] = i;
                r['cn'] = index - (pos - lines[i].length);
                break;
            }
            // 算上回车的长度
            pos += 1;
        }

        return r;
    }
})
