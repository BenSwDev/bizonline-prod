<?php
require 'PHPMailerAutoload.php';

class bizonlineMailer extends PHPMailer
{
	public function __construct()
    {
		parent::__construct();

		$this->Priority = 3;
		$this->CharSet = "UTF-8";
		$this->Encoding = "base64";

        $this->Sender = 'info@bizonline.co.il';
		$this->setFrom('info@bizonline.co.il', 'Bizonline');
		$this->setLanguage('he');
		$this->isHTML(true);
		$this->isMail();
    }
}
