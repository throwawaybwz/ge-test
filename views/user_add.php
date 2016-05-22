<?php $inc('header'); ?>

<?php if ($error): ?>
	<h1><span class="label error"><?=$escape($error)?></span></h1>
<?php endif; ?>

<section class="row">
	<form method="post" action="/user/add" class="two-third" id="registerForm">
		<fieldset>
			<legend>Add a user</legend>
			<div>
				<input type="text" name="name" placeholder="Name" required="required" />
				<input type="email" name="email" placeholder="E-Mail" required="required" />
				<input type="password" name="password" id="pw1" placeholder="Password (8 characters minimum, including 2 numbers)" 
					required="required" pattern="(?=^.{8,}$)(?=(.*\d){2,})^.*" />
				<select name="group">
					<?php foreach ($groups as $g): ?>
					<option value="<?=$escape($g->id)?>"><?=$escape($g->name)?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?=$csrf()?>
			<input type="submit" name="save" class="warning" value="Add" />
		</fieldset>
	</form>
</section>


<?php $inc('footer'); ?>