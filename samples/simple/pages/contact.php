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
    <h3>Contact</h3>

    <?php $this->app->render("menu"); ?>

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

<?php
$enq = \baobab\JSQueue::getInstance();
$enq->addFile("http://code.jquery.com/jquery-1.10.0.js", "jquery");
$enq->addFile("http://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.js", "", array("jquery"));

$enq->beginCode();
?>
<script>
    jQuery(document).ready(function(){
        alert("Contact Page loaded");
    });
</script>
<?php
$enq->endCode(null, array("jquery"));
?>
<?php $this->app->render("app.footer"); ?>
</body>
<html>
