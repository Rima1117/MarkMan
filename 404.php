<?php
require_once 'lib/init.php';
require_once 'lib/functions.php';

$config = loadConfig('config.md');
$pageList = getPageList($config);
?>
<!DOCTYPE html>
<html lang="ja" oldpage="/" class="html">

<head>
    <meta charset="UTF-8">
    <title>404 Not Found - <?php echo $config['site_name']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>

<body>
    <?php echo renderMarkdownTemplate($config["templates_dir"] . '/header.md', $config); ?>
    <main class="body">
        <div class="post">
            <?php echo renderMarkdownTemplate($config["templates_dir"] . '/404.md', $config); ?>
        </div>
    </main>
    <?php echo renderMarkdownTemplate($config["templates_dir"] . '/footer.md', $config); ?>
    <?= generateCssLinks();  ?>
    <?= generateJsLinks(); ?>
    <?= generateCssLinks("custom-css.txt");  ?>
    <?= loadPlugin(); ?>
</body>

</html>