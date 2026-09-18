<?php

function HookFolder_browseAllInitialise()
{
    global $custom_top_nav, $baseurl;

    include_once __DIR__ . "/../include/folder_browse_functions.php";
    if (!folder_browse_ready()) {
        return;
    }

    if (!is_array($custom_top_nav ?? null)) {
        $custom_top_nav = [];
    }

    $link = $baseurl . "/plugins/folder_browse/pages/browse.php";
    foreach ($custom_top_nav as $item) {
        if (($item["link"] ?? "") === $link) {
            return;
        }
    }

    $custom_top_nav[] = [
        "title" => "(lang)folder_browse",
        "link" => $link,
    ];
}
