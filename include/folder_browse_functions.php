<?php

function folder_browse_field(): int
{
    global $folder_browse_field;

    return (int) $folder_browse_field;
}

function folder_browse_ready(): bool
{
    $field = get_resource_type_field(folder_browse_field());

    return is_array($field) && (int) $field["type"] === FIELD_TYPE_CATEGORY_TREE;
}

function folder_browse_node(int $ref): array
{
    $node = [];
    if ($ref < 1 || !get_node($ref, $node)) {
        return [];
    }
    if ((int) $node["resource_type_field"] !== folder_browse_field()) {
        return [];
    }

    return $node;
}

function folder_browse_children(int $parent): array
{
    $nodes = $parent > 0
        ? get_nodes(folder_browse_field(), $parent, false)
        : get_nodes(folder_browse_field(), null, false);

    return is_array($nodes) ? $nodes : [];
}

function folder_browse_branch_refs(array $refs): array
{
    $refs = array_values(array_filter(array_map("intval", $refs)));
    if ($refs === []) {
        return [];
    }

    $rows = ps_query(
        "SELECT DISTINCT parent AS parent
            FROM node
            WHERE resource_type_field = ?
            AND parent IN (" . ps_param_insert(count($refs)) . ")",
        array_merge(["i", folder_browse_field()], ps_param_fill($refs, "i"))
    );

    return array_map("intval", array_column($rows, "parent"));
}

function folder_browse_trail(int $ref): array
{
    if ($ref < 1) {
        return [];
    }

    $rows = get_parent_nodes($ref, true, true);

    return is_array($rows) ? array_reverse($rows) : [];
}

function folder_browse_page_url(int $parent = 0): string
{
    global $baseurl;

    $url = $baseurl . "/plugins/folder_browse/pages/browse.php";

    return $parent > 0 ? generateURL($url, ["parent" => $parent]) : $url;
}

function folder_browse_search_url(int $ref): string
{
    global $baseurl;

    return generateURL(
        $baseurl . "/pages/search.php",
        ["search" => NODE_TOKEN_PREFIX . $ref]
    );
}

function folder_browse_label(array $node): string
{
    $name = $node["translated_name"] ?? $node["name"] ?? "";

    return i18n_get_translated((string) $name);
}
