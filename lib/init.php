<?php

/**
 * @author         Ryoma Kaneko (Rima1117)
 * @copyright   (C) 2025 Ryoma Kaneko.
 * @license        https://www.gnu.org/licenses/gpl-3.0.html
 * @link               https://github.com/Rima1117/MarkMan
 * @link               https://www.ryouma.dev/
 */

ini_set("default_charset", "UTF-8");
ini_set("display_errors", 1);

// エラーチェック用
$error_file = [];
$success_file = [];

// Parsedown
if (!file_exists("lib/Markdown.php")) {
    $result = @file_put_contents("lib/Markdown.php", file_get_contents("https://raw.githubusercontent.com/erusev/parsedown/refs/heads/master/Parsedown.php"));
    if (!$result) {
        $error_file[] = "Parsedown";
    } else {
        $success_file[] = "Parsedown";
    }
}

// crawler-user-agents
if (!file_exists("lib/bot-ua.json")) {
    $result = @file_put_contents("lib/bot-ua.json", file_get_contents("https://raw.githubusercontent.com/monperrus/crawler-user-agents/refs/heads/master/crawler-user-agents.json"));
    if (!$result) {
        $error_file[] = "crawler-user-agents";
    } else {
        $success_file[] = "crawler-user-agents";
    }
}

// デフォルトの設定がない場合、設定を適用
if (file_exists("config.md")) {
    $config_set_status = "exists";
}
if (!file_exists("config.md")) {
    $content = '';
    require("defaultConfig.php");
    foreach ($defaultConfig as $key => $value) {
        $content .= "$key: $value\n";
    }
    $config_set_status = @file_put_contents("config.md", $content);
}

// ページにダウンロードの状態を表記させる
if (isset($error_file[0]) || isset($success_file[0])) {
    require_once 'lib/functions.php';
    // 設定の読み込み
    $config = loadConfig();
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>ライブラリダウンロード - <?= htmlspecialchars($config["site_name"]); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    </head>

    <body>
        <div class="post" style="text-align: center;">
            <h1 class="post-title">ライブラリダウンロード</h1>
            <div class="post-body">
                <?php
                $e_libs = implode('と', $error_file);
                $s_libs = implode('と', $success_file);
                if (count($error_file) >= 1) {
                    echo '<p>' . $e_libs . 'のライブラリのダウンロード失敗しました。<br />手動でダウンロードするか、パーミッション・インターネットの設定を確認してください。</p>';
                } else {
                    echo '<p>ライブラリ（' . $s_libs . '）のダウンロードに成功しました！</p>';
                }
                echo '<br />';
                if (!$config_set_status) {
                    echo '<p>デフォルトの設定を「config.md」に保存できませんでした。<br />手動で以下のデータを「' . __DIR__ . '/config.md」に保存してください。</p>';
                } elseif ($config_set_status != "exists") {
                    echo '<p>また、デフォルトの設定を「config.md」に保存しました</p>';
                }
                if (count($error_file) == 0) {
                    echo '<br /><p>処理は完了しました。続けるにはページを再読み込みしてください。</p><p><button onclick="location.reload();">再読み込み</button></p>';
                }
                ?>
            </div>
        </div>
        <?=
        // CSS リンクを生成
        generateCssLinks();
        // JS リンクを生成
        generateJsLinks();
        // CSS リンクを生成
        generateCssLinks("custom-css.txt");
        ?>
    </body>

    </html>
<?php
    exit;
} else {
    // 必要なファイルの読み込み
    require_once 'Markdown.php';
    require_once 'lib/functions.php';
}
