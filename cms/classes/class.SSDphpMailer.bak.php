<?php
include_once __DIR__ . '/../../phpmailer_new/src/PHPMailer.php';
include_once __DIR__ . '/../../phpmailer_new/src/Exception.php';

class SSDphpMailer {
    private static $init = false;
    private $mailer;

    public function __construct(){
        if (!self::$init){
            spl_autoload_register(function($name){
                $path = __DIR__ . '/../../phpmailer_new/src/' . basename($name) . '.php';
                if (file_exists($path)){
                    include $path;
                }
            });

            self::$init = true;
        }

        $this->mailer = new \PHPMailer\PHPMailer\PHPMailer(true);

        $this->mailer->CharSet  = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
        $this->mailer->Encoding = \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64;
        $this->mailer->setLanguage('he');
        $this->mailer->isHTML(true);
        $this->mailer->isMail();
    }

    public function setFrom($email, $name = ''){
        $this->mailer->setFrom($email, $name);
    }

    public function setSubject($subj){
        $this->mailer->Subject = $subj;
    }

    public function addTo($email, $name = ''){
        $this->mailer->addAddress($email, $name);
    }

    public function addContent($enc, $content){
        $this->mailer->isHTML($enc == 'text/html');

        $this->mailer->Body    = $content;
        $this->mailer->AltBody = Html2Text::convert($content);
    }

    public function send(){
        return $this->mailer->send();
    }


    // failsafe for any non-defined method that may already exist in PHPMailer
    public function __call($name, $args){
        if (method_exists($this->mailer, $name))
            return call_user_func_array(array($this->mailer, $name), $args);
        else
            throw new Exception("Unknown method: " . $name);
    }
}
