<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class Post2Email{

    private string $version = "1.0.0";
    private array $defaultMailConfig = array(
        'SMTPDebug' => 0,
        'isSMTP' => true,
        'Host' => '',
        'SMTPAuth' => true,
        'Username' => '',
        'Password' => '',
        'SMTPSecure' => 'tls',
        'Port' => 465,
        'From' => '',
        'FromName' => 'Post2Email',
        'To' => '',
    );
    private array $mailConfig = [];
    private array $json;


    public function __construct(array $mailConfig, array $allowedKeys)
    {
        // require composer autoload file
        if(!file_exists(__DIR__ .'/../vendor/autoload.php')) {
            $this->sendError('/vendor/autoload.php not found. Please run `composer install`.');
            die();
        }
        require_once(__DIR__ .'/../vendor/autoload.php');

        // check if phpMailer was installed
        $phpMailerInstalled = \Composer\InstalledVersions::isInstalled('phpmailer/phpmailer');
        if(!$phpMailerInstalled){
            $this->sendError('PHPMailer seems not to be installed. Please run `composer install`.');
            die();
        }

        // check if a valid key was given
        if(!$_GET || !array_key_exists('key', $_GET) || !in_array($_GET['key'], $allowedKeys)){
            $this->sendError('No valid key given.');
            die();
        }

        // store given $mailConfig and $allowedKeys for later use
        // - merge the default mail config with the provided mail config
        $this->mailConfig = array_merge($this->defaultMailConfig, $mailConfig);

        // store given JSON post (if any)
        $this->json = json_decode(file_get_contents('php://input'),true);

        // execute ...
        $this->execute();
    }

    /**
     * Processes the json input and
     * prepares parameter for sendEmail()
     *
     * @return void
     */
    private function execute():void
    {
        $json = $this->json;
        $sender = $_SERVER['REMOTE_ADDR'];
        $message = "";
        $prio = 0;
        if(!$json  || is_null($json) || !is_array($json)) {
            $this->sendError('No JSON POST found');
            die();
        }

        // check if we have at least a message (msg)
        if(!array_key_exists('msg', $json)){
            $this->sendError('No message (msg) found in POST');
            die();
        }
        $message = strval($json['msg']);

        // replace default sender name (REMOTE_ADDR) if sender (sdr) given
        if(array_key_exists('sdr', $json)){
            $sender = strval($json['sdr']);
        }

        // check if a valid priority (prio) was given
        if(array_key_exists('prio', $json) && intval($json['prio'])<4){
            $prio = intval($json['prio']);
        }

        $this->sendEmail($sender, $message, $prio);
    }

    /**
     * Prepares email subject and body,
     * finally sends mail using PHPMailer
     *
     * @param string $sender
     * @param string $message
     * @param int $prio
     * @return void
     */
    private function sendEmail(string $sender, string $message, int $prio):void
    {
        //var_dump($sender." -- ".$message." -- ".$prio);
        if($prio>3) $prio = 3;

        switch($prio){
            case 3:
                $prioIcon = "🔴";
                $prioText = "critical";
                break;
            case 2:
                $prioIcon = "🟠";
                $prioText = "high";
                break;
            case 1:
                $prioIcon = "🟡";
                $prioText = "low";
                break;
            default: // 0
                $prioIcon = "🟢";
                $prioText = "none";
                break;
        }

        // create subject and mail body
        $subject = "{$prioIcon} [{$sender}] {$message}";
        $body = "SENDER:\n{$sender}\n\n";
        $body.= "PRIORITY:\n{$prioIcon} ".strtoupper($prioText)." ({$prio})\n\n\n";
        $body.= "MESSAGE:\n{$message}\n\n\n\n";
        $body.= "---\n";
        $body.= "Message sent ".date('Y-m-d h:i:s')." via Post2Email v{$this->version} on {$_SERVER['SERVER_ADDR']}.";

        // handling SMTPSecure settings
        if($this->mailConfig['SMTPSecure'] == 'tls'){
            $this->mailConfig['SMTPSecure'] = PHPMailer::ENCRYPTION_STARTTLS;
        }else if($this->mailConfig['SMTPSecure'] == 'ssl'){
            $this->mailConfig['SMTPSecure'] = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = $this->mailConfig['SMTPDebug'];                      // Enable verbose debug output
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = $this->mailConfig['Host'];                    // Set the SMTP server to send through
            $mail->SMTPAuth   = $this->mailConfig['SMTPAuth'];                                   // Enable SMTP authentication
            $mail->Username   = $this->mailConfig['Username'];                     // SMTP username
            $mail->Password   = $this->mailConfig['Password'];                               // SMTP password
            $mail->SMTPSecure = $this->mailConfig['SMTPSecure'];         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
            $mail->Port       = $this->mailConfig['Port'];                                    // TCP port to connect to
            $mail->CharSet    = 'UTF-8';

            //Recipients
            $mail->setFrom($this->mailConfig['From'], $this->mailConfig['FromName']);
            $mail->addAddress($this->mailConfig['To']);     // Add a recipient

            // Content
            $mail->isHTML(false);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            $this->sendSuccess('Mail sent.');
        } catch (Exception $e) {
            $this->sendError('Mail could not be sent!');
        }
        // nothing more to do
        die();
    }

    /**
     * Sends a JSON response to the client
     *
     * @param string $state
     * @param string $msg
     * @return void
     */
    private function sendJsonResponse(string $state, string $msg):void
    {
        switch ($state){
            case "error":
                $httpStatusCode = 400;
                break;
            default:
                $httpStatusCode = 200;
        }
        //$data = /** whatever you're serializing **/;
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($httpStatusCode);
        echo json_encode([
            'state' => $state,
            'msg' => $msg
        ]);
        die();
    }

    /**
     * A wrapper for sendJsonResponse()
     * only for sending error messages
     *
     * @param string $msg
     * @return void
     */
    private function sendError(string $msg):void
    {
        $this->sendJsonResponse('error', $msg);
    }

    /**
     * A wrapper for sendJsonResponse()
     * only for sending success messages
     *
     * @param string $msg
     * @return void
     */
    private function sendSuccess(string $msg):void
    {
        $this->sendJsonResponse('success', $msg);
    }

}