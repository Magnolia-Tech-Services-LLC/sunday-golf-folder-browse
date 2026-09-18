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

$children = folder_browse_children($parent);
$branches = array_flip(folder_browse_branch_refs(array_column($children, "ref")));
$trail = folder_browse_trail($parent);

include $rs_include . "/header.php";
?>
<div class="BasicsBox">
    <h1><?php echo escape($lang["folder_browse"]); ?></h1>
    <p>
        <a href="<?php echo escape(folder_browse_page_url()); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
            echo escape($lang["folder_browse"]);
        ?></a>
        <?php foreach ($trail as $step) {
            $ref = (int) $step["ref"];
            echo " / ";
            if ($ref === $parent) {
                echo escape(folder_browse_label($step));
                continue;
            }
            echo "<a href=\"" . escape(folder_browse_page_url($ref)) . "\" onclick=\"return CentralSpaceLoad(this, true);\">";
            echo escape(folder_browse_label($step)) . "</a>";
        } ?>
    </p>
    <?php if ($parent > 0) { ?>
        <p><a href="<?php echo escape(folder_browse_search_url($parent)); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
            echo escape($lang["folder_browse_files_here"]);
        ?></a></p>
    <?php } ?>
    <?php if ($children === []) { ?>
        <p><?php echo escape($lang["folder_browse_empty"]); ?></p>
    <?php } else { ?>
        <div class="Listview">
        <table class="ListviewStyle">
        <?php foreach ($children as $child) {
            $ref = (int) $child["ref"];
            $opens_folder = isset($branches[$ref]);
            $href = $opens_folder ? folder_browse_page_url($ref) : folder_browse_search_url($ref);
            ?>
            <tr>
                <td><a href="<?php echo escape($href); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                    echo escape(folder_browse_label($child));
                ?></a></td>
                <td><a href="<?php echo escape(folder_browse_search_url($ref)); ?>" onclick="return CentralSpaceLoad(this, true);"><?php
                    echo escape($lang["folder_browse_files"]);
                ?></a></td>
            </tr>
            <?php
        } ?>
        </table>
        </div>
    <?php } ?>
</div>
<?php
include $rs_include . "/footer.php";
