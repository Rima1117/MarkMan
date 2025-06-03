<?php

/**
 * @author         Ryoma Kaneko (Rima1117)
 * @copyright   (C) 2025 Ryoma Kaneko.
 * @license        https://www.gnu.org/licenses/gpl-3.0.html
 * @link               https://github.com/Rima1117/MarkMan
 * @link               https://www.ryouma.dev/
 */
?>

<?php
// 初期化ファイルの読み込み
require_once 'lib/init.php';
global $config;

// ページリストの取得
$pageList = @getPageList();

// ページリストを日付でソート
uasort($pageList, 'compareDates');
?>
<!DOCTYPE html>
<html lang="ja" oldpage="/" class="html">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?= getPageTitle(); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?=
    // MarkdownテンプレートからHTMLのhead部分を生成
    renderMarkdownTemplate($config["templates_dir"] . '/html-head.md');
    ?>
</head>

<body>
    <?=
    // Markdownテンプレートからヘッダー部分を生成
    renderMarkdownTemplate($config["templates_dir"] . '/header.md');
    ?>
    <main class="body">
        <?=
        // Markdownテンプレートから最新記事リストのヘッダー部分を生成
        renderMarkdownTemplate($config["templates_dir"] . '/latest-list-head.md');
        ?>
        <div class="post-list">
            <?php foreach ($pageList as $pageId => $info): ?>
                <a href="<?= str_replace("%3D", "=", str_replace("%3F", "?", str_replace('%2F', '/', rawurlencode(getPostUri($pageId))))); ?>">
                    <div class="post-card">
                        <?php
                        // アイキャッチ画像の取得と表示
                        if (isset($info["titlethumb"]) && !empty($info["titlethumb"])) {
                            $thumb_uri = $info["titlethumb"];
                            // アイキャッチ画像が設定されている場合はそのURIを使用してアイキャッチ画像を表示
                            if (isset($info["titlethumb"]) && !empty($info["titlethumb"])) {
                                $thumb_uri = $config["pages_dir_uri"] . '/' . $pageId . '/' . $info["titlethumb"];
                            }
                        ?>
                            <div class="post-card-thumb">
                                <img src="<?= htmlspecialchars($thumb_uri); ?>" alt="<?= htmlspecialchars($info['title']); ?>" loading="lazy" decoding="async" class="post-card-thumb-img">
                            </div>
                            <?php
                        } else {
                            // アイキャッチ画像が設定されていない場合はデフォルトのサムネイルを取得
                            if (isset($config["default_thumb_uri"]) && !empty($config["default_thumb_uri"])) {
                                $thumb_uri = $config["default_thumb_uri"];
                            } else {
                                // デフォルトのサムネイルURIが設定されていない場合はHTMLベースのサムネイルを生成
                            ?>
                                <div class="post-card-thumb">
                                    <span class="post-card-thumb-text">No Images</span>
                                </div>
                        <?php
                            }
                        }
                        ?>
                        <h2 title="<?= htmlspecialchars($info['title']); ?>"><?= htmlspecialchars($info['title']); ?></h2>
                        <p title="<?= htmlspecialchars($info['excerpt']); ?>"><?= htmlspecialchars($info['excerpt']); ?></p>
                        <?php
                        // 日付表示用のプレースホルダーを定義
                        $placeholders = [
                            '$post_create_date' => date("Y/m/d", strtotime($info["date"])),
                            '$post_edit_date' => date("Y/m/d", strtotime($info["editdate"])),
                        ];

                        // 編集日が空の場合は編集日表示部分を削除して出力
                        if (empty($info["editdate"])) {
                            echo preg_replace('/<span class="post-edited".*?<\/span>/s', '', renderMarkdownTemplate($config["templates_dir"] . '/post-card-date.md', $placeholders));
                        } else {
                            // 編集日がある場合はそのまま出力
                            echo renderMarkdownTemplate($config["templates_dir"] . '/post-card-date.md', $placeholders);
                        }
                        ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
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