<?php

# Same probe as browse.php: the plugin may be symlinked out of persist.
$rs_include = __DIR__ . "/../../../include";
if (!is_file($rs_include . "/boot.php")) {
    $rs_include = __DIR__ . "/../../../../include";
}
include $rs_include . "/boot.php";
include $rs_include . "/authenticate.php";
if (!checkperm("a")) {
    exit($lang["error-permissiondenied"]);
}

$plugin_name = "folder_browse";
$page_heading = $lang["folder_browse_setup"];
$page_intro = $lang["folder_browse_setup_intro"];
$page_def = [];
$page_def[] = config_add_single_ftype_select(
    "folder_browse_field",
    $lang["folder_browse_setup_field"],
    300,
    false,
    [FIELD_TYPE_CATEGORY_TREE]
);

config_gen_setup_post($page_def, $plugin_name);
include $rs_include . "/header.php";
config_gen_setup_html($page_def, $plugin_name, null, $page_heading, $page_intro);
include $rs_include . "/footer.php";
