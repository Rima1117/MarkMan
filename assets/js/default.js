function pageInit(element) {
    const links = element.querySelectorAll('a');

    links.forEach(link => {
        const href = link.getAttribute('href');

        if (href && href.startsWith('http') && !href.startsWith(window.location.origin)) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        }
    });

    $(".post pre code").each(function () {
        code = String($(this).attr("class")).replace(/^language-/, '')
        if ($(this).attr("class") == undefined) {
            code = "code";
        }
        $(this).parent().attr("name",code)
    })
}

const contentElements = document.getElementsByClassName('html');
if (contentElements.length > 0) {
    pageInit(contentElements[0]);
}