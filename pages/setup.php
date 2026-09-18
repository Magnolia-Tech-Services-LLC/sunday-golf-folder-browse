<?php

# Same probe as browse.php: the plugin may be symlinked out of persist.
$rs_include = __DIR__ . "/../../../include";
if (!is_file($rs_include . "/boot.php")) {
    $rs_include = __DIR__ . "/../../../../include";
}
include $rs_include . "/boot.php";
include $rs_include . "/authenticate.php";
include_once __DIR__ . "/../include/folder_browse_functions.php";
if (!checkperm("a")) {
    exit($lang["error-permissiondenied"]);
}

$saved = false;
if ((getval("submit", "") != "" || getval("save", "") != "") && enforcePostRequest(false)) {
    $picked = getval("folder_browse_fields", [], false, "is_array");
    folder_browse_save_fields(is_array($picked) ? $picked : []);
    if (getval("submit", "") != "") {
        redirect("pages/team/team_plugins.php");
    }
    $saved = true;
}

$choices = get_resource_type_fields("", "title", "asc", "", [FIELD_TYPE_CATEGORY_TREE], false);
if (!is_array($choices)) {
    $choices = [];
}
$selected = folder_browse_configured_ids();

include $rs_include . "/header.php";
global $baseurl_short;
$links_trail = [
    [
        "title" => $lang["systemsetup"],
        "href" => $baseurl_short . "pages/admin/admin_home.php",
        "menu" => true,
    ],
    [
        "title" => $lang["pluginmanager"],
        "href" => $baseurl_short . "pages/team/team_plugins.php",
    ],
    ["title" => $lang["folder_browse_setup"]],
];
?>
<div class="BasicsBox">
    <h1><?php echo escape($lang["folder_browse_setup"]); ?></h1>
    <?php renderBreadcrumbs($links_trail); ?>
    <p><?php echo escape($lang["folder_browse_setup_intro"]); ?></p>
    <?php if ($saved) { ?>
        <p><?php echo escape($lang["folder_browse_saved"]); ?></p>
    <?php } ?>
    <form method="post" action="<?php echo escape($_SERVER["PHP_SELF"]); ?>">
        <?php generateFormToken("form1"); ?>
        <div class="Question fb-setup">
            <label><?php echo escape($lang["folder_browse_setup_field"]); ?></label>
            <?php if ($choices === []) { ?>
                <p><?php echo escape($lang["folder_browse_nofield"]); ?></p>
            <?php } else { ?>
                <table class="fb-setup-options" cellpadding="2" cellspacing="0">
                    <?php foreach ($choices as $choice) {
                        $id = (int) $choice["ref"];
                        ?>
                        <tr>
                            <td>
                                <input
                                    type="checkbox"
                                    name="folder_browse_fields[]"
                                    value="<?php echo $id; ?>"
                                    id="folder_browse_field_<?php echo $id; ?>"
                                    <?php if (in_array($id, $selected, true)) { ?>checked<?php } ?>
                                >
                            </td>
                            <td>
                                <label for="folder_browse_field_<?php echo $id; ?>"><?php
                                    echo escape(folder_browse_field_label($choice));
                                ?></label>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
            <div class="clearerleft"></div>
        </div>
        <div class="QuestionSubmit">
            <input name="save" type="submit" value="<?php echo escape($lang["save"]); ?>">
        </div>
    </form>
</div>
<?php
include $rs_include . "/footer.php";
