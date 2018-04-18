<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once("functions.php");
//$req = (!empty($_SERVER['REQUEST_URI']))?substr($_SERVER['REQUEST_URI'], strlen('/notepad/'), (strpos($_SERVER['REQUEST_URI'], "?")!==false)?strpos($_SERVER['REQUEST_URI'], "?")-strlen('/notepad/'):strlen($_SERVER['REQUEST_URI'])):"";
$req = '';
if (!empty($_SERVER['REQUEST_URI'])) {
  $req = getFilenameWithoutExtension($_SERVER['REQUEST_URI']);
	$end = strpos($req, "?");
	if ($end !== false) {
    $req = substr($req, 0, $end);
	}
	//$req = ucfirst(str_replace('_', ' ', $req));
	$req = str_replace("_", " ", $req);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
  <script src="//code.jquery.com/jquery-3.3.1.min.js"></script>
  <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>

	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
  <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
  <link href="/notepad/styles/main.min.css" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <title>Notepad</title>
</head>
<body>

<div class="container-fluid">
  <div class="header">
    <h2>notepad</h2>
  </div>
  <nav aria-label="breadcrumb" style="margin-top:-4em;opacity:0.7;width:85%;margin-left:auto;margin-right:auto;">
  <ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="/notepad">home</a></li>
		<?php if ($req != "") { ?>
		<li class="breadcrumb-item active" aria-current="page"><?php print $req;?></li>
    <?php } ?>
  </ol>
  </nav>
  <div class="row">
      <div class="col-md-1"></div>
      <div class="col-md-10" style="text-align:right;">
      <?php
        if (!empty($_SESSION['user_id']) && $_SERVER['REQUEST_URI']!='/notepad/logout') {
          print 'Hi ' . $_SESSION['username'] . ' | <a href="logout">logout</a>';
        } else {
        	print '<a href="register">register</a> | <a href="login">login</a>';
        }
            ?></div>
      <div class="col-md-1"></div>
  </div>

  <div class="row">
      <div class="col-md-3"></div>
			<div class="col-md-6">
