<?php

/**
 * @author         Ryoma Kaneko (Rima1117)
 * @copyright   (C) 2025 Ryoma Kaneko.
 * @license        https://www.gnu.org/licenses/gpl-3.0.html
 * @link               https://github.com/Rima1117/MarkMan
 * @link               https://www.ryouma.dev/
 */

require_once 'lib/init.php'; ?>
<!DOCTYPE html>
<html lang="ja" oldpage="/" class="html">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?= getPageTitle("404 Not Found"); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>

<body>
    <?= renderMarkdownTemplate($config["templates_dir"] . '/header.md'); ?>
    <main class="body">
        <div class="post">
            <?= renderMarkdownTemplate($config["templates_dir"] . '/404.md'); ?>
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
    <?= generateCssLinks();  ?>
    <?= generateJsLinks(); ?>
    <?= generateCssLinks("custom-css.txt");  ?>
    <?= loadPlugin(); ?>
</body>

</html>