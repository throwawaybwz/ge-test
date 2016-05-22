<?php
namespace KD2;

/*
	Simple SMTP library for PHP
	Copyright 2012-2013 BohwaZ <http://bohwaz.net/>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, version 3 of the
    License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

class SMTP_Exception extends \Exception {}

class SMTP
{
	const NONE = 0;
	const TLS = 1;
	const SSL = 2;

	const EOL = "\r\n";

	protected $server;
	protected $port;
	protected $username = null;
	protected $password = null;
	protected $secure = 0;

	protected $conn = null;
	protected $last_line = null;

	protected $servername = 'localhost';

	public $timeout = 30;

	protected function _read()
	{
		$data = '';

		while ($str = fgets($this->conn, 4096))
		{
			$data .= $str;

			if ($str[3] == ' ')
			{
				break;
			}
		}

		return $data;
	}

	protected function _readCode($data = null)
	{
		if (is_null($data))
		{
			$data = $this->_read();
			$this->last_line = $data;
		}

		return substr($data, 0, 3);
	}

	protected function _write($data, $eol = true)
	{
		fputs($this->conn, $data . ($eol ? self::EOL : ''));
	}

	public function __construct($server = 'localhost', $port = 25, $username = null, $password = null, $secure = self::NONE)
	{
		$this->server = $secure == self::SSL ? 'ssl://' . $server : $server;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
		$this->secure = (int)$secure;
		$this->servername = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : gethostname();
	}

	public function __destruct()
	{
		$this->disconnect();
	}

	public function disconnect()
	{
		if (is_null($this->conn))
		{
			return true;
		}

		$this->_write('QUIT');
		$this->_read();
		fclose($this->conn);
		$this->conn = null;
		$this->last_line = null;
		return true;
	}

	public function connect()
	{
		$this->conn = fsockopen($this->server, $this->port, $errno, $errstr, $this->timeout);

		if (!$this->conn)
		{
			throw new SMTP_Exception('Unable to connect to server ' . $this->server . ': ' . $errno . ' - ' . $errstr);
		}

		if ($this->_readCode() != 220)
		{
			throw new SMTP_Exception('SMTP error: '.$this->last_line);
		}

		return true;
	}

	public function authenticate()
	{
		$this->_write('HELO '.$this->servername);
		$this->_read();

		if ($this->secure == self::TLS)
		{
			$this->_write('STARTTLS');

			if ($this->_readCode() != 220)
			{
				throw new SMTP_Exception('Can\'t start TLS session: '.$this->last_line);
			}

			stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

			$this->_write('HELO ' . $this->servername);

			if ($this->_readCode() != 250)
			{
				throw new SMTP_Exception('SMTP error on HELO: '.$this->last_line);
			}
		}

		if (!is_null($this->username) && !is_null($this->password))
		{
			$this->_write('AUTH LOGIN');

			if ($this->_readCode() != 334)
			{
				throw new SMTP_Exception('SMTP AUTH error: '.$this->last_line);
			}

			$this->_write(base64_encode($this->username));

			if ($this->_readCode() != 334)
			{
				throw new SMTP_Exception('SMTP AUTH error: '.$this->last_line);
			}

			$this->_write(base64_encode($this->password));

			if ($this->_readCode() != 235)
			{
				throw new SMTP_Exception('SMTP AUTH error: '.$this->last_line);
			}
		}

		return true;
	}

	/**
	 * Send a raw email
	 * @param  string $from From address (MAIL FROM:)
	 * @param  mixed  $to   To address (RCPT TO:), can be a string (single recipient) 
	 *                      or an array (multiple recipients)
	 * @param  string $data Mail data (DATA)
	 * @return boolean TRUE if success, exception if it fails
	 */
	public function rawSend($from, $to, $data)
	{
		if (is_null($this->conn))
		{
			$this->connect();
			$this->authenticate();
		}

		$this->_write('RSET');
		$this->_read();

		$this->_write('MAIL FROM: <'.$from.'>');
		$this->_read();

		if (is_string($to))
		{
			$to = array($to);
		}

		foreach ($to as $dest)
		{
			$this->_write('RCPT TO: <'.$dest.'>');
			$this->_read();
		}

		$data = rtrim($data) . self::EOL;

		$this->_write('DATA');
		$this->_read();
		$this->_write($data . '.');

		if ($this->_readCode() != 250)
		{
			throw new SMTP_Exception('Can\'t send message. SMTP said: ' . $this->last_line);
		}

		return true;
	}

	/**
	 * Send an email to $to, using $subject as a subject and $message as content
	 * @param  mixed  $to      List of recipients, as an array or a string
	 * @param  string $subject Message subject
	 * @param  string $message Message content
	 * @param  mixed  $headers Additional headers, either as an array of key=>value pairs or a string
	 * @return boolean		   TRUE if success, exception if it fails
	 */
	public function send($to, $subject, $message, $headers = array())
	{
		// Parse $headers if it's a string
		if (is_string($headers))
		{
			preg_match_all('/^(\\S.*?):(.*?)\\s*(?=^\\S|\\Z)/sm', $headers, $match);
			$headers = array();

			foreach ($match as $header)
			{
				$headers[$header[1]] = $header[2];
			}
		}

		// Normalize headers
		$headers_normalized = array();

		foreach ($headers as $key=>$value)
		{
			$key = preg_replace_callback('/^.|(?<=-)./', function ($m) { return ucfirst($m[0]); }, strtolower(trim($key)));
			$headers_normalized[$key] = $value;
		}

		$headers = $headers_normalized;
		unset($headers_normalized);

		// Set default headers if they are missing
		if (!isset($headers['Date']))
		{
			$headers['Date'] = date(DATE_RFC822);
		}

		$headers['Subject'] = (trim($subject) == '') ? '' : '=?UTF-8?B?'.base64_encode($subject).'?=';

		if (!isset($headers['MIME-Version']))
		{
			$headers['MIME-Version'] = '1.0';
		}

		if (!isset($headers['Content-Type']))
		{
			$headers['Content-Type'] = 'text/plain; charset=UTF-8';
		}

		if (!isset($headers['From']))
		{
			$headers['From'] = 'mail@'.$this->servername;
		}

		$content = '';

		foreach ($headers as $name=>$value)
		{
			$content .= $name . ': ' . $value . self::EOL;
		}

		$content = trim($content) . self::EOL . self::EOL . $message . self::EOL;
		$content = preg_replace("#(?<!\r)\n#si", self::EOL, $content);
		$content = wordwrap($content, 998, self::EOL, true);

		// SMTP Sender
		$from = 'mail@'.$this->servername;

		// Extract and filter recipients addresses
		$to = self::extractEmailAddresses($to);
		$headers['To'] = implode(', ', $to);

		if (isset($headers['Cc']))
		{
			$headers['Cc'] = self::extractEmailAddresses($headers['Cc']);
			$to = array_merge($to, $headers['Cc']);

			$headers['Cc'] = implode(', ', $headers['Cc']);
		}

		if (isset($headers['Bcc']))
		{
			$headers['Bcc'] = self::extractEmailAddresses($headers['Bcc']);
			$to = array_merge($to, $headers['Bcc']);

			$headers['Bcc'] = implode(', ', $headers['Bcc']);
		}

		if (is_null($this->conn))
		{
			$this->connect();
			$this->authenticate();
		}

		$this->_write('RSET');
		$this->_read();

		$this->_write('MAIL FROM: <'.$from.'>');
		$this->_read();

		foreach ($to as $dest)
		{
			$this->_write('RCPT TO: <'.$dest.'>');
			$this->_read();
		}

		$this->_write('DATA');
		$this->_read();
		$this->_write($content . '.');

		if ($this->_readCode() != 250)
		{
			throw new SMTP_Exception('Can\'t send message. SMTP said: ' . $this->last_line);
		}

		return true;
	}

	/**
	 * Takes a string like a From, Cc, To or Bcc header and gets out all the email
	 * addresses it can find out of it.
	 * This is not perfect as it won't handle addresses like "uncommon,email"@email.tld
	 * because of the comma, but FILTER_VALIDATE_EMAIL doesn't accept it as an email address either
	 * (though it's perfectly valid if you follow the RFC).
	 */
	public static function extractEmailAddresses($str)
	{
		if (is_array($str))
		{
			$out = array();

			// Filter invalid email addresses
			foreach ($str as $email)
			{
				if (filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					$out[] = $email;
				}
			}

			return $out;
		}

		$str = explode(',', $str);
		$out = array();

		foreach ($str as $s)
		{
			$s = trim($s);
			if (preg_match('/(?:([\'"]).*?\1\s*)?<([^>]*)>/', $s, $match) && filter_var(trim($match[2]), FILTER_VALIDATE_EMAIL))
			{
				$out[] = trim($match[2]);
			}
			elseif (filter_var($s, FILTER_VALIDATE_EMAIL))
			{
				$out[] = $s;
			}
			else
			{
				// unrecognized, skip
			}
		}

		return $out;
	}
}

?>