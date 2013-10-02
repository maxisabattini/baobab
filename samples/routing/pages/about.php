<?php

$app = \baobab\App::getInstance();

$app->render("header");



?>
<h1>About</h1>
<h2>Page scope</h2>
<?php
var_dump($this);

$app->render("footer");
