<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 18/08/2022
 * Time: 15:59
 */
include_once __DIR__ . "/../../sendgrid/vendor/autoload.php";

class reviewcomment {


    public static function sendEmailSmsforClientsNotifyAboutReviewComment($data){

        $emailBody = '<html>
        <head>
        </head>
        <body>
        <div style="width:100&;float:left;text-align:center;direction:rtl;direction:rtl;font-family:arial;">
            <div style=" min-height:400px;margin:0;padding:0;overflow:hidden;width:600px;margin:auto">
                <div style="margin:0;padding:0;min-height:428px;width:370px;padding:0 20px;margin:0;float:right;height:auto;text-align:right;">
                    <h3 style="text-align:right;font-size:16px;font-weight:bold;font-family:arial;">שלום '.$data['author'].'</h3>
                    <div>'.$data['siteTitle'].' ענה לחוות דעתך על המקום</div>
                    <br />
                    <br />
                    <div><?=$data[\'answer\']?></div>
                    <br />
                    <br />
                    <div><b>אם ברצונך לערוך את חוות דעתך<a href="'.$data['link'].'"> לחץ כאן </a></b></div>
                    <br />
                    <div>מודים לך מראש</div>
                    <div>צוות ביז און ליין מקבוצת ספא פלוס</div>
                </div>
            </div>
        </div>
        </body>
        </html>';

        if($data['phone']) {
            $message = "קיבלת תגובה לחוות הדעת שהשארת באתר" . PHP_EOL . 'לחץ על הקישור הבא לצפייה בתגובה' . PHP_EOL . $data['link'] ;
            Maskyoo::sms($message, $data['phone'], 'BizOnline');
        }
        if($data['email']) {

//            $email = new \SendGrid\Mail\Mail();
            $email = new BizOnlineMailer;
            $email->setFrom("info@bizonline.co.il", 'Bizonline');
            $email->setSubject('קיבלת תגובה על חוות דעת ב ' . $data['siteTitle']);

            $email->addTo($data['email'], $data['author']);
            $email->addContent("text/html", $emailBody);

//            $sendgrid = new \SendGrid('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');
//            $sendgrid->send($email);
            $email->send();
        }


    }

}