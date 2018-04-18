<?php

require_once("header.php");

$errors = [];

if (!empty($_REQUEST['action']) && $_REQUEST['action']=="register") {
		if (!empty($_REQUEST['username']) && !empty($_REQUEST['email']) && !empty($_REQUEST['password']) && $_REQUEST['password']==$_REQUEST['password_confirm']) {
		$pass_salt = create_random_password(10);
    $pass_hash = password_hash($pass_salt . $_REQUEST['password'], PASSWORD_DEFAULT);
		$db->prepare('INSERT INTO users (name, email, password, password_salt) values ($1, $2, $3, $4)');
		$db->execute([$_REQUEST['username'], $_REQUEST['email'], $pass_hash, $pass_salt]);
		print __("You have been registered", [], "confirmation");
	} else {
		if (!checkRequestParameters(['username'=>'alphanumeric'])) {
		  $errors[] = "User name must be alphanumeric without special characters or spaces";
		}
		if (empty($_REQUEST['email'])) {
			$errors[] = 'Email';
		}
		if (empty($_REQUEST['password'])) {
			$errors[] = 'Password';
		}
		if (empty($_REQUEST['password_confirm']) || $_REQUEST['password']!=$_REQUEST['password_confirm']) {
			$errors[] = 'Password confirmation';
		}
	}
}

if (empty($_REQUEST['action']) || !empty($errors)) {

?>
<div style="padding:50px 0;margin:auto auto;">
   <fieldset><legend>register</legend>
   <div class="login-form-1">
	 <form id="register-form" class="text-left" method="POST" action="<?php print strtolower($req); ?>">
       <input type="hidden" name="action" value="register">
         <div class="login-form-main-message"><?php if (!empty($errors)) {print __("All fields are required", [], "error");} ?></div>
         <div class="main-login-form">
            <div class="login-group">
               <div class="form-group">
                  <label for="username" class="required">Email address</label>
                  <input type="text" class="form-control" id="username" name="username" placeholder="username"<?php print request_value('username'); ?>>
               </div>
               <div class="form-group">
                  <label for="password" class="required">Password</label>
                  <input type="password" class="form-control" id="password" name="password" placeholder="password">
               </div>
               <div class="form-group">
                  <label for="password_confirm" class="required">Password Confirm</label>
                  <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="confirm password">
               </div>
               <div class="form-group">
                  <label for="email" class="required">Email</label>
                  <input type="text" class="form-control" id="email" name="email" placeholder="email"<?php print request_value('email');?>>
               </div>
            </div>
            <button type="submit" class="btn btn-default"><i class="fa fa-chevron-right"></i> Register</button>
         </div>
         <div class="etc-login-form">
            <p>already have an account? <a href="login">login here</a></p>
         </div>
      </form>
	 </div>
   </fieldset>
</div>

<?php
}

require_once("footer.php");
