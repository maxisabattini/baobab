<!-- App Header -->
    <title><?= $title ?></title>
    <meta charset="<?=$charset; ?>" />
	<meta name="keywords" content="<?=$keywords; ?>" />
	<meta name="description" content="<?=$description; ?>" />

<?php
$enq = \baobab\CssQueue::getInstance();
$enq->flushPacked();
