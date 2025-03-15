let config;
let post_uri;

async function initialize() {
    config = await loadConfig();
    const parts = config.page_uri.split("/");
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('p')) {
        post_uri = "/pages.php?p="
    } else {
        post_uri = "/" + parts[1] + "/";
    }
    if (post_uri != "/" && window.location.pathname + window.location.search.startsWith(post_uri)) {
        const url = window.location.pathname;
        if (post_uri.includes(".php")) {
            var postId = urlParams.get('p');
        } else {
            const parts = url.split("/");
            var postId = parts[2];
        }
        $("main").hide()
        await loadPostPage(postId, url)
            .then(() => {
                $("main").fadeIn(300);
            })
            .catch((error) => {
                console.error("投稿ページの読み込みに失敗しました:", error);
                $("main").show();
            });
    }
}

initialize();

$(document).on('click', 'a[href^="/"]', function (event) {
    const href = $(this).attr('href');
    event.preventDefault();
    loadContent(href);
    return false;
});

window.addEventListener('popstate', async function (event) {
    const href = window.location.pathname + window.location.search;
    event.preventDefault();
    await loadContent(href);
    return false;
});

function loadConfig(configPath = "/config.md") {
    return $.ajax({
        url: configPath + "?v=" + Date.now(),
        dataType: "text",
    }).then(function (data) {
        const config = {};
        const lines = data.split("\n");
        lines.forEach(function (line) {
            const trimmedLine = line.trim();
            if (trimmedLine.length > 0 && trimmedLine.indexOf(":") !== -1) {
                const parts = trimmedLine.split(":");
                if (parts.length >= 2) {
                    const key = parts[0].trim();
                    const value = parts.slice(1).join(":").trim();
                    config[key] = value;
                }
            }
        });
        return config;
    });
}

async function getPostContent(pageId) {
    const pagesDir = config.pages_dir;
    const bodyFileName = config.page_body;
    const postPath = `/${pagesDir}/${pageId}/${bodyFileName}?v=${Date.now()}`;

    return $.ajax({
        url: postPath,
        dataType: "text",
    }).then(function (markdown) {
        const html = marked.parse(markdown);
        return html;
    });
}

async function getPostMeta(pageId) {
    const pagesDir = config.pages_dir;
    const metaFileName = config.page_info;
    const postPath = `/${pagesDir}/${pageId}/${metaFileName}?v=${Date.now()}`;
    return await loadConfig(postPath);
}

async function loadPostPage(postId, url) {
    if (postId == null) {
        return
    }
    if ($("html").attr("oldpage") != "/") {
        return
    } else {
        $("html").attr("oldpage", "//")
    }
    const postContent = await getPostContent(postId)
    const postMeta = await getPostMeta(postId)
    $("main").html('<div class="post">' +
        '<h1 class="post-title"></h1>' +
        '<p class="post-date"></h1>' +
        '<div class="post-body"></div>' +
        '</div>')
    $(".post .post-title").text(postMeta.title)
    $(".post .post-body").html(postContent)
    if (!post_uri.includes(".php")) {
        url_post_id = post_uri + postId + "/"
    } else {
        url_post_id = post_uri.replace("$id/", "")  + postId // URLの文字列に「$id/」が混入するバグを防ぐため
    }
    window.history.pushState({}, postMeta.title + " - " + config.site_name,  url_post_id);
    $("html").attr("oldpage", "/")
    document.title = postMeta.title + " - " + config.site_name;
    $(".post .post-date").text(new Date(postMeta.date).toLocaleDateString())
    const contentElements = document.getElementsByClassName('post');
    if (contentElements.length > 0) {
        openExternalLinksInNewTab(contentElements[0]);
    }
}

async function loadContent(url) {
    const parts = config.page_uri.split("/");
    let query = url.substring(url.indexOf('?'));
    const urlParams = new URLSearchParams(query);
    if (urlParams.has('p')) {
        post_uri_this = "/pages.php?p="
    } else {
        post_uri_this = "/" + parts[1] + "/";
    }
    if (!url.startsWith(post_uri_this)) {
        $("main").fadeOut(300, function () {
            $.ajax({
                url: url,
                dataType: 'html',
                success: function (html) {
                    $("html main").html($(html).filter("main"));
                    document.title = $(html).filter("title").text();
                    window.history.pushState({}, $(html).filter("title").text(), url);
                    const contentElements = document.getElementsByClassName('post');
                    if (contentElements.length > 0) {
                        openExternalLinksInNewTab(contentElements[0]);
                    }
                    $("main").fadeIn(300);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('Failed to fetch', url);
                    console.error('Status:', textStatus);
                    console.error('Error:', errorThrown);
                }
            });
        })
    } else if (url.startsWith(post_uri_this)) {
        if (post_uri_this.includes(".php")) {
            let query = url.substring(url.indexOf('?'));
            const urlParams = new URLSearchParams(query);
            var postId = urlParams.get('p');
        } else {
            const parts = url.split("/");
            var postId = parts[2];
        }

        $("main").fadeOut(300, async function () {
            if ($("html").attr("oldpage") != "/") {
                return
            }
            await loadPostPage(postId, url)
                .then(() => {
                    $("main").fadeIn(300);
                })
                .catch((error) => {
                    console.error("投稿ページの読み込みに失敗しました:", error);
                    $("main").show();
                });
        })
    }
}