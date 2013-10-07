<html>
<head>

<?php
$enq = \baobab\CssQueue::getInstance();
$enq->addFile("//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css");
?>

<?php $this->app->render("app.header"); ?>
</head>
<body>

<div class="container">
    <h3>404</h3>

    <?php $this->app->render("menu"); ?>

    <p class="lead">
    Page not found
    </p>
</div>

<?php $this->app->render("app.footer"); ?>
</body>
<html>
