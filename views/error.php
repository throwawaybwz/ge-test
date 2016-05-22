<?php $inc('header'); ?>

<?php if ($msg): ?>
	<h1><span class="label error"><?=$escape($msg)?></span></h1>
<?php endif; ?>

<?php $inc('footer'); ?>