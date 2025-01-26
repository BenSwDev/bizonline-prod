<?php 
class SiteGallery {
	protected $galleryID = 0;

	// gallery data: title, description, etc...
	public $data = [];

	public function __construct($galleryID = 0){
		if ($galleryID){
			$gid = udb::single_value("SELECT `galleryID` FROM `galleries` WHERE `galleryID` = " . intval($galleryID));
			if (!$gid)
				throw new Exception("Cannot load gallery " . $galleryID);

			$this->galleryID = $gid;
		}
	}

	public function create(){
		if ($this->galleryID)
			return true;

		$fields = [
			'siteID'   => intval($this->data['siteID']),
			'domainID' => intval($this->data['domainID']),
			'galleryTitle' => typemap($this->data['galleryTitle'], 'string')
		];

		if (!$fields['siteID'])
			throw new Exception('No siteID');
		if (!$fields['galleryTitle'])
			throw new Exception('No gallery title');
		
		return $this->galleryID = udb::insert('galleries', $fields);
	}

	public function update(){

		if (!$this->galleryID)
			throw new Exception('No galleryID');

		$fields = [
			'galleryTitle' => typemap($this->data['galleryTitle'], 'string')
		];

		udb::update('galleries', $fields, 'galleryID = '.$this->galleryID);

	}

	public function addPictures(array $list){

		foreach($list as $key=>$photo){	
			$fileArr=Array();
			$fileArr['src']=$photo['file'];
			$fileArr['table']="folder";
			$fileArr['ref']=$galID;
			udb::insert("pictures", $fileArr);
		}
	}

	public function deletePictures(array $list){
		
	}

	public function pictures($siteID){


	}

	public function delete(){
	}
}

/*
$gallery = new SiteGallery;

$gallery->data['galleryTitle'] = $_POST['sdakdjalsk'];
$gallery->data['siteID'] = $siteID;
$gallery->data['domainID'] = $domainID;

$gallery->create();

udb::update('rooms', ['galleryID' => $gallery->galleryID], 'roomID = ' . $roomID);

$picList = [12 => [1 => ['title' => '', 'desc' => '']]];

$gallery->addPictures($picList);
*/