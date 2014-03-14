<!-- App Header -->

<title><?= $title ?></title>
<meta charset="<?=$charset; ?>" />
<meta name="keywords" content="<?=$keywords; ?>" />
<meta name="description" content="<?=$description; ?>" />

<?php
$cssEnq = \baobab\CssQueue::getInstance();
if($packed) {
    $cssEnq->flushPacked();
} else {
    $cssEnq->flush();
}
$cssEnq->clear();
