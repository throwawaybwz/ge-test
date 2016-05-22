<?php

namespace Gladtest;

require __DIR__ . '/../lib/init.php';

// Getting requested URL
$url = parse_url($_SERVER['REQUEST_URI']);
$uri = $url['path'];

$users = new Users;
$user = $users->isLogged();

// Main router
switch ($uri)
{
	case '/facebook/login':
		return Controller\Facebook::login();
	case '/facebook/callback':
		return Controller\Facebook::callback();
	case '/':
		if ($user)
			return Controller\User::getList();
		else
			return Controller\User::login();
	case '/api/login':
		return Controller\API::login();
	case '/api/users/list':
		return Controller\API::usersList();
}

if (!$user || !$user->admin)
{
	return View::render('error', ['msg' => '404 Not Found']);
}

switch ($uri)
{
	case '/user/add':
		return Controller\User::add();
	case '/user/edit':
		return Controller\User::edit(!empty($_GET['id']) ? $_GET['id'] : null);
	case '/user/delete':
		return Controller\User::userDelete(!empty($_GET['id']) ? $_GET['id'] : null);
	default:
		return View::render('error', ['msg' => '404 Not Found']);
}