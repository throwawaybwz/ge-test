<?php

namespace Gladtest\Controller;

use Gladtest\View;
use Gladtest\Users;
use Gladtest\User_Exception;

class API
{
	/**
	 * Very simple API token
	 * @return string API token
	 */
	static public function createToken()
	{
		$token = sha1(mt_rand());
		$token = hash_hmac('md5', $token, md5($_SERVER['DOCUMENT_ROOT'] . 'Secret')) . '|' . $token;
		return $token;
	}

	/**
	 * Very simple API token check
	 * @return string API token
	 */
	static public function checkToken($token)
	{
		list($digest, $data) = explode('|', $token, 2);
		return ($digest === hash_hmac('md5', $data, md5($_SERVER['DOCUMENT_ROOT'] . 'Secret')));
	}

	/**
	 * API login, requires email and password as POST
	 * @return string JSON response
	 */
	static public function login()
	{
		$msg = false;
		$success = false;
		$token = null;

		if (!empty($_POST['email']) && !empty($_POST['password']))
		{
			try {
				if ($success = (new Users)->login($_POST['email'], $_POST['password']))
				{
					$token = self::createToken();
					$msg = 'Success!';
				}
				else
				{
					$msg = 'Invalid credentials';
				}
			}
			catch (User_Exception $e)
			{
				$msg = $e->getMessage();
			}
		}

		header('Content-Type: application/json');
		echo json_encode([
			'message'	=>	$msg,
			'success'	=>	$success,
			'token'		=>	$token,
		]);
	}

	static public function usersList()
	{
		header('Content-Type: application/json');
		echo json_encode((new Users)->all());
	}
}