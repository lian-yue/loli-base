<?php
namespace Loli\Crypt;
class Base32 {

	private static $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567=';

	private static $flippedAlphabet = [
		'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5,
		'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11,
		'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17,
		'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
		'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29,
		'6' => 30, '7' => 31, '=' => 0,
	];


	public static function encode($data, $padding = true) {
		if (!is_string($data)) {
			$data = (string) $data;
		}
        $dataLength = strlen($data);

        $fillLength = $dataLength % 5;
		if ($fillLength) {
			$fillLength = 5 - $fillLength;
			$data .= str_repeat(chr(0), $fillLength);
		}

        $code = '';
        for ($i = 0; $i < ($dataLength + $fillLength); $i = $i + 5) {

            $byte1 = ord($data{$i});
            $byte2 = ord($data{$i + 1});
            $byte3 = ord($data{$i + 2});
            $byte4 = ord($data{$i + 3});
            $byte5 = ord($data{$i + 4});


            $bitGroup = $byte1 >> 3;
            $code .= self::$alphabet{$bitGroup};

            $bitGroup = ($byte1 & ~(31 << 3)) << 2 | $byte2 >> 6;
            $code .= self::$alphabet{$bitGroup};

            $bitGroup = $byte2 >> 1 & ~(3 << 5);
            $code .= self::$alphabet{$bitGroup};

            $bitGroup = ($byte2 & 1) << 4 | $byte3 >> 4;
            $code .= self::$alphabet{$bitGroup};

            $bitGroup = ($byte3 & ~(15 << 4)) << 1 | $byte4 >> 7;
            $code .= self::$alphabet{$bitGroup};

            $bitGroup = $byte4 >> 2 & ~(1 << 5);
            $code .= self::$alphabet{$bitGroup};

            $bitGroup = ($byte4 & ~(63 << 2)) << 3 | $byte5 >> 5;
            $code .= self::$alphabet{$bitGroup};

            $bitGroup = $byte5 & ~(7 << 5);
            $code .= self::$alphabet{$bitGroup};
        }

        $codeLength = ($dataLength + $fillLength) * 8 / 5;
        $fillCharLength = (int) ($fillLength * 8 / 5);
        $code = substr($code, 0, $codeLength - $fillCharLength);
        if ($padding) {
            $code .= str_repeat(self::$alphabet{32}, $fillCharLength);
		}
        return $code;
    }




	public static function decode($code) {
		if (!is_string($code)) {
			$code = (string) $code;
		}
		$code = strtoupper(trim($code, '='));

		$codeLength = strlen($code);

		$remainder = $codeLength % 8;
		if (!in_array($remainder, [0, 2, 4, 5, 7], true)) {
			return false;
		}

		$paddingLength = 0;
		if ($remainder !== 0) {
			$paddingLength = 8 - $remainder;
			$code .= str_repeat('=', $paddingLength);
			$codeLength += $paddingLength;
		}


		$data = '';
		for ($i = 0; $i < $codeLength; $i = $i + 8) {
			$bitCode1 = $code{$i};
			if (!isset(self::$flippedAlphabet[$bitCode1])) {
				return false;
			}
			$bitGroup1 = self::$flippedAlphabet[$bitCode1];

			$bitCode2 = $code{$i + 1};
			if (!isset(self::$flippedAlphabet[$bitCode2])) {
				return false;
			}
			$bitGroup2 = self::$flippedAlphabet[$bitCode2];

			$bitCode3 = $code{$i + 2};
			if (!isset(self::$flippedAlphabet[$bitCode3])) {
				return false;
			}
			$bitGroup3 = self::$flippedAlphabet[$bitCode3];

			$bitCode4 = $code{$i + 3};
			if (!isset(self::$flippedAlphabet[$bitCode4])) {
				return false;
			}
			$bitGroup4 = self::$flippedAlphabet[$bitCode4];

			$bitCode5 = $code{$i + 4};
			if (!isset(self::$flippedAlphabet[$bitCode5])) {
				return false;
			}
			$bitGroup5 = self::$flippedAlphabet[$bitCode5];

			$bitCode6 = $code{$i + 5};
			if (!isset(self::$flippedAlphabet[$bitCode6])) {
				return false;
			}
			$bitGroup6 = self::$flippedAlphabet[$bitCode6];

			$bitCode7 = $code{$i + 6};
			if (!isset(self::$flippedAlphabet[$bitCode7])) {
				return false;
			}
			$bitGroup7 = self::$flippedAlphabet[$bitCode7];

			$bitCode8 = $code{$i + 7};
			if (!isset(self::$flippedAlphabet[$bitCode8])) {
				return false;
			}
			$bitGroup8 = self::$flippedAlphabet[$bitCode8];


			$byte = $bitGroup1 << 3 | $bitGroup2 >> 2;
			$data .= chr($byte);

			$byte = ($bitGroup2 & ~(7 << 2)) << 6 | $bitGroup3 << 1 | $bitGroup4 >> 4;
			$data .= chr($byte);

			$byte = ($bitGroup4 & ~(1 << 4)) << 4 | $bitGroup5 >> 1;
			$data .= chr($byte);

			$byte = ($bitGroup5 & 1) << 7 | $bitGroup6 << 2 | $bitGroup7 >> 3;
			$data .= chr($byte);

			$byte = ($bitGroup7 & ~(3 << 3)) << 5 | $bitGroup8;
			$data .= chr($byte);
		}

		$fillbyteCount = (int) ceil($paddingLength * 5 / 8);
		if ($fillbyteCount > 0) {
			$byteCount = strlen($data);
			$data = substr($data, 0, $byteCount - $fillbyteCount);
		}
		return $data;
	}
}
