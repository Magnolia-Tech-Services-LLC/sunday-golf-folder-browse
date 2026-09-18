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

function folder_browse_counts(array $refs): array
{
    $refs = array_values(array_filter(array_map("intval", $refs)));
    if ($refs === []) {
        return [];
    }

    $rows = ps_query(
        "SELECT node, COUNT(*) AS total
            FROM resource_node
            WHERE node IN (" . ps_param_insert(count($refs)) . ")
            GROUP BY node",
        ps_param_fill($refs, "i")
    );
    $counts = [];
    foreach ($rows as $row) {
        $counts[(int) $row["node"]] = (int) $row["total"];
    }

    return $counts;
}

function folder_browse_shows_filter(int $child_count): bool
{
    return $child_count > 12;
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

function folder_browse_count_text(string $one_key, string $many_key, int $n): string
{
    global $lang;

    $key = $n === 1 ? $one_key : $many_key;

    return str_replace("%n", (string) $n, $lang[$key]);
}

function folder_browse_files_label(int $n, string $name = ""): string
{
    global $lang;

    if ($name === "") {
        $key = $n === 1 ? "folder_browse_show_one" : "folder_browse_show_all";

        return str_replace("%n", (string) $n, $lang[$key]);
    }
    $key = $n === 1 ? "folder_browse_show_named_one" : "folder_browse_show_named";

    return str_replace(["%n", "%name"], [(string) $n, $name], $lang[$key]);
}

function folder_browse_empty_text(int $n): string
{
    global $lang;

    if ($n < 1) {
        return $lang["folder_browse_empty_none"];
    }
    $key = $n === 1 ? "folder_browse_empty_one" : "folder_browse_empty_many";

    return str_replace("%n", (string) $n, $lang[$key]);
}

function folder_browse_folder_icon(bool $branch): string
{
    $class = $branch ? "fb-icon fb-icon--branch" : "fb-icon fb-icon--leaf";

    return '<svg class="' . $class . '" viewBox="0 0 24 24" aria-hidden="true">'
        . '<path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h3.9a1.5 1.5 0 0 1 1.06.44L10.9 7.9H19.5A1.5 1.5 0 0 1 21 9.4v8.1a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5z"></path>'
        . '</svg>';
}

function folder_browse_chevron_icon(): string
{
    return '<svg class="fb-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>';
}

