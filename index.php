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
    <title><?php echo $config['site_name']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>

<body>
    <?php echo renderMarkdownTemplate($config["templates_dir"] . '/header.md', $config); ?>
    <main class="body">
        <div class="post-list">
            <?php foreach ($pageList as $pageId => $info): ?>
                <a href="<?= str_replace('%2F', '/',rawurlencode(getPostUri($pageId, $config))); ?>">
                    <div class="post-card">
                        <h2><?= htmlspecialchars($info['title']); ?></h2>
                        <p><?= htmlspecialchars($info['excerpt']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
    <?= generateCssLinks();  ?>
    <?= generateJsLinks(); ?>
</body>

</html>