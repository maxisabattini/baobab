<?php
global $app;

$page = $app->getInfo("page", "page");
$class = 'class="active"';

?>
<ul class="nav nav-pills">
  <li <?= $page=="home" ? $class : ''?> >
    <a href="<?=$app->getPageUrl("home"); ?>"  >Home</a>
  </li>
  <li <?= $page=="contact" ? $class : ''?> >
    <a href="<?=$app->getPageUrl("contact"); ?>" >Contact</a>
  </li>  
</ul>