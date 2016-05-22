<?php $inc('header'); ?>

<?php if ($error): ?>
	<h1><span class="label error"><?=$escape($error)?></span></h1>
<?php endif; ?>

<section class="row">
	<form method="post" action="/user/delete?id=<?=(int)$u->id?>">
		<fieldset>
			<legend>Delete a user?</legend>
			<?=$csrf()?>
			<input type="submit" name="delete" class="error" value="Delete this user" />
		</fieldset>
	</form>
</section>

<?php $inc('footer'); ?>