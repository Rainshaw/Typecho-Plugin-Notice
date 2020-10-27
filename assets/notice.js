$(function () {
    var menlis = $(".error.message").parents(".mdui-panel-item");
    for (var idx=0; idx < menlis.length; idx++){
        var div = $(menlis[idx]);
        div.addClass("mdui-panel-item-open");
    }
});