<?php $inc('header'); ?>

<?php if ($error): ?>
	<h1><span class="label error"><?=$escape($error)?></span></h1>
<?php endif; ?>

<section class="row">
	<form method="post" action="/" class="two-third" id="registerForm">
		<fieldset>
			<legend>Register a new account</legend>
			<div>
				<input type="text" name="name" placeholder="Name" required="required" />
				<input type="email" name="email" placeholder="E-Mail" required="required" />
				<input type="password" name="password" id="pw1" placeholder="Password (8 characters minimum, including 2 numbers)" 
					required="required" pattern="(?=^.{8,}$)(?=(.*\d){2,})^.*" />
				<input type="password" id="pw2" placeholder="Password verification" 
					required="required" pattern="(?=^.{8,}$)(?=(.*\d){2,})^.*" />
			</div>
			<?=$csrf()?>
			<input type="submit" name="register" class="warning" value="Register" />
		</fieldset>
	</form>
	<form method="post" action="/">
		<fieldset>
			<legend>Login</legend>
			<div>
				<input type="email" name="email" placeholder="E-Mail" required="required" />
				<input type="password" name="password" id="pw1" placeholder="Password (min. 8 characters, incl. 2 numbers)" 
					required="required" pattern="(?=^.{8,}$)(?=(.*\d){2,})^.*" />
			</div>
			<?=$csrf()?>
			<input type="submit" name="login" class="button" value="Login" />
		</fieldset>
	</form>
</section>

<script type="text/javascript">
(function () {
	document.getElementById('registerForm').onsubmit = function () {
		var pw1 = document.getElementById('pw1');
		var pw2 = document.getElementById('pw2');
		
		if (pw1.value != pw2.value)
		{
			pw2.setCustomValidity('Password verification is not matching');
			return false;
		}
	};
})();
</script>

<?php $inc('footer'); ?>