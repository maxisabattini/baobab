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
    <?php
	//$this->app->render($page, $this->getVars(), false);
        $this->render($page, false, false);
    ?>
</div>

<?php $this->app->render("app.footer"); ?>
</body>
<html>
