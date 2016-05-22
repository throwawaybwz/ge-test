<?php

namespace Gladtest\Controller;

use Gladtest\View;
use Gladtest\Users;
use Gladtest\User_Exception;

class User
{
	static public function login()
	{
		$error = false;
		$users = new Users;

		if ((!empty($_POST['register']) || !empty($_POST['login']))
			&& !View::checkCSRF())
		{
			$error = 'CSRF error, please re-submit the form';
		}
		else if (!empty($_POST['register']))
		{
			try {
				$users->register(
					$_POST['name'] ?: '',
					$_POST['email'] ?: '',
					$_POST['password'] ?: ''
				);

				$users->login($_POST['email'], $_POST['password']);

				header('Location: /');
				exit;
			}
			catch (User_Exception $e) {
				$error = $e->getMessage();
			}
		}
		else if (!empty($_POST['login']))
		{
			try {
				if ($users->login(
					$_POST['email'] ?: '',
					$_POST['password'] ?: ''
					))
				{
					header('Location: /');
					exit;
				}
				else
				{
					$error = 'Invalid credentials';
				}
			}
			catch (User_Exception $e) {
				$error = $e->getMessage();
			}
		}

		View::render('login', ['user' => false, 'error' => $error]);
	}

	static public function getList()
	{
		$users = new Users;

		View::render('users_list', [
			'user' => $users->isLogged(),
			'users_list' => $users->all(),
		]);
	}

	static public function userDelete($id)
	{
		$users = new Users;
		$error = false;
		$u = $users->get('id', (int)$id);

		if (!$u)
		{
			return View::render('error', ['msg' => 'Invalid user ID']);
		}

		if (!empty($_POST['delete'])
			&& !View::checkCSRF())
		{
			$error = 'CSRF error, please re-submit the form';
		}
		else if (!empty($_POST['delete']))
		{
			try {
				$users->deleteUser($id);

				header('Location: /');
				exit;
			}
			catch (User_Exception $e) {
				$error = $e->getMessage();
			}
		}

		View::render('user_delete', [
			'user' => $users->isLogged(),
			'u' => $u,
			'error' => $error,
		]);
	}

	static public function edit($id)
	{
		$users = new Users;
		$error = false;
		$u = $users->get('id', (int)$id);

		if (!$u)
		{
			return View::render('error', ['msg' => 'Invalid user ID']);
		}

		if (!empty($_POST['save'])
			&& !View::checkCSRF())
		{
			$error = 'CSRF error, please re-submit the form';
		}
		else if (!empty($_POST['save']))
		{
			try {
				$users->editUser(
					$id,
					$_POST['name'] ?: '',
					$_POST['email'] ?: '',
					$_POST['password'] ?: '',
					$_POST['group'] ?: '',
					!empty($_POST['active'])
				);

				header('Location: /');
				exit;
			}
			catch (User_Exception $e) {
				$error = $e->getMessage();
			}
		}

		View::render('user_edit', [
			'user' => $users->isLogged(),
			'u' => $u,
			'error' => $error,
			'groups' => $users->listGroups(),
		]);
	}

	static public function add()
	{
		$users = new Users;
		$error = false;

		if (!empty($_POST['save'])
			&& !View::checkCSRF())
		{
			$error = 'CSRF error, please re-submit the form';
		}
		else if (!empty($_POST['save']))
		{
			try {
				$users->register(
					$_POST['name'] ?: '',
					$_POST['email'] ?: '',
					$_POST['password'] ?: '',
					$_POST['group'] ?: ''
				);

				header('Location: /');
				exit;
			}
			catch (User_Exception $e) {
				$error = $e->getMessage();
			}
		}

		View::render('user_add', [
			'user' => $users->isLogged(),
			'error' => $error,
			'groups' => $users->listGroups(),
		]);
	}
}