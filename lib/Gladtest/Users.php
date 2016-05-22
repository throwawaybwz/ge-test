<?php

namespace Gladtest;

class Users
{
	/**
	 * DB PDO pointer
	 * @var null
	 */
	protected $db = null;

	/**
	 * Class construct, connects to DB
	 */
	public function __construct()
	{
		$this->db = new \PDO('mysql:dbname=' . MYSQL_DATABASE . ';charset=UTF8;host=' . MYSQL_HOST, 
			MYSQL_USER, MYSQL_PASSWORD);
	}

	/**
	 * Check and sanitize user entry
	 * @param  string &$name     User name
	 * @param  string &$email    User email
	 * @param  string &$password User password
	 * @param  int    &$group    User group ID (integer)
	 * @param  int    $id    	 User ID (if existing user)
	 * @return void
	 */
	protected function _checkAndSanitizeUserData(&$name, &$email, &$password, &$group, $id = null)
	{
		$name = trim($name);

		if ($name === '')
		{
			throw new User_Exception('The name can not be left empty.');
		}

		$email = trim($email);

		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			throw new User_Exception('Invalid email address.');
		}

		// Check if the email address is not already registered
		$query = $this->db->prepare('SELECT id FROM users WHERE email = :email AND id != :id;');
		$query->bindValue('email', $email);
		$query->bindValue('id', $id);
		$query->execute();

		if ($query->rowCount() != 0)
		{
			throw new User_Exception('This email address is already registered.');
		}

		if (!is_null($password))
		{
			$password = trim($password);

			if (strlen($password) < 8)
			{
				throw new User_Exception('The password should be at least 8 characters long (and contain 2 numbers).');
			}

			if (preg_match_all('/\d/', $password) < 2)
			{
				throw new User_Exception('The password must contain at least 2 numbers (and be 8 characters long).');
			}

			// Encrypt password
			$password = password_hash($password, PASSWORD_DEFAULT);
		}

		$group = (int) $group;

		$query = $this->db->prepare('SELECT 1 FROM user_groups WHERE id = :id');
		$query->bindValue('id', $group);
		$query->execute();

		if ($query->rowCount() != 1)
		{
			throw new \RuntimeException('Invalid group ID supplied: ' . $group);
		}

		return;
	}

	/**
	 * Register an account
	 * @param  string $name        User name
	 * @param  string $email       User email
	 * @param  string $password    User password
	 * @param  integer $group       User group ID
	 * @param  integer $facebook_id Facebook ID (supplied when registering via Facebook)
	 * @return boolean              TRUE if success
	 * @throws User_Exception 		If there is an error in user entry
	 */
	public function register($name, $email, $password = null, $group = null, $facebook_id = null)
	{
		// Default user group
		if (is_null($group))
		{
			$query = $this->db->prepare('SELECT id FROM user_groups WHERE admin = 0 LIMIT 1;');
			$query->execute();
			$row = $query->fetch(\PDO::FETCH_OBJ);
			$group = $row->id;
		}

		$this->_checkAndSanitizeUserData($name, $email, $password, $group);

		$facebook_id = trim($facebook_id) ?: null;

		$query = $this->db->prepare('INSERT INTO users (name, email, password, facebook_id, group_id)
			VALUES (:name, :email, :password, :facebook_id, (SELECT id FROM user_groups WHERE admin = 1));');

		$query->bindValue(':name', $name);
		$query->bindValue(':email', $email);
		$query->bindValue(':password', $password);
		$query->bindValue(':facebook_id', $facebook_id);
		
		$query->execute();

		(new Email)->welcome($email);

		return true;
	}

	/**
	 * User login
	 * @param  string $email    User email
	 * @param  string $password User password
	 * @param  boolean $session Create cookie session?
	 * @return boolean          TRUE if user exists and password matches, or FALSE if not
	 * @throws User_Exception   if user account is not active
	 */
	public function login($email, $password, $session = true)
	{
		$user = $this->get('email', $email);

		// User unknown
		if (!$user)
		{
			return false;
		}

		$hash = $user->password;

		// Wrong password
		if (!password_verify($password, $hash))
		{
			return false;
		}

		if (!$user->active)
		{
			throw new User_Exception('This account is disabled.');
		}

		return $session ? $this->createSession($user) : true;
	}

	/**
	 * Create user session after login
	 * @param  object $user User data
	 * @return boolean
	 */
	public function createSession($user)
	{
		// Only store minimal data in session
		$user = (object) ['name' => $user->name, 'id' => (int)$user->id, 'admin' => (int)$user->admin];

		// Create session
		$session = new \KD2\CacheCookie;
		$session->set('user', $user);
		$session->save();

		return true;
	}

	/**
	 * Get a user data
	 * @param  string $column Column to match
	 * @param  string $value  Column value to match
	 * @return object
	 */
	public function get($column, $value)
	{
		$query = $this->db->prepare('SELECT users.*, g.admin AS admin FROM users 
			INNER JOIN user_groups AS g ON g.id = users.group_id
			WHERE users.' . $column . ' = :' . $column . ' LIMIT 1;');
		$query->bindValue(':' . $column, $value);
		$query->execute();

		if ($query->rowCount() < 1)
		{
			return false;
		}

		return $query->fetch(\PDO::FETCH_OBJ);
	}

	/**
	 * Logins or register (and login) a facebook user
	 * @param  string $facebook_id Facebook ID
	 * @param  string $name        User name (from Facebook)
	 * @param  string $email       User email (from Facebook)
	 * @return boolean
	 */
	public function facebookRegisterOrLogin($facebook_id, $name, $email)
	{
		$user = $this->get('facebook_id', $facebook_id);

		if (!$user)
		{
			$this->register($name, $email, null, null, $facebook_id);
			$user = $this->get('facebook_id', $facebook_id);
		}

		if (!$user->active)
		{
			throw new User_Exception('This account is disabled.');
		}

		return $this->createSession($user);
	}

	/**
	 * Returns current user session or FALSE
	 * @return mixed
	 */
	public function isLogged()
	{
		$session = new \KD2\CacheCookie;
		
		if ($session->get('user'))
		{
			return (object) $session->get('user');
		}

		return false;
	}

	/**
	 * Returns a list of current groups in DB
	 * @return array List of users
	 */
	public function listGroups()
	{
		$query = $this->db->prepare('SELECT * FROM user_groups ORDER BY name ASC;');
		$query->execute();
		return $query->fetchAll(\PDO::FETCH_OBJ);
	}

	/**
	 * Edit existing user data
	 * @param  integer $id      User ID
	 * @param  string $name        User name
	 * @param  string $email       User email
	 * @param  string $password    User password
	 * @param  integer $group       User group ID
	 * @param  integer $facebook_id Facebook ID (supplied when registering via Facebook)
	 * @param  boolean $active      0 if user is disabled (no login)
	 * @return boolean              TRUE if success
	 * @throws User_Exception 		If there is an error in user entry
	 */
	public function editUser($id, $name, $email, $password, $group, $active)
	{
		$this->_checkAndSanitizeUserData($name, $email, $password, $group, $id);

		$query = $this->db->prepare('UPDATE users SET name = :name, email = :email, password = :password, 
			group_id = :group_id, active = :active WHERE id = :id;');

		$query->bindValue(':id', (int) $id);
		$query->bindValue(':name', $name);
		$query->bindValue(':email', $email);
		$query->bindValue(':password', $password);
		$query->bindValue(':group_id', $group);
		$query->bindValue(':active', (int) (bool) $active);

		return $query->execute();
	}

	/**
	 * Deletes a user
	 * @param  integer $id User ID
	 * @return boolean
	 */
	public function deleteUser($id)
	{
		$query = $this->db->prepare('DELETE FROM users WHERE id = :id;');
		$query->bindValue(':id', $id);
		return $query->execute();
	}

	/**
	 * Returns a list of current users in DB
	 * @return array List of users
	 */
	public function all()
	{
		$query = $this->db->prepare('SELECT id, name, group_id, email, active, created, updated, facebook_id, twitter_id
			FROM users ORDER BY name ASC;');
		$query->execute();
		return $query->fetchAll(\PDO::FETCH_OBJ);
	}
}