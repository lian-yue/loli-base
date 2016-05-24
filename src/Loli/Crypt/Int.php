<?php
namespace Loli\Crypt;
class Int{
	public static function encode($integer, &$salt = null, $saltSize = 2, $hex = 32) {
		if ($salt === null) {
			$salt = mt_rand(0, pow(256, $saltSize) - 1);
		}
		$saltArray = [];
		for ($i=0; $i < $saltSize; ++$i) {
			$saltArray[] = chr((intval($salt / pow(256, $i))) % 256);
		}

		$saltChunk = array_chunk($saltArray, floor($saltSize/ 2));
		$integerArray = $saltChunk[0];
		if (isset($saltChunk[2])) {
			$saltChunk[1][] = $saltChunk[2][0];
		}
		$i = 0;
		do {
			$integerArray[] = chr($integer % 256) ^ $saltArray[$i % $saltSize];
			++$i;
		} while (($integer = floor($integer / 256)) > 0);
		$code = implode('', $integerArray). implode('', $saltChunk[1]);
		if ($hex === 64) {
			return str_replace(['=', '+', '/'], ['', '-', '_'], base64_encode($code));
		}
		return strtolower(Base32::encode($code, false));
	}


	public static function decode($code, &$salt = null, $saltSize = 2, $hex = 32) {
		if ($hex === 64) {
			$code = base64_decode(strtr($code, '-_', '+/'));
		} else {
			$code = Base32::decode($code);
		}
		$length = strlen($code);
		if (!$code || $length <= $saltSize) {
			return false;
		}


		$saltStartSize = floor($saltSize/ 2);
		$saltEndSize = $length - ceil($saltSize /2);

		$saltArray = $integerArray = [];
		for ($i= 0; $i < $length; ++$i) {
			if ($i < $saltStartSize || $i >= $saltEndSize) {
				$saltArray[] = $code{$i};
			} else {
				$integerArray[] = $code{$i};
			}
		}


		$salt2 = 0;
		foreach($saltArray as $i => $value) {
			$salt2 += ord($value) * pow(256, $i);
		}

		if ($salt !== null && $salt !== $salt2) {
			return false;
		}
		$salt = $salt2;

		$integer = 0;
		foreach($integerArray as $i => $value) {
			$integer +=  ord($value ^ $saltArray[$i % $saltSize]) * pow(256, $i);
		}
		return $integer;
	}
}
