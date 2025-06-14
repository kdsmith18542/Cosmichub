<?php
namespace App\Utils;

// Include PHPMailer files directly since we're not using Composer
require_once __DIR__ . '/../../PHPMailer/Exception.php';
require_once __DIR__ . '/../../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/SMTP.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Psr\Log\LoggerInterface;

class Mailer {
    protected LoggerInterface $logger;
    private $mailer;
    private $templatesPath;
    private $siteUrl;
    private $fromEmail;
    private $fromName;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->mailer = new PHPMailer(true);
        $this->templatesPath = __DIR__ . '/../../templates/emails/';
        $this->siteUrl = rtrim($_ENV['SITE_URL'] ?? 'https://cosmichub.online', '/');
        $this->fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@cosmichub.online';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'CosmicHub';
        
        $this->configureMailer();
    }

    private function configureMailer() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'mail.cosmichub.online';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? 'admin@cosmichub.online';
            $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? 'Kmskes1218!';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use 'tls' or 'ssl'
            $this->mailer->Port = (int)($_ENV['MAIL_PORT'] ?? 465); // 587 for TLS, 465 for SSL
            
            // Debugging (always on for now to help with troubleshooting)
            $this->mailer->SMTPDebug = SMTP::DEBUG_CONNECTION;
            $this->mailer->Debugoutput = function($str, $level) {
                $log = "[PHPMailer $level] $str\n";
                $this->logger->debug($str, ['level' => $level, 'component' => 'PHPMailer']);
                echo $log;
            };

            // Email settings
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->isHTML(true);
            
            // SSL/TLS options
            $this->mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logger->error("Mailer configuration error: " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Send a verification email
     * 
     * @param string $toEmail Recipient email
     * @param string $name Recipient name
     * @param string $token Verification token
     * @return bool True on success, false on failure
     */
    public function sendVerificationEmail($toEmail, $name, $token) {
        try {
            $verificationUrl = $this->siteUrl . "/verify-email?token=" . urlencode($token);
            
            // Load email template
            $template = file_get_contents($this->templatesPath . 'verification-email.html');
            
            // Replace placeholders
            $replacements = [
                '{{name}}' => htmlspecialchars($name),
                '{{email}}' => htmlspecialchars($toEmail),
                '{{verification_url}}' => $verificationUrl,
                '{{year}}' => date('Y')
            ];
            
            $message = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $template
            );
            
            // Set email details
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $name);
            $this->mailer->Subject = 'Verify Your Email - CosmicHub';
            $this->mailer->Body = $message;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $message));
            
            // Send email
            $this->mailer->send();
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to send verification email to $toEmail: " . $e->getMessage(), ['exception' => $e, 'toEmail' => $toEmail]);
            return false;
        }
    }
    
    /**
     * Send a generic email using a template
     * 
     * @param string $toEmail Recipient email
     * @param string $subject Email subject
     * @param string $templateName Template filename (without .html)
     * @param array $data Associative array of template variables
     * @return bool True on success, false on failure
     */
    /**
     * Get the underlying PHPMailer instance
     * 
     * @return PHPMailer The PHPMailer instance
     */
    public function getMailer() {
        return $this->mailer;
    }
    
    /**
     * Send a generic email using a template
     * 
     * @param string $toEmail Recipient email
     * @param string $subject Email subject
     * @param string $templateName Template filename (without .html)
     * @param array $data Associative array of template variables
     * @return bool True on success, false on failure
     */
    public function sendTemplateEmail($toEmail, $subject, $templateName, $data = []) {
        try {
            $templatePath = $this->templatesPath . $templateName . '.html';
            
            if (!file_exists($templatePath)) {
                throw new \Exception("Email template not found: $templateName");
            }
            
            // Load template
            $template = file_get_contents($templatePath);
            
            // Add default replacements
            $replacements = array_merge([
                '{{site_url}}' => $this->siteUrl,
                '{{year}}' => date('Y'),
                '{{email}}' => $toEmail
            ], $data);
            
            // Replace placeholders
            $message = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $template
            );
            
            // Set email details
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $message));
            
            // Send email
            $this->mailer->send();
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to send email to $toEmail: " . $e->getMessage(), ['exception' => $e, 'toEmail' => $toEmail]);
            return false;
        }
    }
}
