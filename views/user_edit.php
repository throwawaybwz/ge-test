<?php $inc('header'); ?>

<?php if ($error): ?>
	<h1><span class="label error"><?=$escape($error)?></span></h1>
<?php endif; ?>

<section class="row">
	<form method="post" action="/user/edit?id=<?=(int)$u->id?>" class="two-third" id="registerForm">
		<fieldset>
			<legend>Edit a user</legend>
			<div>
				<input type="text" name="name" placeholder="Name" required="required" value="<?=$escape($u->name)?>" />
				<input type="email" name="email" placeholder="E-Mail" required="required" value="<?=$escape($u->email)?>" />
				<input type="password" name="password" id="pw1" placeholder="Password (8 characters minimum, including 2 numbers)" 
					required="required" pattern="(?=^.{8,}$)(?=(.*\d){2,})^.*" />
				<label>
					<input type="checkbox" name="active" value="1" <?=($u->active ? 'checked' : '')?> />
					<span class="checkable">Active user</span>
				</label>
				<select name="group">
					<?php foreach ($groups as $g): ?>
					<option value="<?=$escape($g->id)?>"><?=$escape($g->name)?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" disabled value="Facebook ID: <?=$escape($u->facebook_id ?: 'No')?>" />
				<input type="text" disabled value="Twitter ID: <?=$escape($u->twitter_id ?: 'No')?>" />
			</div>
			<?=$csrf()?>
			<input type="submit" name="save" class="warning" value="Save" />
		</fieldset>
	</form>
</section>

<?php $inc('footer'); ?>