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

function HookFolder_browseAllSearchstringprocessing()
{
    global $search;

    include_once __DIR__ . "/../include/folder_browse_functions.php";
    if (!is_string($search) || !folder_browse_ready()) {
        return;
    }
    $ref = folder_browse_node_from_search($search);
    if ($ref < 1) {
        return;
    }
    # Keep the path in the box. Search by node id so a repeated leaf name
    # cannot match a different folder.
    $GLOBALS["folder_browse_display_search"] = $search;
    $search = NODE_TOKEN_PREFIX . $ref;
}

function folder_browse_restore_display_search(): void
{
    global $search, $searchparams;

    if (!isset($GLOBALS["folder_browse_display_search"])) {
        return;
    }
    $search = $GLOBALS["folder_browse_display_search"];
    if (isset($searchparams) && is_array($searchparams)) {
        $searchparams["search"] = $search;
    }
}

function HookFolder_browseAllAdd_search_title_links()
{
    # After do_search, before the header. Restoring earlier would make a
    # second do_search on the same request use the path instead of the node id.
    folder_browse_restore_display_search();
}

function HookFolder_browseAllHeadertop()
{
    folder_browse_restore_display_search();
}
