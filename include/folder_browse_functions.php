<?php

function folder_browse_configured_ids(): array
{
    global $folder_browse_fields, $folder_browse_field;

    if (isset($folder_browse_fields) && is_array($folder_browse_fields)) {
        $raw = $folder_browse_fields;
    } else {
        $raw = [$folder_browse_field ?? 0];
    }

    $ids = [];
    foreach ($raw as $id) {
        $id = (int) $id;
        if ($id > 0 && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }

    return $ids;
}

function folder_browse_is_tree(array $field): bool
{
    return (int) ($field["type"] ?? 0) === FIELD_TYPE_CATEGORY_TREE;
}

function folder_browse_fields(): array
{
    $fields = [];
    foreach (folder_browse_configured_ids() as $id) {
        $field = get_resource_type_field($id);
        if (is_array($field) && folder_browse_is_tree($field)) {
            $fields[] = $field;
        }
    }

    return $fields;
}

function folder_browse_field(int $ref): array
{
    foreach (folder_browse_fields() as $field) {
        if ((int) $field["ref"] === $ref) {
            return $field;
        }
    }

    return [];
}

function folder_browse_ready(): bool
{
    return folder_browse_fields() !== [];
}

function folder_browse_field_label(array $field): string
{
    $title = trim((string) ($field["title"] ?? ""));
    if ($title === "") {
        $title = (string) ($field["name"] ?? "");
    }

    return i18n_get_translated($title);
}

function folder_browse_node(int $ref): array
{
    $node = [];
    if ($ref < 1 || !get_node($ref, $node)) {
        return [];
    }
    if (folder_browse_field((int) $node["resource_type_field"]) === []) {
        return [];
    }

    return $node;
}

function folder_browse_children(int $parent, int $field): array
{
    if (folder_browse_field($field) === []) {
        return [];
    }
    $nodes = $parent > 0
        ? get_nodes($field, $parent, false)
        : get_nodes($field, null, false);

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

function folder_browse_branch_refs(array $refs, int $field): array
{
    $refs = array_values(array_filter(array_map("intval", $refs)));
    if ($refs === [] || folder_browse_field($field) === []) {
        return [];
    }

    $rows = ps_query(
        "SELECT DISTINCT parent AS parent
            FROM node
            WHERE resource_type_field = ?
            AND parent IN (" . ps_param_insert(count($refs)) . ")",
        array_merge(["i", $field], ps_param_fill($refs, "i"))
    );

    return array_map("intval", array_column($rows, "parent"));
}

function folder_browse_field_counts(array $ids): array
{
    $ids = array_values(array_filter(array_map("intval", $ids)));
    if ($ids === []) {
        return [];
    }

    $rows = ps_query(
        "SELECT n.resource_type_field AS field_id, COUNT(DISTINCT rn.resource) AS total
            FROM node n
            JOIN resource_node rn ON rn.node = n.ref
            WHERE n.resource_type_field IN (" . ps_param_insert(count($ids)) . ")
            GROUP BY n.resource_type_field",
        ps_param_fill($ids, "i")
    );
    $counts = [];
    foreach ($rows as $row) {
        $counts[(int) $row["field_id"]] = (int) $row["total"];
    }

    return $counts;
}

function folder_browse_trail(int $ref): array
{
    if ($ref < 1) {
        return [];
    }

    $rows = get_parent_nodes($ref, true, true);

    return is_array($rows) ? array_reverse($rows) : [];
}

function folder_browse_page_url(int $parent = 0, int $field = 0): string
{
    global $baseurl;

    $url = $baseurl . "/plugins/folder_browse/pages/browse.php";
    if ($parent > 0) {
        return generateURL($url, ["parent" => $parent]);
    }
    if ($field > 0) {
        return generateURL($url, ["field" => $field]);
    }

    return $url;
}

function folder_browse_search_text(int $ref): string
{
    $node = folder_browse_node($ref);
    if ($node === []) {
        return "";
    }
    $field = folder_browse_field((int) $node["resource_type_field"]);
    $short = (string) ($field["name"] ?? "");
    $parts = [];
    foreach (folder_browse_trail($ref) as $step) {
        $parts[] = folder_browse_label($step);
    }
    if ($short === "" || $parts === []) {
        return "";
    }

    $text = $short . ":" . implode("/", $parts);
    if (preg_match('/[\s,"]/', $text) === 1) {
        return '"' . str_replace('"', "", $text) . '"';
    }

    return $text;
}

function folder_browse_search_url(int $ref): string
{
    global $baseurl;

    $text = folder_browse_search_text($ref);
    if ($text === "") {
        return folder_browse_page_url($ref);
    }

    return generateURL($baseurl . "/pages/search.php", ["search" => $text]);
}

function folder_browse_field_by_name(string $name): array
{
    $name = mb_strtolower($name);
    foreach (folder_browse_fields() as $field) {
        if (mb_strtolower((string) $field["name"]) === $name) {
            return $field;
        }
    }

    return [];
}

function folder_browse_child_named(int $field, ?int $parent, string $name): int
{
    $want = mb_strtolower($name);
    foreach (folder_browse_children($parent ?? 0, $field) as $child) {
        $label = mb_strtolower(folder_browse_label($child));
        $raw = mb_strtolower((string) ($child["name"] ?? ""));
        if ($label === $want || $raw === $want) {
            return (int) $child["ref"];
        }
    }

    return 0;
}

function folder_browse_node_from_search(string $search): int
{
    $search = trim($search);
    if (strlen($search) > 1 && $search[0] === '"' && str_ends_with($search, '"')) {
        $search = substr($search, 1, -1);
    }
    $split = explode(":", $search, 2);
    if (count($split) !== 2 || $split[0] === "" || $split[1] === "") {
        return 0;
    }
    $field = folder_browse_field_by_name($split[0]);
    if ($field === []) {
        return 0;
    }

    $parent = null;
    $found = 0;
    foreach (explode("/", $split[1]) as $part) {
        if ($part === "") {
            return 0;
        }
        $found = folder_browse_child_named((int) $field["ref"], $parent, $part);
        if ($found < 1) {
            return 0;
        }
        $parent = $found;
    }

    return $found;
}

function folder_browse_save_fields(array $ids): void
{
    $valid = [];
    foreach ($ids as $id) {
        $field = get_resource_type_field((int) $id);
        if (is_array($field) && folder_browse_is_tree($field)) {
            $valid[] = (int) $field["ref"];
        }
    }
    $valid = array_values(array_unique($valid));
    $config = get_plugin_config("folder_browse");
    if (!is_array($config)) {
        $config = [];
    }
    $config["folder_browse_fields"] = $valid;
    unset($config["folder_browse_field"]);
    set_plugin_config("folder_browse", $config);
    $GLOBALS["folder_browse_fields"] = $valid;
    unset($GLOBALS["folder_browse_field"]);
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

