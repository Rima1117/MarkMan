<?php

/**
 * @author         Ryoma Kaneko (Rima1117)
 * @copyright   (C) 2025 Ryoma Kaneko.
 * @license        https://www.gnu.org/licenses/gpl-3.0.html
 * @link               https://github.com/Rima1117/MarkMan
 * @link               https://www.ryouma.dev/
 */

require_once("defaultConfig.php");
global $defaultConfig;
global $defaultPlaceholders;
global $dateFormat;
global $config;

/**
 * 設定ファイル (config.md) を読み込み、設定情報を配列として返す
 *
 * @param string $configPath 設定ファイルのパス
 * @return array 設定情報の連想配列
 */
function loadConfig($configPath = "config.md")
{
    global $defaultConfig;
    $config = $defaultConfig;

    if (file_exists($configPath) && is_readable($configPath)) {
        $lines = file($configPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $config[trim($key)] = trim($value);
                }
            }
        }
    } else {
        $content = '';
        foreach ($defaultConfig as $key => $value) {
            $content .= "$key: $value\n";
        }
        if (file_put_contents($configPath, $content) === false) {
            error_log("設定ファイル (config.md) の作成に失敗しました。");
        }
    }
    return $config;
}

/**
 * 設定情報に基づいてページリストを取得する
 *
 * @param array $config 設定情報の連想配列
 * @return array ページリストの連想配列 (ページID => 情報)
 */
function getPageList()
{
    global $config;
    $pageList = [];
    $pagesDir = $config['pages_dir'];
    $infoFileName = $config['page_info'];
    $pageDirectories = glob($pagesDir . '/*', GLOB_ONLYDIR);

    if ($pageDirectories === false) {
        error_log("ディレクトリのスキャンに失敗しました: " . $pagesDir);
        return $pageList;
    }

    foreach ($pageDirectories as $pageDirectory) {
        $pageId = basename($pageDirectory);
        $infoFilePath = $pageDirectory . '/' . $infoFileName;

        if (file_exists($infoFilePath)) {
            try {
                $pageInfo = parseInformation($infoFilePath);
                $pageList[$pageId] = $pageInfo;
            } catch (Exception $e) {
                error_log("設定ファイルの解析に失敗しました: " . $infoFilePath . " - " . $e->getMessage());
            }
        }
    }

    return $pageList;
}

/**
 * init.md ファイルを解析し、情報を配列として返す
 *
 * @param string $filePath init.md ファイルのパス
 * @return array 情報の連想配列
 */
function parseInformation($filePath)
{
    $info = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $info[trim($key)] = trim($value);
        }
    }
    return $info;
}

/**
 * 設定情報とページIDに基づいて post.md の内容を取得し、HTMLに変換して返す
 *
 * @param string $pageId ページID
 * @param array $config 設定情報の連想配列
 * @return string HTMLに変換された記事の内容
 */
function getPostContent($pageId)
{
    global $config;
    $pagesDir = $config['pages_dir'];
    $bodyFileName = $config['page_body'];
    $postPath = $pagesDir . '/' . $pageId . '/' . $bodyFileName;

    if (!file_exists($postPath) || !is_readable($postPath)) {
        error_log("記事ファイルが存在しないか、読み取りできません: " . $postPath);
        return '';
    }

    $markdown = file_get_contents($postPath);

    if ($markdown === false) {
        error_log("記事ファイルの読み込みに失敗しました: " . $postPath);
        return '';
    }

    try {
        static $parser = null;
        if ($parser === null) {
            $parser = new Parsedown();
        }
        return $parser->text($markdown);
    } catch (Exception $e) {
        error_log("Markdownの解析に失敗しました: " . $e->getMessage());
        return '';
    }
}

/**
 * Markdownテキストから設定に基づいた文字数の概要文を生成する
 *
 * @param string $markdown Markdownテキスト
 * @param array $config 設定情報の連想配列
 * @return string 生成された概要文
 */
function generateExcerpt($markdown)
{
    global $config;
    $length = $config["page_excerpt_count"] ?? 128;
    $ellipsis = $config["page_excerpt_ellipsis"] ?? '…';

    $parsedown = new Parsedown();
    $html = $parsedown->text($markdown);

    $text = strip_tags($html);
    $text = str_replace(array("\r\n", "\r", "\n"), '', $text);

    if (mb_strlen($text) > $length) {
        $text = mb_substr($text, 0, $length) . $ellipsis;
    }

    return $text;
}

/**
 * 設定情報とページIDに基づいて ページ情報ファイルに概要文を追記する
 *
 * @param string $pageId ページID
 * @param array $config 設定情報の連想配列
 * @param string $excerpt 追記する概要文
 */
function updateInformationFile($pageId, $excerpt)
{
    global $config;
    $pagesDir = $config['pages_dir'];
    $infoFileName = $config['page_info'];
    $infoPath = $pagesDir . '/' . $pageId . '/' . $infoFileName;
    $lines = @file($infoPath);
    $newLines = [];
    $excerptAdded = false;

    if (!empty($lines)) {
        foreach ($lines as $line) {
            $newLines[] = $line;
            if (strpos(trim($line), 'excerpt:') === 0) {
                $excerptAdded = true;
            }
        }
        if (!$excerptAdded) {
            $newLines[] = "\nexcerpt: " . $excerpt . "\n";
            @file_put_contents($infoPath, implode('', $newLines));
        }
    }
}

/**
 * 設定に基づいてURLを作成する
 *
 * @param string $pageId ページID
 * @param array $config 設定情報の連想配列
 * @return string 生成されたURL
 */
function getPostUri($pageId)
{
    global $config;
    $uriTemplate = $config['page_uri'];
    $url = str_replace('$id', $pageId, $uriTemplate);
    return $url;
}

/**
 * Markdownテンプレートを読み込み、HTMLに変換して返す
 *
 * @param string $templatePath テンプレートファイルのパス
 * @param array $config 設定情報の連想配列
 * @param array $placeholders プレースホルダーと置換値の連想配列 (オプション)
 * @return string HTMLに変換されたテンプレートの内容
 */
function renderMarkdownTemplate($templatePath, $placeholders = array())
{
    if (file_exists($templatePath) && is_readable($templatePath)) {
        global $defaultPlaceholders;
        $markdown = file_get_contents($templatePath);
        $parser = new Parsedown();
        $html = $parser->text($markdown);

        $placeholders = array_merge($defaultPlaceholders, $placeholders);

        $html = str_replace(array_keys($placeholders), array_map('htmlspecialchars', array_values($placeholders)), $html);
        $html = "<!-- Hello Markdown World!-->\n" . $html;

        return indentHTML($html);
    }
    return '';
}

/**
 * css.txtファイルの内容に基づいて、CSS読み込みHTMLを生成する (v1.0.1 追加)
 * "#" から始まる行はコメントとして扱う
 * "!" から始まる行はunixtimeによるキャッシュ無効化をしない
 *
 * @param string $cssFilePath css.txtファイルのパス
 * @return string CSS読み込みHTML
 */
function generateCssLinks($cssFilePath = 'css.txt')
{
    $cssLinks = '';
    if (file_exists($cssFilePath)) {
        $config = loadConfig();
        $siteUri = htmlspecialchars(rtrim($config["site_uri"], "/"));
        $cssUrls = file($cssFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($cssUrls as $cssUrl) {
            $trimmedCssUrl = trim($cssUrl);
            if (strpos($trimmedCssUrl, '#') === 0) {
                continue;
            }
            $cacheBuster = strpos($trimmedCssUrl, '!') === 0 ? '' : '?v=' . time();
            $url = strpos($trimmedCssUrl, '/') === 0 ? $siteUri . $trimmedCssUrl : $trimmedCssUrl;
            $url = htmlspecialchars(strpos($trimmedCssUrl, '!') === 0 ? substr($url, 1) : $url);
            $cssLinks .= '<link rel="stylesheet" href="' . $url . $cacheBuster . '">' . PHP_EOL;
        }
    }
    return $cssLinks;
}

/**
 * js.txtファイルの内容に基づいて、JS読み込みHTMLを生成する (v1.0.1 追加)
 * "#" から始まる行はコメントとして扱う
 * "!" から始まる行はunixtimeによるキャッシュ無効化をしない
 *
 * @param string $jsFilePath js.txtファイルのパス
 * @return string JS読み込みHTML
 */
function generateJsLinks($jsFilePath = 'js.txt')
{
    $jsLinks = '';
    if (file_exists($jsFilePath)) {
        $config = loadConfig();
        $siteUri = htmlspecialchars(rtrim($config["site_uri"], "/"));
        $jsUrls = file($jsFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($jsUrls as $jsUrl) {
            $trimmedJsUrl = trim($jsUrl);
            if (strpos($trimmedJsUrl, '#') === 0) {
                continue;
            }
            $cacheBuster = strpos($trimmedJsUrl, '!') === 0 ? '' : '?v=' . time();
            $url = strpos($trimmedJsUrl, '/') === 0 ? $siteUri . $trimmedJsUrl : $trimmedJsUrl;
            $url = htmlspecialchars(strpos($trimmedJsUrl, '!') === 0 ? substr($url, 1) : $url);
            $jsLinks .= '<script src="' . $url . $cacheBuster . '"></script>' . PHP_EOL;
        }
    }
    return $jsLinks;
}

/**
 * plugin.txtファイルの内容に基づいて、JS・CSS読み込みHTMLを生成する (v1.0.1 追加)
 * "#" から始まる行はコメントとして扱う
 * "!" から始まる行はunixtimeによるキャッシュ無効化をしない
 *
 * @param string $pluginFilePath plugin.txtファイルのパス
 * @return string Plugin読み込みHTML
 */
function loadPlugin($pluginFilePath = "plugin.txt")
{
    $links = '';
    if (file_exists($pluginFilePath)) {
        $config = loadConfig();
        $siteUri = htmlspecialchars(rtrim($config["site_uri"], "/"));
        $plugins = file($pluginFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($plugins as $plUrl) {
            $trimmedPlUrl = trim($plUrl);
            if (strpos($trimmedPlUrl, '#') === 0) {
                continue;
            }
            $cacheBuster = strpos($trimmedPlUrl, '!') === 0 ? '' : '?v=' . time();
            $pluginName = strpos($trimmedPlUrl, '!') === 0 ? substr($trimmedPlUrl, 1) : $trimmedPlUrl;
            $links .= sprintf(
                '<script src="%s/assets/js/plugins/%s.js%s"></script>' . PHP_EOL .
                    '<link rel="stylesheet" href="%s/assets/css/plugins/%s.css%s">',
                $siteUri,
                htmlspecialchars($pluginName),
                $cacheBuster,
                $siteUri,
                htmlspecialchars($pluginName),
                $cacheBuster
            );
        }
        return $links;
    }
    return '';
}

/**
 * 日付のデータを比較し、どちらが新しいのか返す関数。 (v1.0.1 追加)
 *
 * @param string $a 比較対象A
 * @return string $b比較対象B
 */
function compareDates($a, $b)
{
    return strtotime($b['date']) - strtotime($a['date']);
}


/**
 * ドメインからサブドメインを削除する関数 (v1.0.1.1 追加)
 *
 * @param string $domain サブドメインを含むドメイン文字列
 * @return string サブドメインを削除したドメイン文字列
 */
function removeSubdomains(string $domain): string
{
    // ホスト部分を抽出
    $host = parse_url($domain, PHP_URL_HOST);

    if (!$host) {
        return $domain;
    }

    $parts = explode('.', $host);

    if (count($parts) >= 2) {
        $baseDomain = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
        return $baseDomain;
    }

    return $domain;
}

/**
 * HTMLのインデントを整形する関数 (v1.0.1.1 追加)
 *
 * @param string $html 整形前のHTML文字列
 * @param string $indentStr インデントに使用する文字列（デフォルトは半角スペース4つ）
 * @return string 整形後のHTML文字列
 */
function indentHTML(string $html, string $indentStr = '    '): string
{
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();

    $html =  mb_encode_numericentity($html, [0x80, 0x10ffff, 0, 0x1fffff]);
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    libxml_clear_errors();

    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    $formattedHtml = $dom->saveHTML();
    if ($indentStr !== '    ') {
        $formattedHtml = preg_replace('/\s+/', $indentStr, $formattedHtml);
    }

    $formattedHtml = preg_replace('/<!DOCTYPE.*?>/', '', $formattedHtml);
    $formattedHtml = preg_replace('/<body[^>]*>/', '', $formattedHtml);
    $formattedHtml = str_replace('</body>', '', $formattedHtml);
    $formattedHtml = str_replace('<html>', '', $formattedHtml);
    $formattedHtml = str_replace('</html>', '', $formattedHtml);

    $formattedHtml = trim($formattedHtml);
    return $formattedHtml;
}


/**
 * 複数のini設定を配列で設定する関数 (v1.0.1.1 追加)
 *
 * @param array $settings 設定の配列。連想配列でキーが設定名、値が設定値。
 * @return void
 */
function setIniSettings(array $settings): void
{
    foreach ($settings as $key => $value) {
        if (is_string($key)) {
            ini_set($key, $value);
        }
    }
}

/** 404ページの内部リンクを返す関数 (v1.0.1.1 追加)
 *
 * @return array 404ページの内部リンク
 */
function getNotFoundUri()
{
    global $config;
    if ($config["page_ext"] == 1) {
        $uri = "/404.php";
    } else {
        $uri = "/404/";
    }
    return $uri;
}

/**
 * VERSIONファイルを読み取り、バージョンとリリース日を返す関数 (v1.0.1.1 追加)
 *
 * @param string $filePath VERSIONファイルのパス
 * @return array バージョンとリリース日の連想配列。ファイルが存在しない場合は空の配列を返す。
 */
function getVersionInfo(string $filePath = "VERSION"): array
{
    if (!file_exists($filePath)) {
        return [];
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    $version = '';
    $releaseDate = '';
    foreach ($lines as $line) {
        if (strpos($line, 'Version:') === 0) {
            $version = trim(substr($line, strpos($line, ':') + 1));
        } elseif (strpos($line, 'Release Date:') === 0) {
            $releaseDate = trim(substr($line, strpos($line, ':') + 1));
        }
    }

    return [
        'version' => $version,
        'releaseDate' => $releaseDate,
    ];
}

/**
 * Configに基づいてページのタイトルテキストを返す関数 (v1.0.1.1 追加)
 *
 * @param string $title タイトル
 * @return array  ページタイトル
 */
function getPageTitle($title = "")
{
    global $config;
    if (!empty($title)) {
        global $defaultPlaceholders;
        $placeholders = [
            '$title' => $title
        ];
        $placeholders = array_merge($defaultPlaceholders, $placeholders);
        $page_title = str_replace(array_keys($placeholders), array_map('htmlspecialchars', array_values($placeholders)), $config["site_page_title"]);
    } else {
        $page_title = $config["site_name"];
    }
    return htmlspecialchars($page_title);
}
