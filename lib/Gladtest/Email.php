<?php

namespace Gladtest;

class Email
{
	/**
	 * Sends welcome email to new users
	 * @param  string $to User email address
	 * @return boolean
	 */
	public function welcome($to)
	{
		$subject = 'Welcome to test app!';
		$content = 'Your account has been created.';

		if (SMTP_PASSWORD)
		{
			$smtp = new \KD2\SMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, \KD2\SMTP::TLS);
			return $smtp->send($to, $subject, $content);
		}
	}
}