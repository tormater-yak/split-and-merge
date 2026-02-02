<?php
if (!defined("INDEXED")) exit;

$thread = $db->query("SELECT * FROM threads WHERE threadid='" . $db->real_escape_string($q2) . "'");

if ($thread->num_rows < 1) {
    include getcwd() . "/pages/header.php";
    message($lang["thread.ThreadDoesntExist"]);
    include getcwd() . "/pages/footer.php";
    exit();
}

$threadinfo = $thread->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posts = array();
    $threadid = NULL;
    foreach($_POST as $k => $v) {
        if (str_contains($k,"post_")) {
            array_push($posts,substr($k,5));
        }
    }
    if (!count($posts)) {
        include getcwd() . "/pages/header.php";
        message("No posts were selected to split/merge.");
        include getcwd() . "/pages/footer.php";
        exit();
    }
    if (isset($_POST["otherthread"]) && strlen($_POST["otherthread"])) {
        if (is_numeric($_POST["otherthread"])) {
            $thread = $db->query("SELECT * FROM threads WHERE threadid='" . $db->real_escape_string($_POST["otherthread"]) . "'");
            if (!count($posts)) {
                include getcwd() . "/pages/header.php";
                message($lang["thread.ThreadDoesntExist"]);
                include getcwd() . "/pages/footer.php";
                exit();
            }
            $threadid = $db->real_escape_string($_POST["otherthread"]);            
        }
    }
    else if (isset($_POST["threadtitle"]) && strlen($_POST["threadtitle"])) {
        $threadresult = $db->query("INSERT INTO threads (title, sticky, locked, posts, startuser, starttime, lastpostuser, lastposttime, category, draft) VALUES ('" . $db->real_escape_string(trim($_POST["threadtitle"])) . "', '0', '0', '1', '0', '0', '0', '0', '" . $db->real_escape_string($_POST["category"]) . "', '0')");
        $threadid = $db->insert_id;
    }
    else {
        include getcwd() . "/pages/header.php";
        message("No thread was selected to split/merge to.");
        include getcwd() . "/pages/footer.php";
        exit();
    }
    
    foreach ($posts as $p) {
        $db->query("UPDATE posts SET thread='" . $db->real_escape_string($threadid)  . "' WHERE postid='" . $p . "'");
    }
    
    foreach ([$db->real_escape_string($threadid),$db->real_escape_string($q2)] as $v) {
        $postCheck = $db->query("SELECT * FROM posts WHERE thread='" . $v . "' ORDER BY timestamp ASC");

        if ($postCheck->num_rows == 0) {
            $result = $db->query("DELETE FROM threads WHERE threadid='" . $v . "'");
            continue;
        }
        $lastpost = $db->query("SELECT * FROM posts WHERE thread='" . $v . "' ORDER BY timestamp DESC LIMIT 1");
        if ($lastpost->num_rows == 0) {
            include getcwd() . "/pages/header.php";
            message("The server ran into an issue processing your request.");
            include getcwd() . "/pages/footer.php";
            exit();
        }
        $row = $lastpost->fetch_assoc();
        $first = $postCheck->fetch_assoc();
        $update = $db->query("UPDATE threads SET posts=" . $postCheck->num_rows . ", starttime='" . $first["timestamp"] . "', startuser='" . $first["user"] . "', lastpostuser='" . $row["user"] . "', lastposttime='" . $row["timestamp"] . "' WHERE threadid='" . $v . "'");
    }
    
    redirect("thread/" . $threadid);
    exit();
}

include getcwd() . "/pages/header.php";

echo '<h2>Split and merge threads</h2>';
echo "<script>function selectall(element,yesorno) {
    var children = element.parentNode.children;
    for (var i = 0; i < children.length; i++) {
        if (children[i].type == 'checkbox') children[i].checked = yesorno;
   }
}</script>";

echo "<form action='' method='post'><fieldset><legend>Posts in thread</legend>";

$postcounter = $config["postsPerPage"];
$pagecounter = 0;

$result = $db->query("SELECT * FROM posts WHERE thread='" . $db->real_escape_string($q2) . "' ORDER BY timestamp");

while ($post = $result->fetch_assoc()) {
    if ($postcounter >= $config["postsPerPage"]) {
        $pagecounter++;
        if ($pagecounter != 1) echo "</details>";
        echo "<details class='postspoiler' style='display:block'><summary>Page " . $pagecounter . "</summary>";
        echo "<button type='button' onclick='selectall(this,true)'>Select all on this page</button> <button type='button' onclick='selectall(this,false)'>Deselect all on this page</button><br>";
        $postcounter = 0;
    }
    
    echo "<input type='checkbox' name='post_" . $post["postid"] . "' id='" . $post["postid"] . "'>";
    echo "<label for='" . $post["postid"] . "'><div class='userpost'>" . formatPost($post["content"]) . "</div></label><br>";
    
    $postcounter++;
}
echo "</details></fieldset><br><fieldset><legend>Split/Merge parameters</legend>";

if ($q1 == "merge") {
    echo "<div class='forminput'><label>Other thread id</label><input type='text' name='otherthread'></div>";
}
else {
    echo "<div class='forminput'><label>New thread title</label><input type='text' name='threadtitle'></div>";
    echo "<div class='forminput'><label>New thread category</label><select name='category'>";
$categories = $db->query("SELECT * FROM categories");
while ($row = $categories->fetch_assoc()) {
    echo '<option ';
    if ($threadinfo["category"] == $row["categoryid"]) echo "selected ";
    echo 'value="' . $row['categoryid'] . '">' . htmlspecialchars($row['categoryname']) . '</option>';
}
echo "</select></div>";
}   

echo "</details><input class='buttonbig' type='submit'></fieldset></form>";
include getcwd() . "/pages/footer.php";

?>
