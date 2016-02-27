<?php
namespace Loli\Http\Message;
use Psr\Http\Message\UploadedFileInterface;

use GuzzleHttp\Psr7\Stream;

class UploadedFile implements UploadedFileInterface {
	protected $name;
	protected $tmp_name;
	protected $size;
	protected $type;
	protected $error;
	public function __construct($name, $tmp_name = '', $size = 0, $type = 'application/octet-stream', $error = UPLOAD_ERR_OK) {
		$this->name = basename($name);
		$this->tmp_name = $tmp_name;
		$this->size = $size;
		$this->type = $type;
		$this->error = $error;
		if ($this->error === UPLOAD_ERR_OK) {
			$this->size = filesize($this->tmp_name);
			if (!is_file($this->tmp_name)) {
				throw new \InvalidArgumentException( __METHOD__ . '() Upload file does not exist');
			}
		}
	}

	public function getStream() {
		if ($this->error !== UPLOAD_ERR_OK) {
			throw new \InvalidArgumentException( __METHOD__ . '() File upload error');
		}
		return new Stream($this->tmp_name);
	}

	public function moveTo($targetPath) {
		if ($this->error !== UPLOAD_ERR_OK) {
			throw new \InvalidArgumentException( __METHOD__ . '() File upload error');
		}
		if(!is_uploaded_file($this->tmp_name)) {
			throw new \InvalidArgumentException(sprintf('Invalid temporary file "%s".', $this->tmp_name));
		}
		return move_uploaded_file($this->tmp_name, $targetPath);
	}

	public function getSize() {
		return $this->size;
	}

	public function getError() {
		return $this->error;
	}

	public function getClientFilename() {
		return $this->name;
	}

	public function getClientMediaType() {
		if ($this->error === UPLOAD_ERR_OK) {
			$finfo = new \finfo(FILEINFO_MIME);
			return $finfo->file($this->tmp_name);
		}
		return $this->type;
	}
	public function __toString(){
		return $this->getClientFilename();
	}
}
