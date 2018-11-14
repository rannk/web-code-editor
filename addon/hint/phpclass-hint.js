(function(mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror"], mod);
    else // Plain browser env
        mod(CodeMirror);
})
(function(CodeMirror) {
    "use strict";
    var v1,v2;
    function getHints(cm, options) {
        var cur = cm.getCursor(), token = cm.getTokenAt(cur);
        console.log(token);
        if(/\$\w.*/.test(token.string)) {
            v1 = token;
        }else if(token.string == "-" && v1) {
            v2 = v1;
        }else if(token.string == ">" && v2) {
            console.log("bq");
        }else{
            v1 = v2 = "";
        }
    }

    //CodeMirror.registerHelper("hint", "xml", getHints);

})
