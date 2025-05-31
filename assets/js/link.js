let config;
let post_uri;

async function initialize() {
    currrent = location.href;
    try {
        config = await loadConfig();
        const urlParams = new URLSearchParams(window.location.search);
        let pageUri = config.page_uri || "pages.php?p=$id";
        const isPhpPage = pageUri.includes("pages.php");

        post_uri = urlParams.has('p') && isPhpPage ? "/pages.php?p=" : pageUri.replace("$id", "").replace(/\/+/g, "/"); // 余分なスラッシュを削除

        if (post_uri !== "/" && window.location.href.startsWith(new URL(post_uri, window.location.origin).href)) {
            const pathParts = window.location.pathname.split("/").filter(part => part !== "");
            const postId = urlParams.get('p') || pathParts.pop();

            if (!postId) {
                // postId が空文字列の場合の処理
                throw new Error("postId is empty");
            }

            await loadPostPage(postId, window.location.pathname);
        }

        $("main,.footer-nav,#mirror_message").fadeIn(300);

    } catch (error) {
        if (config.error_show == 1) {
            console.error("投稿ページの読み込みに失敗しました。404ページを表示します。エラー:", error);
        } else {
            console.error("投稿ページの読み込みに失敗しました。404ページを表示します。");
        }
        const nfPage = config.page_ext == 1 ? "/404.php" : "/404/";
        await loadContent(nfPage).then(() => {
            $("main,.footer-nav,#mirror_message").fadeIn(300, function () {
                if (window.location.pathname + window.location.search !== nfPage) {
                    window.history.replaceState({}, "", currrent);
                }
            });
        });
    }
}

initialize();

$(document).on('click', 'a[href^="/"]', async function (event) {
    const href = $(this).attr('href');
    event.preventDefault();
    await loadContent(href);
});

window.addEventListener('popstate', async function (event) {
    const href = window.location.pathname + window.location.search;
    event.preventDefault();
    await loadContent(href);
});

async function loadConfig(configPath = "/config.md") {
    try {
        const response = await $.ajax({
            url: configPath + "?v=" + Date.now(),
            dataType: "text",
        });

        const config = {};
        const lines = response.split("\n");

        for (const line of lines) {
            const trimmedLine = line.trim();

            if (trimmedLine.startsWith("#") || trimmedLine.length === 0) {
                continue;
            }

            if (trimmedLine.includes(":")) {
                const [key, ...valueParts] = trimmedLine.split(":");
                const value = valueParts.join(":").trim();
                const trimmedKey = key.trim();

                if (!isNaN(value)) {
                    config[trimmedKey] = Number(value);
                } else if (value.toLowerCase() === "true") {
                    config[trimmedKey] = true;
                } else if (value.toLowerCase() === "false") {
                    config[trimmedKey] = false;
                } else {
                    config[trimmedKey] = value;
                }
            }
        }

        return config;
    } catch (error) {
        console.error("設定ファイルの読み込みに失敗しました:", error);
        return {};
    }
}

async function getPostContent(pageId) {
    if (!config || !config.pages_dir || !config.page_body) {
        console.error("設定オブジェクトが不正です。");
        return null;
    }

    if (config.pages_dir_uri == undefined) {
        pagesDir = "/" + config.pages_dir;
    } else {
        pagesDir = config.pages_dir_uri
    }
    const bodyFileName = config.page_body;
    const postPath = `${pagesDir}/${pageId}/${bodyFileName}?v=${Date.now()}`;

    try {
        const markdown = await $.ajax({
            url: postPath,
            dataType: "text",
        });

        const html = marked.parse(markdown);

        return html;
    } catch (error) {
        console.error("コンテンツの取得に失敗しました。404ページに移動します。\nエラー:", error);
        const nfPage = config.page_ext == 1 ? "/404.php" : "/404/";
        await loadContent(nfPage).then(() => {
            $("main,.footer-nav,#mirror_message").fadeIn(300, function () {
                if (window.location.pathname + window.location.search !== nfPage) {
                    window.history.replaceState({}, "", currrent);
                }
            });
        });
        return null;
    }
}

async function getPostMeta(pageId) {
    if (!config || !config.pages_dir || !config.page_info) {
        console.error("設定オブジェクトが不正です。");
        return null;
    }

    if (config.pages_dir_uri == undefined) {
        pagesDir = "/" + config.pages_dir;
    } else {
        pagesDir = config.pages_dir_uri
    }
    const metaFileName = config.page_info;
    const postPath = `${pagesDir}/${pageId}/${metaFileName}?v=${Date.now()}`;

    try {
        const meta = await loadConfig(postPath);
        return meta;
    } catch (error) {
        console.error("記事のメタデータの取得に失敗しました:", error);
        return null;
    }
}

async function loadPostPage(postId, url) {
    if (postId == null) {
        return;
    }

    if (!config || !config.templates_dir || !config.pages_dir || !config.site_name) {
        console.error("設定オブジェクトが不正です。");
        return;
    }

    const currentPage = $("html").attr("oldpage");
    if (currentPage !== "/") {
        return;
    }

    $("html").attr("oldpage", "//");

    try {
        const postContent = await getPostContent(postId);
        const postMeta = await getPostMeta(postId);

        $("main").html(`
            <div class="post">
                <div class="post-top"></div>
                <div class="post-body"></div>
            </div>
        `);

        const headerPath = `/${config.templates_dir}/post-header.md?v=${Date.now()}`;
        const markdown = await $.ajax({
            url: headerPath,
            dataType: "text",
        });

        const pagesDir = config.pages_dir;
        const thumbDir = `/${pagesDir}/${postId}/`;
        const placeholders = [
            { from: '$post_title', to: postMeta.title },
            { from: '$post_thumb', to: String(postMeta.titlethumb).startsWith('/') ? postMeta.titlethumb : thumbDir + postMeta.titlethumb },
            { from: '$post_create_date', to: new Date(postMeta.date).toLocaleDateString() },
            { from: '$post_edit_date', to: new Date(postMeta.editdate).toLocaleDateString() },
        ];

        let postHeadHtml = marked.parse(markdown);
        placeholders.forEach(replacement => {
            postHeadHtml = postHeadHtml.replaceAll(replacement.from, replacement.to);
        });

        if (!postMeta.titlethumb) {
            postHeadHtml = postHeadHtml.replace(/<div class="post-thumb">.*?<\/div>/s, '');
        }
        if (!postMeta.editdate) {
            postHeadHtml = postHeadHtml.replace(/<span class="post-edited" .*?<\/span>/s, '');
        }

        $("main .post .post-top").html(postHeadHtml);
        $("main .post .post-body").html(postContent);

        let url_post_id;
        if (!url.includes(".php")) {
            url_post_id = url.startsWith('/') ? url : '/' + url;
        } else {
            url_post_id = config.page_uri.replace("$id", "") + postId;
        }

        window.history.pushState({}, postMeta.title + " - " + config.site_name, url_post_id);
        $("main,.footer-nav,#mirror_message").fadeIn(300);
        $("html").attr("oldpage", "/");
        document.title = postMeta.title + " - " + config.site_name;

        const contentElements = document.getElementsByClassName('post');
        if (contentElements.length > 0) {
            if (typeof pageInit === 'function') {
                pageInit(contentElements[0]);
            } else {
                console.error("pageInit 関数が定義されていません。");
            }
        }
    } catch (error) {
        console.error("投稿ページのロードに失敗しました:", error);
        $("html").attr("oldpage", "/");
    }
}

async function loadContent(url) {
    if (!config || !config.page_uri || config.error_show === undefined || config.page_ext === undefined) {
        console.error("設定オブジェクトが不正です。");
        return;
    }

    const pageUriParts = config.page_uri.split("/");
    const query = url.substring(url.indexOf('?'));
    const urlParams = new URLSearchParams(query);
    const postId = urlParams.get('p') || url.split("/")[2];
    const isPostPage = url.startsWith(urlParams.has('p') ? "/pages.php?p=" : `/${pageUriParts[1]}/`);

    $("main, .footer-nav,#mirror_message").fadeOut(300, async () => {
        try {
            if (!isPostPage) {
                const response = await $.ajax({
                    url: url,
                    dataType: 'html',
                });
                $("html main").html($(response).filter("main"));
                document.title = $(response).filter("title").text();
                window.history.pushState({}, document.title, url);
                $("main,.footer-nav,#mirror_message").fadeIn(300)
                const contentElements = document.getElementsByClassName('post');
                if (contentElements.length > 0) {
                    pageInit(contentElements[0]);
                }
            } else {
                await loadPostPage(postId, url)
            }
        } catch (error) {
            console.error("コンテンツのロードに失敗しました:", error);
            if (isPostPage) {
                const errorMessage = config.error_show == 1 ? `投稿ページの読み込みに失敗しました。404ページを表示します。エラー: ${error}` : "投稿ページの読み込みに失敗しました。404ページを表示します。";
                console.error(errorMessage);
                const notFoundPage = config.page_ext == 1 ? "/404.php" : "/404/";
                try {
                    await loadContent(notFoundPage);
                    if (window.location.pathname + window.location.search !== notFoundPage) {
                        window.history.replaceState({}, "", location.href);
                    }
                    $("main, .footer-nav,#mirror_message").fadeIn(300);
                } catch (notFoundError) {
                    console.error("404ページのロードにも失敗しました:", notFoundError);
                    $("main, .footer-nav,#mirror_message").fadeIn(300);
                }
            } else {
                const errorMessage = config.error_show == 1 ? `ページの読み込みに失敗しました。404ページを表示します。エラー: ${error}` : "ページの読み込みに失敗しました。404ページを表示します。";
                console.error(errorMessage);
                const notFoundPage = config.page_ext == 1 ? "/404.php" : "/404/";
                try {
                    await loadContent(notFoundPage);
                    if (window.location.pathname + window.location.search !== notFoundPage) {
                        window.history.replaceState({}, "", location.href);
                    }
                    $("main, .footer-nav,#mirror_message").fadeIn(300);
                } catch (notFoundError) {
                    console.error("404ページのロードにも失敗しました:", notFoundError);
                    $("main, .footer-nav,#mirror_message").fadeIn(300);
                }
            }
        }
    });
}