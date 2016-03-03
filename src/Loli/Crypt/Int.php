<?php
namespace Loli\Crypt;
class Int{
	public static function encode($integer, &$mask = null, $maskSize = 2, $hex = 32) {
		if ($mask === null) {
			$mask = mt_rand(0, pow(256, $maskSize) - 1);
		}
		$maskArray = [];
		for ($i=0; $i < $maskSize; ++$i) {
			$maskArray[] = chr((intval($mask / pow(256, $i))) % 256);
		}

		$maskChunk = array_chunk($maskArray, floor($maskSize/ 2));
		$integerArray = $maskChunk[0];
		if (isset($maskChunk[2])) {
			$maskChunk[1][] = $maskChunk[2][0];
		}
		$i = 0;
		do {
			$integerArray[] = chr($integer % 256) ^ $maskArray[$i % $maskSize];
			++$i;
		} while (($integer = floor($integer / 256)) > 0);
		$code = implode('', $integerArray). implode('', $maskChunk[1]);
		if ($hex === 64) {
			return str_replace(['=', '+', '/'], ['', '-', '_'], base64_encode($code));
		}
		return strtolower(Base32::encode($code, false));
	}


	public static function decode($code, &$mask = null, $maskSize = 2, $hex = 32) {
		if ($hex === 64) {
			$code = base64_decode(strtr($code, '-_', '+/'));
		} else {
			$code = Base32::decode($code);
		}
		$length = strlen($code);
		if (!$code || $length <= $maskSize) {
			return false;
		}


		$maskStartSize = floor($maskSize/ 2);
		$maskEndSize = $length - ceil($maskSize /2);

		$maskArray = $integerArray = [];
		for ($i= 0; $i < $length; ++$i) {
			if ($i < $maskStartSize || $i >= $maskEndSize) {
				$maskArray[] = $code{$i};
			} else {
				$integerArray[] = $code{$i};
			}
		}


		$mask2 = 0;
		foreach($maskArray as $i => $value) {
			$mask2 += ord($value) * pow(256, $i);
		}

		if ($mask !== null && $mask !== $mask2) {
			return false;
		}
		$mask = $mask2;

		$integer = 0;
		foreach($integerArray as $i => $value) {
			$integer +=  ord($value ^ $maskArray[$i % $maskSize]) * pow(256, $i);
		}
		return $integer;
	}
}
