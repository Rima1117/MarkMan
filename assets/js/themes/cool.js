// テーマ用JavaScript
const blur_show_height = 128;

$(window).scroll(function () {
    if ($(this).scrollTop() > blur_show_height) {
        $("header.nav").addClass("active")
    } else {
        $("header.nav").removeClass("active")
    }
});