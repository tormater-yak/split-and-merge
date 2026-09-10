<?php
if (!defined("INDEXED")) exit;

function SplitAndMerge_AddToolbarOptions() {
    global $tools;
    $tools["mergethread"] = "Merge";
    $tools["splitthread"] = "Split";
}

function SplitAndMerge_AddStyles() {
    echo "<style>button[name='mergethread'] {margin-left:0.78ch;}</style>";
    // note: 0.78 is used here because the size of the font outside of the container
    // is 75%, but the button font size is 95%, and i would like this space to match
    // with the other space!
}

function SplitAndMerge_AddPages() {
    global $pages;
    $pages["merge"] = "extensions/SplitAndMerge/mergethread.php";
    $pages["split"] = "extensions/SplitAndMerge/mergethread.php";
}
if (!defined("AJAX")) {
    hook("meta", "SplitAndMerge_AddStyles");
    hook("beforeRenderModTools", "SplitAndMerge_AddToolbarOptions");
    if (get_role_permissions() & PERM_EDIT_THREAD) hook("beforePageLoad","SplitAndMerge_AddPages");

    if ($q1 == "thread") {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST["mergethread"])) {
                redirect("merge/" . $q2);
            }
            if (isset($_POST["splitthread"])) {
                redirect("split/" . $q2);
            }
        }
    }
}
?>
