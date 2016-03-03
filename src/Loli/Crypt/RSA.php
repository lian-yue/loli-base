<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-11-05 06:14:13
/*
/* ************************************************************************** */
namespace Loli\Crypt;
if (!extension_loaded('openssl')) {
	throw new Exception('openssl extension is not loaded');
}
class Rsa{

	private $_publicKey = '';

	private $_privateKey;

	const EXPONENT = 65537;

	const PADDING = OPENSSL_PKCS1_PADDING;

	public function __construct(array $config) {
		if (!empty($config['publicKey'])) {
			$this->setPublicKey($config['publicKey']);
		}
		if (!empty($config['privateKey'])) {
			$this->setPrivateKey($config['privateKey'], empty($config['passphrase']) ? null : $config['passphrase']);
		}
	}

	public static function newKey($bits = 2048, $passphrase = false, $method = 'sha512') {
		$passphrase = $passphrase ? $passphrase : null;

		$config = [];
		$res = openssl_pkey_new([
			'digest_alg' => $method,
			'private_key_bits' => $bits,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		] + $config);

		openssl_pkey_export($res, $privateKey, $passphrase, $config);
		$publicKey = openssl_pkey_get_details($res);
		$publicKey = $publicKey['key'];
		return ['publicKey' => $publicKey, 'privateKey' => $privateKey, 'passphrase' => $passphrase];
	}

	public static function passphraseVerify($privateKey, $passphrase) {
		if (!openssl_pkey_get_private($privateKey, $passphrase ? $passphrase: null)) {
			return false;
		}
		return true;
	}

	public function setPublicKey($publicKey) {
		$this->publicKey = $publicKey;
		$this->_publicKey = openssl_pkey_get_public($publicKey);
		if (!$this->_publicKey) {
			throw new Exception('Public Key Error');
		}
		return true;
	}

	public function setPrivateKey($privateKey, $passphrase = null) {
		$this->privateKey = $privateKey;
		$this->_privateKey = openssl_pkey_get_private($privateKey, $passphrase ? $passphrase : null);
		if (!$this->_privateKey) {
			throw new Exception('Private Key Error');
		}
		return true;
	}

	public function publicEncrypt($data) {
		if (!$this->_publicKey) {
			throw new Exception('The public key is empty');
		}
		if (!openssl_public_encrypt($data, $code, $this->_publicKey, self::PADDING) || !$code) {
			throw new Exception('Encrypt error');
		}
		return base64_encode($code);
	}

	public function publicDecrypt($code) {
		if (!$this->_publicKey) {
			throw new Exception('The public key is empty');
		}
		if (!$code || !($code = base64_decode($code))) {
			return false;
		}
		if (!openssl_public_decrypt($code, $data, $this->_publicKey, self::PADDING)) {
			return false;
		}
		return $data;
	}


	public function privateEncrypt($data) {
		if (!$this->_privateKey) {
			throw new Exception('The private key is empty');
		}
		if (!openssl_private_encrypt($data, $code, $this->_privateKey, self::PADDING) || !$code) {
			throw new Exception('Encrypt error');
		}
		return base64_encode($code);
	}


	public function privateDecrypt($code) {
		if (!$this->_privateKey) {
			throw new Exception('The private key is empty');
		}
		if (!$code || !($code = base64_decode($code))) {
			return false;
		}
		if (!openssl_private_decrypt($code, $data, $this->_privateKey, self::PADDING)) {
			return false;
		}
		return $data;
	}


	public function encrypt($data, $method = 'RC4') {
		if (!$this->_publicKey) {
			throw new Exception('The public key is empty');
		}
		if (!@openssl_seal($data, $code, $ekeys, [$this->_publicKey], $method)) {
			throw new Exception('Encrypt error');
		}
		return [base64_encode($code), base64_encode($ekeys[0])];
	}

	public function decrypt($code, $ekey) {
		if (!$this->_privateKey) {
			throw new Exception('The private key is empty');
		}
		if (!$code || !($code = base64_decode($code))) {
			return false;
		}
		if (!$ekey || !($ekey = base64_decode($ekey))) {
			return false;
		}
		if (!openssl_open($code, $data, $ekey, $this->_privateKey)) {
			return false;
		}
		return $data;
	}
}
