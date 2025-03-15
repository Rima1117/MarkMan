<?php
require_once 'lib/init.php';
require_once 'lib/functions.php';

$config = loadConfig();
if (!isset($_GET["p"])) {
    $pageId = basename($_SERVER['REQUEST_URI']);
} else {
    $pageId = urldecode(basename($_GET["p"]));
}

if (getPostUri($pageId, $config) != $_SERVER['REQUEST_URI']) {
    header("Location: " . getPostUri($pageId, $config));
}

$info = getPageList($config)[$pageId];
$content = getPostContent($pageId, $config);

if (!isset($info['excerpt']) || empty($info['excerpt'])) {
    $excerpt = generateExcerpt($content, $config);
    updateInformationFile($pageId, $config, $excerpt);
    $info['excerpt'] = $excerpt;
}

$json = file_get_contents('/lib/bot-ua.json');

$arr = json_decode($json, true);

$pattern_list = [];
foreach ($arr as $key => $value) {
    $pattern_list[] = $value['pattern'];
}

$pattern_list_string = implode('|', $pattern_list);

$ua = $_SERVER['HTTP_USER_AGENT'];

if (preg_match('/' . $pattern_list_string . '/', $ua)) {
    $bot = false;
} else {
    $bot = true;
}
?>
<!DOCTYPE html>
<html oldpage="/">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($info['title']); ?> - <?php echo htmlspecialchars($config['site_name']); ?></title>
    <meta name="description" content="<?= htmlspecialchars($info['excerpt']); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <meta property="og:title" content="<?= htmlspecialchars($info['title']); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($config['site_uri'] . getPostUri($pageId, $config)); ?>">
    <meta property="og:description" content="<?= htmlspecialchars($info['excerpt']); ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($config['site_name']); ?>">
</head>

<body>
    <?php echo renderMarkdownTemplate($config["templates_dir"] . '/header.md', $config); ?>
    <main class="body">
        <?php if ($bot) { ?>
            <div class="post">
                <h1 class="post-title"><?php echo htmlspecialchars($info['title']); ?></h1>
                <p class="post-date"><?php echo date("Y/m/d", strtotime($info["date"])); ?></p>
                <div class="post-body"><?php echo $content; ?></div>
            </div>
        <?php } ?>
    </main>
    <?= generateCssLinks();  ?>
    <?= generateJsLinks(); ?>
</body>

</html>