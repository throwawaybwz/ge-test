<?php

namespace Gladtest\Controller;
use Gladtest\Users;

class Facebook
{
	/**
	 * Initiates Facebook login/register query (and redirect)
	 * @return void
	 */
	static public function login()
	{
		session_start();

		$fb = new \Facebook\Facebook([
			'app_id' => \Gladtest\FACEBOOK_APPID,
			'app_secret' => \Gladtest\FACEBOOK_SECRET,
			'default_graph_version' => 'v2.2',
		]);

		$helper = $fb->getRedirectLoginHelper();

		$permissions = ['email', 'public_profile'];
		$url = $helper->getLoginUrl(\Gladtest\FACEBOOK_CALLBACK_URL, $permissions);

		header('Location: ' . $url);
		exit;
	}

	/**
	 * Handles facebook login callback
	 * @return void
	 */
	static public function callback()
	{
		session_start();

		$fb = new \Facebook\Facebook([
			'app_id' => \Gladtest\FACEBOOK_APPID,
			'app_secret' => \Gladtest\FACEBOOK_SECRET,
			'default_graph_version' => 'v2.2',
		]);

		$helper = $fb->getRedirectLoginHelper();

		$accessToken = $helper->getAccessToken();

		if (!isset($accessToken))
		{
			if ($helper->getError())
			{
				header('HTTP/1.0 401 Unauthorized');
				echo "Error: " . $helper->getError() . "\n";
				echo "Error Code: " . $helper->getErrorCode() . "\n";
				echo "Error Reason: " . $helper->getErrorReason() . "\n";
				echo "Error Description: " . $helper->getErrorDescription() . "\n";
			}
			else
			{
				header('HTTP/1.0 400 Bad Request');
				echo 'Bad request';
			}
			exit;
		}

		// The OAuth 2.0 client handler helps us manage access tokens
		$oAuth2Client = $fb->getOAuth2Client();

		// Get the access token metadata from /debug_token
		$tokenMetadata = $oAuth2Client->debugToken($accessToken);

		// Validation (these will throw FacebookSDKException's when they fail)
		$tokenMetadata->validateAppId(\Gladtest\FACEBOOK_APPID);
		$tokenMetadata->validateExpiration();

		if (! $accessToken->isLongLived()) {
			$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
		}

		$response = $fb->get('/me?fields=id,name,email', $accessToken);

		$user = $response->getGraphUser();

		$users = new Users;

		return $users->facebookRegisterOrLogin($user['id'], $user['name'], $user['email']);
	}
}