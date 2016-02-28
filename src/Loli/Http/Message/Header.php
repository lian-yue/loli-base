<?php
namespace Loli\Http\Message;
class Header {

	private static $cookie = [];

	public static function setCookie($name, $value = '', $expires = 0, $path = null,  $domain = null, $secure = null, $httponly = null) {
		if (!$value && !$expires) {
			$value = 'deleted';
			$expires = new \DateTime('1970-1-2');
		} elseif (!$expires) {

		} elseif(is_numeric($expires)) {
			if ($expires < 0) {
				$expires = new \DateTime('1970-1-2');
			} else {
				$expires = new \DateTime('now +' . $expires . ' seconds');
			}
		} elseif ($expires instanceof \DateInterval) {
			$datetime = new \DateTime();
			$datetime->add($expires);
			$expires = $datetime;
		} elseif ($expires instanceof \DateTime) {
			$expires = $expires;
		} else {
			throw new \InvalidArgumentException( __METHOD__ . '() The expiration time is invalid');
		}

		foreach(['path', 'domain', 'secure', 'httponly'] as $key) {
			if (!isset($$key) && isset(self::$cookie[$key])) {
				$$key = self::$cookie[$key];
			}
		}


		$array = [];
		$array[rawurlencode($name)] = rawurlencode(to_string($value));
		if ($expires) {
			$expires->setTimeZone(new \DateTimeZone('GMT'));
			$array['expires'] = $expires->format(\DateTime::COOKIE);
			$array['Max-Age'] = $expires->getTimestamp() - time();
		}
		if ($path) {
			$array['path'] = str_replace('%2F', '/', rawurlencode((string) $path));
		}
		if ($domain) {
			$array['domain'] = rawurlencode((string) $domain);
		}
		if ($secure) {
			$array['secure'] = true;
		}
		if ($httponly) {
			$array['httponly'] = true;
		}

		foreach($array as $name => &$value) {
			if ($value !== true) {
				$value = $name . '=' . $value;
			} else {
				$value = $name;
			}
		}
		return implode('; ', $array);
	}

	public static function cacheControl(array $array) {
		$cache = [];
		foreach ($array as $name => $value) {
			switch ($name) {
				case 'max-age':
				case 's-maxage':
					$cache[] = $name .'=' . $value;
					break;
				case 'no-cache':
					$cache[] = $name .($value && $value !== true ? '=' . $value : '');
					break;
				case 'public':
					if (empty($attribute)) {
						$attribute = true;
						$cache[] = $value ? $name : 'private';
					}
					break;
				case 'private':
					if (empty($attribute)) {
						$attribute = true;
						$cache[] = $value ? $name : 'public';
					}
					break;
				default:
					if ($value) {
						$cache[] = $name;
					}
			}
		}
		return implode(',', $cache);
	}



	public static function register() {
		self::$cookie = empty($_SERVER['LOLI']['cookie']) ? [] : $_SERVER['LOLI']['cookie'];
	}
}
Header::register();
