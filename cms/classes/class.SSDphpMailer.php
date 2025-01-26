<?php
include_once __DIR__ . '/../../phpmailer_new/src/PHPMailer.php';
include_once __DIR__ . '/../../phpmailer_new/src/Exception.php';

class SSDphpMailer {
    private static $init = false;
    private $mailer;

    public function __construct(){
        if (!self::$init){
            spl_autoload_register(function($name){
                $tmp = explode('\\', $name);

                $path = __DIR__ . '/../../phpmailer_new/src/' . end($tmp) . '.php';
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

    public function useSMTP($host, $user, $pass, $debug = 0){
//Tell PHPMailer to use SMTP
        $this->mailer->isSMTP();
//Enable SMTP debugging
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
        $this->mailer->SMTPDebug = $debug;
//Set the hostname of the mail server
        $this->mailer->Host = $host;
//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $this->mailer->Port = 587;
//Set the encryption system to use - ssl (deprecated) or tls
        $this->mailer->SMTPSecure = 'tls';
//Custom connection options
//Note that these settings are INSECURE
        /*$this->SMTPOptions = array(
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                //'verify_depth' => 3,
                'allow_self_signed' => true,
                //'peer_name' => 'smtp.example.com',
                //'cafile' => '/etc/ssl/ca_cert.pem',
            ],
        );*/
//Whether to use SMTP authentication
        $this->mailer->SMTPAuth = true;
//Username to use for SMTP authentication - use full email address for gmail
        $this->mailer->Username = $user;
//Password to use for SMTP authentication
        $this->mailer->Password = $pass;

        return $this;
    }

    // failsafe for any non-defined method that may already exist in PHPMailer
    public function __call($name, $args){
        if (method_exists($this->mailer, $name))
            return call_user_func_array(array($this->mailer, $name), $args);
        else
            throw new Exception("Unknown method: " . $name);
    }
}
