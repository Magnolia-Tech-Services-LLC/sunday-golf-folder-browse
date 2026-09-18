<?php

# The plugin lives on the persist volume and is symlinked into plugins/.
# __DIR__ is the real path, so the include root is one level further out than a stock plugin.
$rs_include = __DIR__ . "/../../../include";
if (!is_file($rs_include . "/boot.php")) {
    $rs_include = __DIR__ . "/../../../../include";
}
include $rs_include . "/boot.php";
include $rs_include . "/authenticate.php";
include_once __DIR__ . "/../include/folder_browse_functions.php";

if (!checkperm("s") || !folder_browse_ready()) {
    include $rs_include . "/header.php";
    $message = checkperm("s") ? $lang["folder_browse_nofield"] : $lang["error-permissiondenied"];
    echo "<div class=\"BasicsBox\"><h1>" . escape($lang["folder_browse"]) . "</h1>";
    echo "<p>" . escape($message) . "</p></div>";
    include $rs_include . "/footer.php";
    exit;
}

$parent = (int) getval("parent", 0);
$field_id = (int) getval("field", 0);
$current = $parent > 0 ? folder_browse_node($parent) : [];
if ($parent > 0 && $current === []) {
    include $rs_include . "/header.php";
    echo "<div class=\"BasicsBox\"><h1>" . escape($lang["folder_browse"]) . "</h1>";
    echo "<p>" . escape($lang["folder_browse_missing"]) . "</p>";
    echo "<p><a href=\"" . escape(folder_browse_page_url()) . "\" onclick=\"return CentralSpaceLoad(this, true);\">";
    echo escape($lang["folder_browse"]) . "</a></p></div>";
    include $rs_include . "/footer.php";
    exit;
}
if ($parent > 0) {
    $field_id = (int) $current["resource_type_field"];
}
$field = $field_id > 0 ? folder_browse_field($field_id) : [];
if ($parent === 0 && $field_id > 0 && $field === []) {
    include $rs_include . "/header.php";
    echo "<div class=\"BasicsBox\"><h1>" . escape($lang["folder_browse"]) . "</h1>";
    echo "<p>" . escape($lang["folder_browse_missing"]) . "</p>";
    echo "<p><a href=\"" . escape(folder_browse_page_url()) . "\" onclick=\"return CentralSpaceLoad(this, true);\">";
    echo escape($lang["folder_browse"]) . "</a></p></div>";
    include $rs_include . "/footer.php";
    exit;
}

$showing_fields = $parent === 0 && $field_id === 0;
if ($showing_fields) {
    $listed = folder_browse_fields();
    $counts = folder_browse_field_counts(array_column($listed, "ref"));
    $rows = [];
    foreach ($listed as $listed_field) {
        $id = (int) $listed_field["ref"];
        $rows[] = [
            "name" => folder_browse_field_label($listed_field),
            "open" => folder_browse_page_url(0, $id),
            "branch" => true,
            "count" => $counts[$id] ?? 0,
            "search" => "",
        ];
    }
} else {
    $children = folder_browse_children($parent, $field_id);
    $child_refs = array_column($children, "ref");
    $branches = array_flip(folder_browse_branch_refs($child_refs, $field_id));
    $count_refs = $child_refs;
    if ($parent > 0) {
        $count_refs[] = $parent;
    }
    $counts = folder_browse_counts($count_refs);
    $rows = [];
    foreach ($children as $child) {
        $ref = (int) $child["ref"];
        $opens = isset($branches[$ref]);
        $rows[] = [
            "name" => folder_browse_label($child),
            "open" => $opens ? folder_browse_page_url($ref) : folder_browse_search_url($ref),
            "branch" => $opens,
            "count" => $counts[$ref] ?? 0,
            "search" => folder_browse_search_url($ref),
        ];
    }
}

$here = $parent > 0 ? ($counts[$parent] ?? 0) : 0;
$child_count = count($rows);
$show_filter = folder_browse_shows_filter($child_count);

$ancestors = [];
foreach (folder_browse_trail($parent) as $step) {
    if ((int) $step["ref"] !== $parent) {
        $ancestors[] = $step;
    }
}
$back = ["label" => $lang["folder_browse"], "url" => folder_browse_page_url()];
if ($ancestors !== []) {
    $up = $ancestors[count($ancestors) - 1];
    $back = [
        "label" => folder_browse_label($up),
        "url" => folder_browse_page_url((int) $up["ref"]),
    ];
} elseif ($parent > 0) {
    $back = [
        "label" => folder_browse_field_label($field),
        "url" => folder_browse_page_url(0, $field_id),
    ];
}

if ($showing_fields) {
    $title = $lang["folder_browse"];
    $summary = folder_browse_count_text("folder_browse_trees_one", "folder_browse_trees", $child_count);
} elseif ($parent > 0) {
    $title = folder_browse_label($current);
    $summary = folder_browse_count_text("folder_browse_subfolders_one", "folder_browse_subfolders", $child_count);
} else {
    $title = folder_browse_field_label($field);
    $summary = folder_browse_count_text("folder_browse_top_one", "folder_browse_top", $child_count);
}

include $rs_include . "/header.php";
?>
<div class="BasicsBox fb">
    <?php if (!$showing_fields) { ?>
        <nav class="fb-path" aria-label="<?php echo escape($lang["folder_browse_path"]); ?>">
            <a href="<?php echo escape(folder_browse_page_url()); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                echo escape($lang["folder_browse"]);
            ?></a>
            <?php if ($parent > 0) { ?>
                <?php echo folder_browse_chevron_icon(); ?>
                <a href="<?php echo escape(folder_browse_page_url(0, $field_id)); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                    echo escape(folder_browse_field_label($field));
                ?></a>
            <?php } ?>
            <?php foreach ($ancestors as $step) { ?>
                <?php echo folder_browse_chevron_icon(); ?>
                <a href="<?php echo escape(folder_browse_page_url((int) $step["ref"])); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                    echo escape(folder_browse_label($step));
                ?></a>
            <?php } ?>
        </nav>
    <?php } ?>

    <div class="fb-head">
        <div>
            <h1><?php echo escape($title); ?></h1>
            <?php if ($rows !== []) { ?>
                <p class="fb-summary"><?php echo escape($summary); ?></p>
            <?php } ?>
        </div>
        <?php if ($parent > 0 && $rows !== []) { ?>
            <a class="Button fb-files" href="<?php echo escape(folder_browse_search_url($parent)); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                echo escape($here > 0 ? folder_browse_files_label($here) : $lang["folder_browse_show_files"]);
            ?></a>
        <?php } ?>
    </div>

    <?php if ($show_filter) { ?>
        <div class="fb-filter">
            <label class="fb-filter-label" for="fb-filter"><?php echo escape($lang["folder_browse_filter"]); ?></label>
            <span class="fb-filter-box">
                <input id="fb-filter" class="fb-filter-input" type="text" autocomplete="off" placeholder="<?php
                    echo escape($lang["folder_browse_filter"]);
                ?>">
                <button type="button" class="fb-filter-clear" hidden aria-label="<?php
                    echo escape($lang["folder_browse_clear"]);
                ?>">&times;</button>
            </span>
            <p class="fb-filter-status" data-idle="<?php echo escape($lang["folder_browse_filter_hint"]); ?>" data-count="<?php
                echo escape($lang["folder_browse_filter_count"]);
            ?>"><?php echo escape($lang["folder_browse_filter_hint"]); ?></p>
            <p class="fb-filter-order" hidden><?php echo escape($lang["folder_browse_filter_order"]); ?></p>
        </div>
    <?php } ?>

    <?php if ($rows === []) { ?>
        <div class="fb-empty">
            <?php echo folder_browse_folder_icon(false); ?>
            <p><?php echo escape(folder_browse_empty_text($here)); ?></p>
            <?php if ($here > 0) { ?>
                <a class="Button" href="<?php echo escape(folder_browse_search_url($parent)); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                    echo escape(folder_browse_files_label($here, $title));
                ?></a>
            <?php } ?>
            <?php if ($parent > 0 || $field_id > 0) { ?>
                <a class="fb-back" href="<?php echo escape($back["url"]); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                    echo escape(str_replace("%name", $back["label"], $lang["folder_browse_back"]));
                ?></a>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="Listview">
        <table class="ListviewStyle">
            <tr class="ListviewTitleStyle">
                <th colspan="3">
                    <span class="fb-line">
                        <span><?php echo escape($lang["folder_browse_col_folder"]); ?></span>
                        <span class="fb-files-col"><?php echo escape($lang["folder_browse_files"]); ?></span>
                        <span></span>
                    </span>
                </th>
            </tr>
            <?php foreach ($rows as $row) { ?>
            <tr class="fb-row" data-name="<?php echo escape($row["name"]); ?>">
                <td colspan="3">
                    <div class="fb-line">
                        <a class="fb-open<?php echo $row["branch"] ? " fb-open--branch" : ""; ?>" href="<?php
                            echo escape($row["open"]);
                        ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                            echo folder_browse_folder_icon($row["branch"]);
                        ?><span class="fb-label"><?php echo escape($row["name"]); ?></span></a>
                        <?php if ($row["search"] !== "") { ?>
                            <a class="fb-count" href="<?php echo escape($row["search"]); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                                echo (int) $row["count"];
                            ?></a>
                        <?php } else { ?>
                            <span class="fb-files-col"><?php echo (int) $row["count"]; ?></span>
                        <?php } ?>
                        <span class="fb-chev"><?php echo $row["branch"] ? folder_browse_chevron_icon() : ""; ?></span>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </table>
        </div>
        <p class="fb-note"><?php
            echo escape($lang["folder_browse_counts_note"]);
            if ($parent === 0 && $field_id > 0) {
                echo " " . escape($lang["folder_browse_order_note"]);
            }
        ?></p>
    <?php } ?>
</div>
<?php if ($show_filter) { ?>
<script>
(function () {
    var root = document.querySelector(".fb");
    var input = document.getElementById("fb-filter");
    if (!root || !input) {
        return;
    }
    var rows = Array.prototype.slice.call(root.querySelectorAll(".fb-row"));
    var status = root.querySelector(".fb-filter-status");
    var order = root.querySelector(".fb-filter-order");
    var clear = root.querySelector(".fb-filter-clear");

    function esc(value) {
        return value.replace(/[&<>"']/g, function (ch) {
            return {"&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#39;"}[ch];
        });
    }

    function paint(label, name, query) {
        if (query === "") {
            label.textContent = name;
            return;
        }
        var at = name.toLowerCase().indexOf(query);
        if (at < 0) {
            label.textContent = name;
            return;
        }
        label.innerHTML = esc(name.slice(0, at))
            + "<mark class=\"fb-hit\">" + esc(name.slice(at, at + query.length)) + "</mark>"
            + esc(name.slice(at + query.length));
    }

    function apply() {
        var query = input.value.trim().toLowerCase();
        var shown = 0;
        rows.forEach(function (row) {
            var name = row.getAttribute("data-name") || "";
            var hit = query === "" || name.toLowerCase().indexOf(query) !== -1;
            row.hidden = !hit;
            if (hit) {
                shown += 1;
            }
            var label = row.querySelector(".fb-label");
            if (label) {
                paint(label, name, hit ? query : "");
            }
        });
        if (status) {
            status.textContent = query === ""
                ? status.getAttribute("data-idle")
                : status.getAttribute("data-count").replace("%shown", String(shown)).replace("%total", String(rows.length));
        }
        if (order) {
            order.hidden = query === "";
        }
        if (clear) {
            clear.hidden = query === "";
        }
    }

    input.addEventListener("input", apply);
    if (clear) {
        clear.addEventListener("click", function () {
            input.value = "";
            apply();
            input.focus();
        });
    }
})();
</script>
<?php } ?>
<?php
include $rs_include . "/footer.php";
