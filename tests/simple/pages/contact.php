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
    <h3>Contact</h3>

    <?php $app->render("menu"); ?>

    <div class="span6">
        <form>
            <div class="controls controls-row">
                <input id="name" name="name" type="text" class="span3" placeholder="Name">
                <input id="email" name="email" type="email" class="span3" placeholder="Email address">
            </div>
            <div class="controls">
                <textarea id="message" name="message" class="span6" placeholder="Your Message" rows="5"></textarea>
            </div>

            <div class="controls">
                <button id="contact-submit" type="submit" class="btn btn-primary input-medium pull-right">Send</button>
            </div>
        </form>
    </div>
</div>

<?php $app->render("app.footer"); ?>
</body>
<html>