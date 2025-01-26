<?php 

namespace Core\Files;

use Imagick;

class Optimizer
{

	protected $tmpFile;
	protected $imageObj;
	protected $name;
	protected $width = 0;
	protected $height = 0;
	protected $compress = true;
	protected $isImageArr = ['image/png', 'image/gif', 'image/jpeg'];

	function __construct($file)
	{
		$this->tmpFile = $file;
		
		$this->name = uniqid(random_int(0, 99));
	}

	protected function getExtention(){
		$getExt = explode('.', $this->tmpFile['name']);
		
		return end($getExt);
	}

	public function isImage(){
		return defined("IT_IS_PICTURE_I_PROMISE") ? true : in_array(mime_content_type($this->tmpFile['tmp_name']), $this->isImageArr);
	}

	public function setName($name){
		$this->name = $name;

		return $this;
	}

	public function setSize($width = 0, $height = 0){
		$this->width = $width;
		$this->height = $height;

		return $this;
	}

	public function uncompress(){
		$this->compress = false;

		return $this;
	}

	protected function resize(){
		if($this->width != 0 || $this->height != 0){
			$this->imageObj->thumbnailImage($this->width, $this->height);

			return $this;
		}

		if($this->imageObj->getImageWidth() > 1920){
			$this->imageObj->thumbnailImage(1920, 0);
		}

		if($this->imageObj->getImageHeight() > 1080){
			$this->imageObj->thumbnailImage(0, 1080);
		}

		return $this;
	}



	protected function compress(){
		if(! $this->compress){
			return $this;
		}

		$this->imageObj->setImageCompression(Imagick::COMPRESSION_JPEG);
		$this->imageObj->setImageCompressionQuality(75);
		$this->imageObj->stripImage();

		return $this;
	}

	protected function reset(){
		$this->width = 0;

		$this->height = 0;

		$this->compress = true;
	}

	public function saveToLocal($path){
		if(! $this->isImage()){
			$this->reset();

			return false;
		}

		$this->imageObj = new Imagick($this->tmpFile['tmp_name']);

		$this->resize()->compress();

		$fullName = $this->name.'.'.$this->getExtention();

		$fullPath = $path.'/'.$fullName;

		if(! $this->imageObj->writeImage($_SERVER['DOCUMENT_ROOT'].'/'.$fullPath)){
			
			$this->reset();

			return false;
		}

		$this->reset();

		return [
			'source' => 'local',
			'name' => $fullName,
			'ssd_db_path' => $fullPath
		];
	}


}
