<?php
global $app;
?>
<html>
<head>

<?php
$enq = \baobab\CssEnqueuer::getInstance();
$enq->addFile("//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css");
?>

<?php $app->render("app.header"); ?>
</head>
<body>

<div class="container">
    <h3>Home</h3>

    <?php $app->render("menu"); ?>

    <p class="lead">
    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
    </p>
</div>

<?php $app->render("app.footer"); ?>
</body>
<html>