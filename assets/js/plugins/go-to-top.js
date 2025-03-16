const top_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" height="24" viewBox="0 0 24 24" width="24"><path d="m15.25 14.25-3.25-3.5-3.25 3.5" stroke="var(--text)" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/></svg>';
const duration = 300;
const show_height = 128;
const bottom_px = 128;

$(function () {
    if (!$("#page_top_button").legnth) {
        $("body").append('<div id="page_top_button" class="page-top-button">' + top_icon_svg + '</div>');
        $(document).on('click', '#page_top_button', function (event) {
            $('html, body').animate(
                {
                    scrollTop: 0,
                },
                duration
            );
        });

        var page_top_button = $("#page_top_button");

        $(window).scroll(function () {

            if ($(window).scrollTop() + $(window).height() + bottom_px >= $(document).height()) {
                page_top_button.fadeOut(300);
            } else if ($(this).scrollTop() > show_height) {
                page_top_button.fadeIn(300);
            } else {
                page_top_button.fadeOut(300);
            }
        });

        if ($(window).scrollTop() > show_height) {
            page_top_button.fadeIn(300);
        }
    }
})