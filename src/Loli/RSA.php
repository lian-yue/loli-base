<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-25 13:06:27
/*
/* ************************************************************************** */
namespace Loli;
/*
class RSA{
	public $library = '';

	public $hash = 'sha256';

	public $hLen = 32;

	public $bits = 1024;

	public static $hashs = [
		'md2' => 'md2',
		'md5' => 'md5',
		'sha1' => 'sha-1',
		'sha256' => 'sha-256',
		'sha384' => 'sha-384',
		'sha512' => 'sha-512',
	];
	public function __construct($public = false, $private = false) {
		if (extension_loaded('openssl')) {
			$this->library = 'openssl';
		} elseif (extension_loaded('gmp')) {
			$this->library = 'gmp';
		} elseif (extension_loaded('bcmath')) {
			$this->library = 'bcmath';
		} else {
			$this->library = '';
		}
		$this->public = $public;
		$this->private = $private;
		$this->bits = 1024;
	}



	public function encode($message, $L = '', $private = false) {
		$k = $this->bits / 8;
		$EM = $this->addPKCS1V2($message, $k, $L);
		$m = $this->OS2IP($EM);
		$m = $this->RSAEP($m);
		$m = $this->I2OSP($m, $k);
	}


	public function setHash($hash) {
		if (!$hash) {
			$this->hash = false;
		} elseif (isset(self::$hashs[$hash])) {
			$this->hash = self::$hashs[$hash];
		} elseif (in_array($hash, self::$hashs, true)) {
			$this->hash = $hash;
		} else {
			$this->hash = 'sha-256';
		}
		$this->hLen = $this->hash ? strlen(hash($this->hash, '', true)) : 0;
		return $this->hash;
	}


	/*public function addPKCS1V15($M, $type = "\x00") {
		$k = $this->bits / 8;
		$mLen = strlen($M);
		if ($mLen > $k - 11) {
			throw new Exception("Message tool long");
		}
		$PSlength = $k - 3 - $mLen;
		switch ($type) {
			case "\x00":
				$PS = str_repeat("\x00", $PSlength);
				break;
			case "\x01":
				$PS = str_repeat("\xFF", $PSlength);
				break;
			case "\x02":
				$PS = '';
				for($i = 0; $i < $PSlength; $i++) {
					$PS .= chr(mt_rand(1, 255));
				}
				break;
			default:
				throw new Exception('Padding Type');
		}
		return "\x00" . $type . $PS . "\x00" . $M;
	}

	public function removePKCS1V15($EM) {
		$k = $this->bits / 8;
		if (strlen($EN) !== $k || $k < 11) {
			throw new Exception('EN Length');
		}
		if ($EM{0} !== "\x00") {
			throw new Exception('EN 0 != \\x00');
		}

		$EM = substr($EM, 1);

		if($EM{0} === "\x00") {
			throw new Exception('Block type 0 not implemented.');
		}

		if (!in_array($EM{0}, ["\x01", "\x02"], true)) {
			throw new Exception('Block type unknown');
		}

		$offset = strpos($EM, "\x00", 1);
		return substr($EM, $offset + 1);
	}

	public function addPKCS1V2($M, $k, $L = '') {
		$mLen = strlen($M);
		if ($mLen > $k - 2 * $this->hLen - 2) {
			throw new Exception("Message tool long");
		}

		$lHash = hash($this->hash, (string) $L, true);
		$PSlength = $k - strlen($M) - 2 % $this->hLen - 2;
		$PS = $PSlength ? str_repeat("\x00", $PSlength) : '';
		$DB = $lHash . $PS . "\x01" . $M;
		$seed = '';
		for($i = 0; $i < $this->hLen; $i++) {
			$seed .= chr(mt_rand(1, 255));
		}
		$dbMask = $this->MGF($seed, $k - $this->hLen, - 1);
		$maskedDB = $DB ^ $dbMask;
        $seedMask = $this->MGF($maskedDB, $this->hLen);
        $maskedSeed = $seed ^ $seedMask;
        return "\x00" . $maskedSeed . $maskedDB;
	}

	public function MGF($mgfSeed, $maskLen) {
		$t = '';
		$count = ceil($maskLen / $this->hLen);
		for ($i = 0; $i < $count; $i++) {
			$c = pack('N', $i);
			$t .= hash($this->hash, $mgfSeed . $c, true);
		}
		return substr($t, 0, $maskLen);
	}







	public function OS2IP($X) {
		switch ($this->library) {
			case 'gmp':
				return gmp_strval($X, 256);
				break;
			case 'bcmath':
			    $radix = "1";
			    $x = "0";
			    for($i = strlen($X) - 1; $i >= 0; $i--) {
			        $digit = ord($X{$i});
			        $partRes = bcmul($digit, $radix);
			        $x = bcadd($x, $partRes);
			        $radix = bcmul($radix, 256);
			    }
			    return $x;
				break;
			default:
		}
	}

	public function I2OSP($m, $k) {
		switch ($this->library) {
			case 'gmp':
				$X = "";
				while($m > 0) {
					$mod = gmp_mod($m, 256);
					$m = gmp_div($m, 256);
					$X = chr($mod) . $X;
				}
				break;
			case 'bcmath':
				$X = "";
				while($m > 0) {
					$mod = bcmod($m, 256);
					$m = bcdiv($m, 256);
					$X = chr($mod) . $X;
				}
				break;
			default:
		}
		if (strlen($X) !== $k) {
			throw new Exception('Integer too');
		}
		return $X;
	}









	public function RSAEP($m) {
		switch ($this->library) {
			case 'gmp':
				return gmp_strval(gmp_powm($m, $this->publicKey, $this->publicModulus));
				break;
			case 'bcmath':
				return bcpowmod($m, $this->publicKey, $this->publicModulus, 0);
				break;
			default:
		}
	}

	public function create($bits = 1024) {
		switch ($this->library) {
			case 'openssl':
				$res = openssl_pkey_new(['private_key_bits' => $bits]);
				openssl_pkey_export($res, $privateKey);
				$publicKey = openssl_pkey_get_details($res);
				$publicKey = $publicKey['key'];
				break;
			default:
				# code...
				break;
		}

	}
}*/