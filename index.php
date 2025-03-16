<?php
require_once 'lib/init.php';
require_once 'lib/functions.php';

$config = loadConfig('config.md');
$pageList = getPageList($config);
uasort($pageList, 'compareDates');
?>
<!DOCTYPE html>
<html lang="ja" oldpage="/" class="html">

<head>
    <meta charset="UTF-8">
    <title><?php echo $config['site_name']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo renderMarkdownTemplate($config["templates_dir"] . '/html-head.md', $config); ?>
</head>

<body>
    <?php echo renderMarkdownTemplate($config["templates_dir"] . '/header.md', $config); ?>
    <main class="body">
        <?php echo renderMarkdownTemplate($config["templates_dir"] . '/latest-list-head.md', $config); ?>
        <div class="post-list">
            <?php foreach ($pageList as $pageId => $info): ?>
                <a href="<?= str_replace("%3D", "=", str_replace("%3F", "?", str_replace('%2F', '/', rawurlencode(getPostUri($pageId, $config))))); ?>">
                    <div class="post-card">
                        <h2 title="<?= htmlspecialchars($info['title']); ?>"><?= htmlspecialchars($info['title']); ?></h2>
                        <p title="<?= htmlspecialchars($info['excerpt']); ?>"><?= htmlspecialchars($info['excerpt']); ?></p>
                        <?php
                        $placeholders = [
                            '$post_create_date' => date("Y/m/d", strtotime($info["date"])),
                            '$post_edit_date' => date("Y/m/d", strtotime($info["editdate"])),
                        ];
                        if (empty($info["editdate"])) {
                            echo preg_replace('/<span class="post-edited".*?<\/span>/s', '',renderMarkdownTemplate($config["templates_dir"] . '/post-card-date.md', $config, $placeholders)); 
                        } else {
                            echo renderMarkdownTemplate($config["templates_dir"] . '/post-card-date.md', $config, $placeholders); 
                        }
                       ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
    <?php echo renderMarkdownTemplate($config["templates_dir"] . '/footer.md', $config); ?>
    <div id="loadArea" style="display:none;">
        <?= generateCssLinks();  ?>
        <?= generateJsLinks(); ?>
        <?= generateCssLinks("custom-css.txt");  ?>
        <?= generateJsLinks("custom-js.txt");  ?>
        <?= loadPlugin(); ?>
    </div>
</body>

</html>