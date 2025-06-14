<?php
namespace App\Libraries;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP as PHPMailerSMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Psr\Log\LoggerInterface;

class MailService {
    protected LoggerInterface $logger;
    /** @var PHPMailer */
    private $mailer;
    
    /** @var string */
    private $fromEmail;
    
    /** @var string */
    private $fromName;
    
    /**
     * Constructor
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->mailer = new PHPMailer(true);
        $this->fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@cosmichub.online';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'CosmicHub';
        
        $this->configureMailer();
    }
    
    /**
     * Configure the mailer with settings from environment
     */
    private function configureMailer() {
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
        $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $this->mailer->Port = $_ENV['MAIL_PORT'] ?? 2525;
        
        // Debug mode
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            $this->mailer->SMTPDebug = PHPMailerSMTP::DEBUG_SERVER;
        }
        
        // Set from email
        $this->mailer->setFrom($this->fromEmail, $this->fromName);
    }
    
    /**
     * Send an email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string|null $altBody Plain text version
     * @return bool
     */
    public function send($to, $subject, $body, $altBody = null) {
        try {
            // Reset all addresses and attachments
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($to);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = $altBody ?? strip_tags($body);
            
            $this->mailer->send();
            return true;
            
        } catch (PHPMailerException $e) {
            $this->logger->error('Mailer Error: ' . $this->mailer->ErrorInfo, ['component' => 'PHPMailer']);
            return false;
        } catch (Exception $e) {
            $this->logger->error('Mail Error: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Send an email verification link
     * 
     * @param string $to Recipient email
     * @param string $name Recipient name
     * @param string $verificationUrl Verification URL
     * @return bool
     */
    public function sendVerificationEmail($to, $name, $verificationUrl) {
        $subject = 'Verify Your Email Address';
        
        $body = $this->getVerificationEmailTemplate([
            'name' => $name,
            'verification_url' => $verificationUrl
        ]);
        
        return $this->send($to, $subject, $body);
    }
    
    /**
     * Get the verification email template
     * 
     * @param array $data Template data
     * @return string
     */
    private function getVerificationEmailTemplate($data) {
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/emails/verification.php';
        return ob_get_clean();
    }
}
