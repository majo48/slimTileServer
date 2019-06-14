<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 2018-12-21
 * Time: 07:09
 */

namespace App\api;

use PDO;
use Slim\Container;
use PHPMailer\PHPMailer\PHPMailer;

class Register
{
    /** @var Container $container */
    protected   $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function index($request, $response, $args)
    {
        // request log message
        $this->container->logger->info("/register request");

        // check for email
        $email = $request->getQueryParam('email');
        if (!empty($email)){

            // register email in the app
            $guid = $this->getGUID(false); // create GUID
            $errmsg = $this->sendMail($email, $guid); // send email with GUID

            // register log message
            $this->container->logger->info("registered ".$email.' with key '.$guid);

            // persist user info
            $this->registerUser($email, $guid);

            // thank user
            return $this->container->renderer->render($response, 'thanks.phtml', $args);
        }

        // Render register view
        return $this->container->renderer->render($response, 'register.phtml', $args);
    }

    /**
     * Create GUID (globally unique identifier)
     * Credit: Kristof_Polleunis at yahoo dot com
     * @param $email string
     * @return string
     */
    private function getGUID($opt = true)
    {
        if( function_exists('com_create_guid') ){
            // if extension com_dotnet is installed (best)
            if( $opt ){ return com_create_guid(); }
            else { return trim( com_create_guid(), '{}' ); }
        }
        else {
            // else alternative (good enough)
            mt_srand( (double)microtime() * 10000 );
            $charid = strtoupper( md5(uniqid(rand(), true)) );
            $hyphen = chr( 45 );    // "-"
            $left_curly = $opt ? chr(123) : "";     //  "{"
            $right_curly = $opt ? chr(125) : "";    //  "}"
            $uuid = $left_curly
                . substr( $charid, 0, 8 ) . $hyphen
                . substr( $charid, 8, 4 ) . $hyphen
                . substr( $charid, 12, 4 ) . $hyphen
                . substr( $charid, 16, 4 ) . $hyphen
                . substr( $charid, 20, 12 )
                . $right_curly;
            return $uuid;
        }
    }

    /**
     * Send an email to user's email account.
     * @param $email string
     * @param $guid string
     * @return mixed
     */
    private function sendMail($email, $guid)
    {
        $errmsg = null;
        $mailUser = $this->container->settings['custom']['mailUser'];
        $mailPassword = $this->container->settings['custom']['mailPassword'];
        $mailBody =
            'Please find enclosed your OSMAP key: '.$guid.'<br><br>'.
            'For information on how to use this key, please check '.
            'https://github.com/majo48/slimTileServer/wiki'.'<br><br>'.
            'This is an automated message, please do not reply directly '.
            'to this email.<br>'.
            'If you have a problem, please open an issue in '.
            'https://github.com/majo48/slimTileServer/issues';

        /**
         * https://github.com/PHPMailer/PHPMailer/blob/master/examples/gmail.phps
         * This example shows settings to use when sending via Google's Gmail servers.
         * This uses traditional id & password authentication - look at the gmail_xoauth.phps
         * example to see how to use XOAUTH2.
         */
        try{
            //Create a new PHPMailer instance
            $mail = new PHPMailer;

            //Tell PHPMailer to use SMTP
            $mail->isSMTP();

            //Enable SMTP debugging
            // 0 = off (for production use)
            // 1 = client messages
            // 2 = client and server messages
            $mail->SMTPDebug = 0;

            //Set the hostname of the mail server
            $mail->Host = 'smtp.gmail.com';

            // use
            // $mail->Host = gethostbyname('smtp.gmail.com');
            // if your network does not support SMTP over IPv6
            //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
            $mail->Port = 587;

            //Set the encryption system to use - ssl (deprecated) or tls
            $mail->SMTPSecure = 'tls';

            //Whether to use SMTP authentication
            $mail->SMTPAuth = true;

            //Username to use for SMTP authentication - use full email address for gmail
            $mail->Username = $mailUser;

            //Password to use for SMTP authentication
            $mail->Password = $mailPassword;

            //Set who the message is to be sent from
            $mail->setFrom($mailUser);

            //Set an alternative reply-to address
            //$mail->addReplyTo('replyto@example.com', 'First Last');

            //Set who the message is to be sent to
            $mail->addAddress($email);

            //Set the subject line
            $mail->Subject = 'Please find enclosed your new OSMAP key';

            //Read an HTML message body from an external file, convert referenced images to embedded,
            //convert HTML into a basic plain-text alternative body
            //$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
            $mail->msgHTML($mailBody);

            //Replace the plain text body with one created manually
            //$mail->AltBody = 'This is a plain-text message body';

            //Attach an image file
            //$mail->addAttachment('images/phpmailer_mini.png');

            //send the message, check for errors
            if (!$mail->send()) {
                $errmsg = "Mailer Error: " . $mail->ErrorInfo;
            }
        }
        catch (\Exception $e){
            $errmsg = $e->getMessage();
        }
        return $errmsg;
    }

    /**
     * Register the user in the MySQL database.
     * @param string $username
     * @param string $userkey
     */
    private function registerUser($username, $userkey)
    {
        try{
            $datestring = date('Y-m-d H:i:s');
            $quote = '"';
            $pdo = $this->container->get('pdo');
            $stmt = $pdo->query(
                "SELECT * FROM termsofuse ".
                "WHERE username = ".$quote.$username.$quote.';'
            );
            $rows = $stmt->fetch();

            if ($rows===false){
                // the user is not yet registered in the database
                $sql = "INSERT INTO termsofuse ".
                    "(username, userkey, registerdate) ".
                    "VALUES(".$quote.$username.$quote.",".
                    $quote.$userkey.$quote.",".$quote.$datestring.$quote.");"
                ;
                $rows = $pdo->exec($sql);
            }
            else {
                // the user is already registered in the database
                $sql = "UPDATE termsofuse SET".
                    " userkey = ".$quote.$userkey.$quote.','.
                    " registerdate = ".$quote.$datestring.$quote.
                    " WHERE username = ".$quote.$username.$quote
                ;
                $rows = $pdo->exec($sql);
            }
            $this->container->logger->info(
                "persisted ".$username.' with key: '.$userkey
            );
        }
        catch (\Exception $e){
            $this->container->logger->error(
                "persisted: ".$username.' with error: '.$e
            );
        }
    }

}