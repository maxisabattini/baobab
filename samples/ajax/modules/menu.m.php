<?php
$page = $this->app->getRouteParams()->page;
$class = 'class="active"';
?>
<ul class="nav nav-pills">
  <li <?= $page=="home" ? $class : ''?> >
    <a href="<?=$this->app->getRouteUrl("home"); ?>"  >Home</a>
  </li>
  <li <?= $page=="contact" ? $class : ''?> >
    <a href="<?=$this->app->getRouteUrl("contact"); ?>" >Contact</a>
  </li>  
</ul>