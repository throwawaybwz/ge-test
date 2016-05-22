<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="/picnic.css" />
	<title>Test</title>
	<style type="text/css">
	main {
		margin-top: 3em;
		padding: 1em;
	}
	form input {
	    display: block;
	    margin: 0.6em auto;
	}
	</style>
</head>
<body>

<nav>
	<a class="brand" href="/"><span>Test app</span></a>
	<div class="menu">
		<?php if (!empty($user)): ?>
			<?php if ($user->admin): ?>
				<a class="button" href="/user/add">Add user</a>
			<?php endif; ?>
			<a class="button" href="/">List users</a>
		<?php else: ?>
			<a class="button" href="/">Login</a>
			<a class="pseudo button" href="/facebook/login">Login with Facebook</a>
		<?php endif; ?>
	</div>
</nav>

<main>