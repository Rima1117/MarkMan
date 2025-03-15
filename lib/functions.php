<?php
require_once 'Markdown.php';

/**
 * 設定ファイル (config.md) を読み込み、設定情報を配列として返す
 *
 * @param string $configPath 設定ファイルのパス
 * @return array 設定情報の連想配列
 */
function loadConfig($configPath = "config.md")
{
    $defaultConfig = [
        'site_name' => 'MarkMan',
        'pages_dir' => 'post-md',
        'page_ext' => true,
        'page_info' => 'init.md',
        'page_body' => 'post.md',
        'page_uri' => '/pages.php?p=$id',
        'page_excerpt_count' => 128,
        'templates_dir' => 'template-md',
        'site_uri' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']
    ];
    $config = $defaultConfig;
    $lines = @file($configPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines) {
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $config[trim($key)] = trim($value);
            }
        }
    } else {
        $content = '';
        foreach ($defaultConfig as $key => $value) {
            $content .= "$key: $value\n";
        }
        file_put_contents("config.md", $content);
    }
    return $config;
}

/**
 * 設定情報に基づいてページリストを取得する
 *
 * @param array $config 設定情報の連想配列
 * @return array ページリストの連想配列 (ページID => 情報)
 */
function getPageList($config)
{
    $pageList = [];
    $pagesDir = $config['pages_dir'];
    $infoFileName = $config['page_info'];
    $dirs = glob($pagesDir . '/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $pageId = basename($dir);
        $infoPath = $dir . '/' . $infoFileName;
        if (file_exists($infoPath)) {
            $info = parseInformation($infoPath);
            $pageList[$pageId] = $info;
        }
    }
    return $pageList;
}

/**
 * information.md ファイルを解析し、情報を配列として返す
 *
 * @param string $filePath information.md ファイルのパス
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
function getPostContent($pageId, $config)
{
    $pagesDir = $config['pages_dir'];
    $bodyFileName = $config['page_body'];
    $postPath = $pagesDir . '/' . $pageId . '/' . $bodyFileName;
    if (file_exists($postPath)) {
        $markdown = file_get_contents($postPath);
        $parser = new Parsedown();
        return $parser->text($markdown);
    }
    return '';
}

/**
 * Markdownテキストから設定に基づいた文字数の概要文を生成する
 *
 * @param string $markdown Markdownテキスト
 * @param array $conifg 設定情報の連想配列
 * @return string 生成された概要文
 */
function generateExcerpt($markdown, $config)
{
    $length = $config["page_excerpt_count"];
    $text = strip_tags($markdown);
    $text = str_replace(array("\r\n", "\r", "\n"), '', $text);
    $text = mb_substr($text, 0, $length);
    return $text . '…';
}

/**
 * 設定情報とページIDに基づいて ページ情報ファイルに概要文を追記する
 *
 * @param string $pageId ページID
 * @param array $config 設定情報の連想配列
 * @param string $excerpt 追記する概要文
 */
function updateInformationFile($pageId, $config, $excerpt)
{
    $pagesDir = $config['pages_dir'];
    $infoFileName = $config['page_info'];
    $infoPath = $pagesDir . '/' . $pageId . '/' . $infoFileName;
    $lines = file($infoPath);
    $newLines = [];
    $excerptAdded = false;

    foreach ($lines as $line) {
        $newLines[] = $line;
        if (strpos(trim($line), 'excerpt:') === 0) {
            $excerptAdded = true;
        }
    }
    if (!$excerptAdded) {
        $newLines[] = "\nexcerpt: " . $excerpt . "\n";
        file_put_contents($infoPath, implode('', $newLines));
    }
}

/**
 * 設定に基づいてURLを作成する
 *
 * @param string $pageId ページID
 * @param array $config 設定情報の連想配列
 * @return string 生成されたURL
 */
function getPostUri($pageId, $config)
{
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
function renderMarkdownTemplate($templatePath, $config, $placeholders = array())
{
    if (file_exists($templatePath)) {
        $markdown = file_get_contents($templatePath);
        $parser = new Parsedown();
        $html = $parser->text($markdown);

        $defaultPlaceholders = array(
            '$site_name' => $config['site_name']
        );

        $placeholders = array_merge($defaultPlaceholders, $placeholders);

        $html = str_replace(array_keys($placeholders), array_values($placeholders), $html);

        return $html;
    }
    return '';
}

/**
 * css.txtファイルの内容に基づいて、CSS読み込みHTMLを生成する
 *
 * @param string $cssFilePath css.txtファイルのパス
 * @return string CSS読み込みHTML
 */
function generateCssLinks($cssFilePath = 'css.txt')
{
    $cssLinks = '';
    if (file_exists($cssFilePath)) {
        $cssUrls = file($cssFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($cssUrls as $cssUrl) {
            $cssLinks .= '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl) . '?v=' . time() . '">' . PHP_EOL;
        }
    }
    return $cssLinks;
}

/**
 * js.txtファイルの内容に基づいて、JS読み込みHTMLを生成する
 *
 * @param string $jsFilePath js.txtファイルのパス
 * @return string JS読み込みHTML
 */
function generateJsLinks($jsFilePath = 'js.txt')
{
    $jsLinks = '';
    if (file_exists($jsFilePath)) {
        $jsUrls = file($jsFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($jsUrls as $jsUrl) {
            $jsLinks .= '<script src="' . htmlspecialchars($jsUrl) . '?v=' . time() . '"></script>' . PHP_EOL;
        }
    }
    return $jsLinks;
}
