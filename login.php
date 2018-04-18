<?php
require_once("db_settings.php");
session_start();
$error = '';
if (!empty($_REQUEST['username']) && !empty($_REQUEST['password'])) {
    $db->prepare('select id, name, password, password_salt from users where name=$1;');
    $db->execute([$_REQUEST['username']]);
    $row = $db->fetchRow();
    if (password_verify($row[3] . $_REQUEST['password'], $row[2])) {
        $_SESSION['user_id'] = $row[0];
        $_SESSION['username'] = $row[1];
        header("Location: /notepad/dashboard");
        exit;
    } else {
        $error = __("Incorrect username or password", [], "error");
    }
}
require_once("header.php");
?>

<div style="padding:50px 0">
   <fieldset><legend>login</legend>
   <div class="login-form-1">
      <form id="login-form" class="text-left" method="POST">
         <div class="login-form-main-message"><?php if (!empty($error)) {
    print $error;
}?></div>
         <div class="main-login-form">
            <div class="login-group">
               <div class="form-group">
                  <label for="username" class="sr-only">Username</label>
                  <input type="text" class="form-control" id="username" name="username" placeholder="username"<?php print request_value('username'); ?>>
               </div>
               <div class="form-group">
                  <label for="password" class="sr-only">Password</label>
                  <input type="password" class="form-control" id="password" name="password" placeholder="password">
               </div>
            </div>
            <button type="submit" class="login-button btn btn-default"><i class="fa fa-chevron-right"></i> Login</button>
         </div>
         <div class="etc-login-form">
            <p>new user? <a href="register">create new account</a></p>
         </div>
      </form>
   </div>
 </fieldset>
</div>

<?php
require_once("footer.php");
