<?php

/**
 * @author         Ryoma Kaneko (Rima1117)
 * @copyright   (C) 2025 Ryoma Kaneko.
 * @license        https://www.gnu.org/licenses/gpl-3.0.html
 * @link               https://github.com/Rima1117/MarkMan
 * @link               https://www.ryouma.dev/
 */

// 初期化ファイルの読み込み
require_once 'lib/init.php';
global $config;

// ページIDの取得処理
if (!isset($_GET["p"])) {
    // パラメータ"p"がない場合、URLのパス部分をページIDとする
    $pageId = basename($_SERVER['REQUEST_URI']);
} else {
    // パラメータ"p"がある場合、その値をURLデコードしてページIDとする
    $pageId = urldecode(basename($_GET["p"]));
}

// 正規のURLへリダイレクト
if (getPostUri($pageId) != $_SERVER['REQUEST_URI']) {
    // configで指定されたURLテンプレートと現在のURLが異なる場合、リダイレクトする
    header("Location: " . getPostUri($pageId));
}

// ページ情報の取得
$info = @getPageList()[$pageId];

if (empty($info["title"])) {
    header("Location: " . getNotFoundUri());
}

// ページコンテンツの取得
$content = @getPostContent($pageId);

// 抜粋が存在しない場合の処理
if (!isset($info['excerpt']) || empty($info['excerpt'])) {
    // 抜粋を生成
    $excerpt = generateExcerpt($content);
    // 情報をファイルに書き込み、更新
    updateInformationFile($pageId, $excerpt);
    // ページ情報に抜粋を設定
    $info['excerpt'] = $excerpt;
}

// bot判別用のJSONファイルを読み込む
$json = file_get_contents('lib/bot-ua.json');
// JSONデータを配列にデコード
$arr = json_decode($json, true);

// パターンリストを格納する配列
$pattern_list = [];
// 配列からパターンを抽出して$pattern_listに格納
foreach ($arr as $key => $value) {
    $pattern_list[] = $value['pattern'];
}

// パターンリストを結合して正規表現パターン文字列を作成
$pattern_list_string = implode('|', $pattern_list);

// ユーザーエージェントを取得
$ua = $_SERVER['HTTP_USER_AGENT'];

// ユーザーエージェントがbotのパターンにマッチするかどうかを判定
if (preg_match('/' . $pattern_list_string . '/', $ua)) {
    $bot = true;
} else {
    $bot = false;
}
?>
<!DOCTYPE html>
<html lang="ja" oldpage="/" class="html">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?= getPageTitle($info["title"]); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    if (!empty($info["title"])) {
    ?>
        <meta name="description" content="<?= htmlspecialchars($info['excerpt']); ?>">
        <meta property="og:title" content="<?= htmlspecialchars($info['title']); ?>">
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?= htmlspecialchars($config['site_uri'] . getPostUri($pageId, $config)); ?>">
        <meta property="og:description" content="<?= htmlspecialchars($info['excerpt']); ?>">
        <meta property="og:site_name" content="<?= htmlspecialchars($config['site_name']); ?>">
        <?php
        // サムネイル画像が設定されている場合の処理
        if (isset($info['titlethumb'])) {
            // サムネイル画像のディレクトリURLを生成
            $thumbDir   = htmlspecialchars(rtrim($config["site_uri"], "/")) . "/" . $config["pages_dir"] . "/" . basename($pageId) . "/";
            // サムネイル画像のURLを決定 (絶対パスまたは相対パス)
            $thumb = (strpos($info['titlethumb'], '/') === 0) ? $info['titlethumb'] : $thumbDir . $info['titlethumb'];
        ?>
            <meta property="og:image" content="<?= htmlspecialchars($thumb); ?>">
    <?php
        }
    }
    ?>
    <?php echo renderMarkdownTemplate($config["templates_dir"] . '/html-head.md'); ?>
</head>

<body>
    <?php echo renderMarkdownTemplate($config["templates_dir"] . '/header.md'); ?>
    <main class="body">
        <?php if ($bot) { ?>
            <div class="post">
                <h1 class="post-title"><?php echo htmlspecialchars($info['title']); ?></h1>
                <p class="post-date"><?php echo date("Y/m/d", strtotime($info["date"])); ?></p>
                <div class="post-body"><?php echo $content; ?></div>
            </div>
        <?php }; ?>
    </main>
    <?php
    if ($config["mirror_message"] == 1) {
    ?>
        <p style="text-align:center;" id="mirror_message"><a href="<?= htmlspecialchars($config["pages_dir_uri"]); ?>"><?= htmlspecialchars($config["pages_dir_uri"]); ?></a> からのミラー。</p>
    <?php
    }
    // Markdownテンプレートからフッター部分を生成
    echo renderMarkdownTemplate($config["templates_dir"] . '/footer.md');
    if ($config["footer_version"] == 1) {
    ?>
        <p style="margin:0;text-align:right;padding-right:14px;background-color:var(--bg-secondary)" id="mirror_message">Powered by MarkMan v<?= htmlspecialchars(getVersionInfo()["version"]); ?></p>
    <?php
    }
    ?>
    <div id="loadArea" style="display:none;">
        <?php
        // CSS リンクを生成
        echo generateCssLinks();
        // JS リンクを生成
        echo generateJsLinks();

        // ?custom-cssでカスタムCSS(テーマ)を無効化できるように
        if (!isset($_GET["custom-css"])) {
            echo generateCssLinks("custom-css.txt");
        }
        // カスタムJSリンクを生成
        echo generateJsLinks("custom-js.txt");
        // プラグインのロード
        echo loadPlugin();
        ?>
    </div>
</body>

</html>