<?php
require_once("functions.php");

$domain = removeSubdomains((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']);

$defaultConfig = [
    'site_name' => $domain,
    'pages_dir' => 'post-md',
    'pages_dir_uri' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']."/post-md",
    'page_ext' => true,
    'page_info' => 'init.md',
    'page_body' => 'post.md',
    'page_uri' => '/pages.php?p=$id',
    'page_excerpt_count' => 128,
    'templates_dir' => 'template-md',
    'site_uri' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'],
    "copyright" => '© $year ' . $domain,
    "error_show" => 0,
    "page_excerpt_ellipsis" => "…",
    "date_format" => "Y/m/d",
    "mirror_message" => 0,
    "site_page_title" => '$title - $site_name',
    "footer_version" => 0
];

global $config;
if (!isset($config)) {
    $config = loadConfig();
}

$defaultPlaceholders = [
    '$site_name' => htmlspecialchars($config['site_name']),
    '$copyright' => htmlspecialchars($config['copyright']),
    '$year' => date("Y"),
    '$month' => date("m"),
    '$date' => date("d"),
    '$fulldate' => date($config['date_format']),
    '$time' => date($config['time_format']),
    '$helloworld_post_link' => str_replace('$id', "hello-world", htmlspecialchars($config["page_uri"])),
    '$version' => getVersionInfo()["version"],
    '$release_date' => date($config['date_format'],strtotime(getVersionInfo()["releaseDate"])),
];
