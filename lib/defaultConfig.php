<?php
$defaultConfig = [
    'site_name' => 'MarkMan',
    'pages_dir' => 'post-md',
    'page_ext' => true,
    'page_info' => 'init.md',
    'page_body' => 'post.md',
    'page_uri' => '/pages.php?p=$id',
    'page_excerpt_count' => 128,
    'templates_dir' => 'template-md',
    'site_uri' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'],
    "copyright" => '© $year ' . $_SERVER['HTTP_HOST'] . '.',
    "error_show" => 0,
    "page_excerpt_ellipsis" => "…"
];
