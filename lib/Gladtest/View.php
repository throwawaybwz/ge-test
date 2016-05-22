<?php

namespace Gladtest;

class View
{
	/**
	 * Very simple CSRF protection which doesn't require sessions or cookies
	 * @return string HMAC-MD5 string
	 */
	static public function generateCSRF()
	{
		$csrf = sha1(mt_rand());
		$csrf = hash_hmac('md5', $csrf, md5($_SERVER['DOCUMENT_ROOT'])) . '|' . $csrf;
		return $csrf;
	}

	/**
	 * Very simple CSRF check
	 * @param  string $csrf Received CSRF string
	 * @return boolean
	 */
	static public function checkCSRF()
	{
		$csrf = $_POST['csrf'];
		list($digest, $data) = explode('|', $csrf, 2);
		return ($digest === hash_hmac('md5', $data, md5($_SERVER['DOCUMENT_ROOT'])));
	}

	static public function render($template, $vars = null)
	{
		$csrf = function () {
			return '<input type="hidden" name="csrf" value="' . htmlspecialchars(View::generateCSRF(), ENT_QUOTES, 'UTF-8') . '" />';
		};

		$escape = function($str)
		{
			return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
		};

		$inc = function($tpl) use ($vars)
		{
			View::render($tpl, $vars);
		};

		if (!is_null($vars))
		{
			extract($vars);
		}

		require GLAD_ROOT . '/views/' . $template . '.php';
	}
}