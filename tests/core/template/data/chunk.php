<?php
use rock\template\Template;
/** @var Template $this */
?>
Test <?=$this->getChunk('@rockunit.tpl\subchunk')?>

<?=$this->getPlaceholder('text')?>

[[+escape]]
<?=$this->getPlaceholder('hi')?>, <?=$this->getPlaceholder('world')?>!!!
<?=$this->getPlaceholder(['foo', 'bar'], false, true)?>

<?=$this->{'baz.bar'}?>