<?php
global $app;

$page = $app->info("page", "page");
$class = 'class="active"';

?>
<ul class="nav nav-pills">
  <li <?= $page=="home" ? $class : ''?> >
    <a href="<?=$app->pageUrl("home"); ?>"  >Home</a>
  </li>
  <li <?= $page=="contact" ? $class : ''?> >
    <a href="<?=$app->pageUrl("contact"); ?>" >Contact</a>
  </li>  
</ul>