<?php
require_once("header.php");
$new = false;

if (empty($_SESSION['user_id']) || empty($_SESSION["username"])) {
    print __("You need to <a href='login'>log in</a> to access this page", [], "error");
    require_once("footer.php");
    exit;
} else {
    if (!empty($_REQUEST['action']) && $_REQUEST['action']=='Save') {
        if (!empty($_REQUEST['created'])) {
            $db->prepare('INSERT INTO notes (id, owner_id, title, note, created, modified) values ($1, $2, $3, $4, $5, $5)');
            $db->execute([$_REQUEST['id'], $_SESSION['user_id'], $_REQUEST['title'], $_REQUEST['note'], $_REQUEST['created']]);
        } else {
            $db->prepare('UPDATE notes set note=$1, modified=$2, title=$3 where owner_id=$4 and id=$5');
            $db->execute([$_REQUEST['note'], date('Y-m-d h:i:s'), $_REQUEST['title'], $_SESSION['user_id'], $_REQUEST['id']]);
        }
        print __('Your note has been saved<br>', [], "center");
        listNotes($db);
    } elseif (!empty($_SERVER['QUERY_STRING'])) {
        if ($_SERVER['QUERY_STRING']=="_new") {
            $new = true;
            $note = [date('Y-m-d h:i:s'), '', ''];
        } else {
          $db->prepare('SELECT created, note, title FROM notes WHERE lower(id::text)=lower($1) AND owner_id::text=$2');
          $db->execute([$_SERVER['QUERY_STRING'], $_SESSION['user_id']]);

          $note = $db->fetchRow();
          if (!$note) {
            print __ ("I could not find that note", [], "error");
            listNotes($db);
          }
        }
        $noteID = '';
        if ($_SERVER['QUERY_STRING']=="_new") {
            $noteID = hash('sha256', $_SESSION['username'] . '-' . $note[0]);
        } else {
            $noteID = $_SERVER['QUERY_STRING'];
				} ?>
     <div class="right"><a href="dashboard">dashboard</a></div>
     <fieldset><legend>note</legend>
		 <div class="login-form-1">
     <form id="note-form" class="text-left" method="POST" action="<?php print strtolower($req); ?>">
       <?php if ($new) {
            ?><input type="hidden" name="created" value="<?php print $note[0]; ?>"><?php
        }
        print '<input type="hidden" name="id" value="' . $noteID . '">'; ?>
				<label for="created">Created</label><br>
        <?php print $note[0]; ?><br><br>
        <label for="title">Title</label><br>
				<input type="text" name="title" id="title" value="<?php print htmlentities($note[2]); ?>"><br><br>
       <label for="note">Note</label><br>
       <textarea id="note" name="note" class="edit-note"><?php print htmlentities($note[1]); ?></textarea><br><br>
       <button type="submit" name="action" id="action" value="Save" class="save-button btn btn-default"><i class="fa fa-chevron-right"></i> Save</button>
       </form>
     </div>
	 </fieldset>
<?php require_once("footer.php");
    } else {
        listNotes($db);
    }
}

function listNotes(&$db)
{
    $db->prepare('SELECT lower(id), created, modified, title FROM notes WHERE owner_id::text=$1;');
    $db->execute([$_SESSION['user_id']]);
    $c = 0;
    print __('Create a <a href="?_new">new note</a>' . "\n", [], "right");
    print "<ul>\n";
    while ($note = $db->fetchRow()) {
        print '<li><a href="?' . $note[0] . '">' . htmlentities($note[3]) . '</a><ul><li>created on ' . $note[1] . ' last modified on ' . $note[2] . "</li></ul></li>\n";
        $c++;
    }
    print "</ul>\n";
    if ($c==0) {
        print __("You have not <a href=\"?_new\">created</a> any notes yet", []);
    }
    require_once("footer.php");
    exit;
}
