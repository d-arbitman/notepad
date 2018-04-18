<?php
require_once("header.php");
unset($_SESSION['username']);
unset($_SESSION['user_id']);
?>
<div class="text-center" style="padding:50px 0">You have been logged out</div>
<?php
require_once("footer.php");
