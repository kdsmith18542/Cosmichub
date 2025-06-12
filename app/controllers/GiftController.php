<?php
/**
 * Gift Controller
 * 
 * Handles gift report functionality - allowing users to purchase credit packs as gifts
 */

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\User;
use App\Models\CreditTransaction;
use App\Models\Plan;
use App\Models\Gift;
use App\Libraries\Database;
use App\Services\EmailService;
use Exception;
use Stripe\StripeClient;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;

class GiftController extends BaseController
{
    /**
     * Display the gift purchase page
     */
    public function index()
    {
        $this->requireLogin('/login?redirect=/gift');
        
        // Get available credit packs for gifting
        $creditPacks = Plan::where('credits', '>', 0)->get();
        
        $this->view('gift/index', [
            'title' => 'Gift a Cosmic Report',
            'creditPacks' => $creditPacks,
            'stripe_publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY')
        ]);
    }
    
    /**
     * Process gift purchase
     */
    public function purchase()
    {
        $this->requireLogin();
        
        try {
            // Validate input
            $planId = $_POST['plan_id'] ?? null;
            $recipientEmail = trim($_POST['recipient_email'] ?? '');
            $recipientName = trim($_POST['recipient_name'] ?? '');
            $giftMessage = trim($_POST['gift_message'] ?? '');
            $senderName = trim($_POST['sender_name'] ?? '');
            $paymentMethodId = $_POST['payment_method_id'] ?? null;
            
            if (!$planId || !$recipientEmail || !$recipientName || !$paymentMethodId) {
                return $this->jsonResponse(['error' => 'Missing required fields'], 400);
            }
            
            if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse(['error' => 'Invalid recipient email address'], 400);
            }
            
            // Get the plan
            $plan = Plan::find($planId);
            if (!$plan || $plan->credits <= 0) {
                return $this->jsonResponse(['error' => 'Invalid credit pack selected'], 400);
            }
            
            $user = auth();
            $stripe = new StripeClient(getenv('STRIPE_SECRET_KEY'));
            
            // Create payment intent
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $plan->price * 100, // Convert to cents
                'currency' => 'usd',
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'type' => 'gift_purchase',
                    'user_id' => $user['id'],
                    'plan_id' => $plan->id,
                    'recipient_email' => $recipientEmail,
                    'recipient_name' => $recipientName
                ]
            ]);
            
            if ($paymentIntent->status === 'succeeded') {
                // Payment successful, create gift record
                $giftCode = $this->generateGiftCode();
                
                $giftId = Gift::create([
                    'gift_code' => $giftCode,
                    'sender_user_id' => $user['id'],
                    'sender_name' => $senderName ?: $user['name'],
                    'recipient_email' => $recipientEmail,
                    'recipient_name' => $recipientName,
                    'gift_message' => $giftMessage,
                    'credits_amount' => $plan->credits,
                    'plan_id' => $plan->id,
                    'purchase_amount' => $plan->price,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'status' => 'pending',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
                ]);
                
                // Send gift email to recipient
                $this->sendGiftEmail($giftId);
                
                // Send confirmation email to sender
                $this->sendGiftConfirmationEmail($user, $recipientName, $recipientEmail, $plan->credits);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Gift sent successfully!',
                    'gift_code' => $giftCode
                ]);
            } else {
                return $this->jsonResponse(['error' => 'Payment failed. Please try again.'], 400);
            }
            
        } catch (CardException $e) {
            return $this->jsonResponse(['error' => 'Payment failed: ' . $e->getError()->message], 400);
        } catch (Exception $e) {
            error_log('Gift purchase error: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'An error occurred. Please try again.'], 500);
        }
    }
    
    /**
     * Display gift redemption page
     */
    public function redeem($giftCode = null)
    {
        if (!$giftCode) {
            $giftCode = $_GET['code'] ?? '';
        }
        
        $gift = null;
        $error = null;
        
        if ($giftCode) {
            $gift = Gift::where('gift_code', $giftCode)->first();
            
            if (!$gift) {
                $error = 'Invalid gift code';
            } elseif ($gift->status === 'redeemed') {
                $error = 'This gift has already been redeemed';
            } elseif (strtotime($gift->expires_at) < time()) {
                $error = 'This gift has expired';
            }
        }
        
        $this->view('gift/redeem', [
            'title' => 'Redeem Gift',
            'gift' => $gift,
            'error' => $error,
            'gift_code' => $giftCode
        ]);
    }
    
    /**
     * Process gift redemption
     */
    public function processRedemption()
    {
        try {
            $giftCode = trim($_POST['gift_code'] ?? '');
            
            if (!$giftCode) {
                return $this->jsonResponse(['error' => 'Gift code is required'], 400);
            }
            
            $gift = Gift::where('gift_code', $giftCode)->first();
            
            if (!$gift) {
                return $this->jsonResponse(['error' => 'Invalid gift code'], 400);
            }
            
            if ($gift->status === 'redeemed') {
                return $this->jsonResponse(['error' => 'This gift has already been redeemed'], 400);
            }
            
            if (strtotime($gift->expires_at) < time()) {
                return $this->jsonResponse(['error' => 'This gift has expired'], 400);
            }
            
            // Check if user is logged in
            $user = auth();
            if (!$user) {
                // Store gift code in session and redirect to registration
                $_SESSION['pending_gift_code'] = $giftCode;
                return $this->jsonResponse([
                    'redirect' => '/register?gift=' . urlencode($giftCode)
                ]);
            }
            
            // Redeem the gift
            Database::getInstance()->beginTransaction();
            
            try {
                // Add credits to user account
                User::addCredits($user['id'], $gift->credits_amount);
                
                // Create credit transaction record
                CreditTransaction::create([
                    'user_id' => $user['id'],
                    'type' => 'gift_redemption',
                    'amount' => $gift->credits_amount,
                    'description' => 'Gift redemption from ' . $gift->sender_name,
                    'reference_id' => $gift->id
                ]);
                
                // Update gift status
                Gift::update($gift->id, [
                    'status' => 'redeemed',
                    'redeemed_by_user_id' => $user['id'],
                    'redeemed_at' => date('Y-m-d H:i:s')
                ]);
                
                Database::getInstance()->commit();
                
                // Send thank you email to recipient
                $this->sendRedemptionThankYouEmail($user, $gift);
                
                // Send notification to sender
                $this->sendRedemptionNotificationEmail($gift, $user);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Gift redeemed successfully! ' . $gift->credits_amount . ' credits have been added to your account.',
                    'credits_added' => $gift->credits_amount
                ]);
                
            } catch (Exception $e) {
                Database::getInstance()->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('Gift redemption error: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'An error occurred while redeeming the gift. Please try again.'], 500);
        }
    }
    
    /**
     * Display user's sent gifts
     */
    public function myGifts()
    {
        $this->requireLogin('/login?redirect=/gift/my-gifts');
        
        $user = auth();
        $gifts = Gift::where('sender_user_id', $user['id'])
                    ->orderBy('created_at', 'DESC')
                    ->get();
        
        $this->view('gift/my-gifts', [
            'title' => 'My Gifts',
            'gifts' => $gifts
        ]);
    }
    
    /**
     * Generate unique gift code
     */
    private function generateGiftCode()
    {
        do {
            $code = 'COSMIC-' . strtoupper(bin2hex(random_bytes(4)));
        } while (Gift::where('gift_code', $code)->first());
        
        return $code;
    }
    
    /**
     * Send gift email to recipient
     */
    private function sendGiftEmail($giftId)
    {
        $gift = Gift::find($giftId);
        if (!$gift) return;
        
        $emailService = new EmailService();
        
        $subject = '🎁 You\'ve received a cosmic gift from ' . $gift->sender_name . '!';
        
        $redeemUrl = getenv('APP_URL') . '/gift/redeem?code=' . urlencode($gift->gift_code);
        
        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 20px; border-radius: 15px;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <h1 style='margin: 0; font-size: 28px;'>🌟 Cosmic Gift Alert! 🌟</h1>
            </div>
            
            <div style='background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px; margin-bottom: 30px;'>
                <h2 style='margin-top: 0; color: #fff;'>Hello {$gift->recipient_name}!</h2>
                <p style='font-size: 18px; line-height: 1.6;'>
                    <strong>{$gift->sender_name}</strong> has sent you a special cosmic gift - 
                    <strong>{$gift->credits_amount} credits</strong> to explore your cosmic blueprint!
                </p>
                
                " . ($gift->gift_message ? "<div style='background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #fff;'>Personal Message:</h3>
                    <p style='font-style: italic; font-size: 16px;'>\"" . htmlspecialchars($gift->gift_message) . "\"</p>
                </div>" : "") . "
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$redeemUrl}' style='display: inline-block; background: #fff; color: #667eea; padding: 15px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 18px;'>
                        🎁 Claim Your Gift
                    </a>
                </div>
                
                <p style='font-size: 14px; opacity: 0.9;'>
                    Gift Code: <strong>{$gift->gift_code}</strong><br>
                    Expires: " . date('F j, Y', strtotime($gift->expires_at)) . "
                </p>
            </div>
            
            <div style='text-align: center; font-size: 14px; opacity: 0.8;'>
                <p>Discover your cosmic blueprint at CosmicHub.online</p>
            </div>
        </div>
        ";
        
        $emailService->send($gift->recipient_email, $subject, $message);
    }
    
    /**
     * Send confirmation email to gift sender
     */
    private function sendGiftConfirmationEmail($sender, $recipientName, $recipientEmail, $credits)
    {
        $emailService = new EmailService();
        
        $subject = '✨ Your cosmic gift has been sent!';
        
        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #667eea;'>Gift Sent Successfully! ✨</h2>
            
            <p>Hi {$sender['name']},</p>
            
            <p>Your cosmic gift has been successfully sent to <strong>{$recipientName}</strong> ({$recipientEmail}).</p>
            
            <div style='background: #f8f9ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #667eea;'>Gift Details:</h3>
                <ul>
                    <li><strong>Recipient:</strong> {$recipientName}</li>
                    <li><strong>Credits:</strong> {$credits}</li>
                    <li><strong>Status:</strong> Delivered</li>
                </ul>
            </div>
            
            <p>They'll receive an email with instructions on how to redeem their gift. Thank you for spreading cosmic joy! 🌟</p>
            
            <p>Best regards,<br>The CosmicHub Team</p>
        </div>
        ";
        
        $emailService->send($sender['email'], $subject, $message);
    }
    
    /**
     * Send thank you email after gift redemption
     */
    private function sendRedemptionThankYouEmail($user, $gift)
    {
        $emailService = new EmailService();
        
        $subject = '🎉 Welcome to your cosmic journey!';
        
        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #667eea;'>Welcome to CosmicHub! 🎉</h2>
            
            <p>Hi {$user['name']},</p>
            
            <p>You've successfully redeemed your cosmic gift from <strong>{$gift->sender_name}</strong>!</p>
            
            <div style='background: #f8f9ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #667eea;'>Your Account:</h3>
                <ul>
                    <li><strong>Credits Added:</strong> {$gift->credits_amount}</li>
                    <li><strong>Ready to explore:</strong> Your cosmic blueprint awaits!</li>
                </ul>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . getenv('APP_URL') . "/dashboard' style='display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 50px; font-weight: bold;'>
                    Start Your Cosmic Journey
                </a>
            </div>
            
            <p>Best regards,<br>The CosmicHub Team</p>
        </div>
        ";
        
        $emailService->send($user['email'], $subject, $message);
    }
    
    /**
     * Send notification to sender when gift is redeemed
     */
    private function sendRedemptionNotificationEmail($gift, $redeemer)
    {
        $sender = User::find($gift->sender_user_id);
        if (!$sender) return;
        
        $emailService = new EmailService();
        
        $subject = '🎁 Your cosmic gift has been redeemed!';
        
        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #667eea;'>Great News! 🎁</h2>
            
            <p>Hi {$sender['name']},</p>
            
            <p><strong>{$gift->recipient_name}</strong> has redeemed the cosmic gift you sent them!</p>
            
            <div style='background: #f8f9ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #667eea;'>Gift Details:</h3>
                <ul>
                    <li><strong>Redeemed by:</strong> {$redeemer['name']}</li>
                    <li><strong>Credits:</strong> {$gift->credits_amount}</li>
                    <li><strong>Redeemed on:</strong> " . date('F j, Y') . "</li>
                </ul>
            </div>
            
            <p>Thank you for sharing the cosmic love! 🌟</p>
            
            <p>Best regards,<br>The CosmicHub Team</p>
        </div>
        ";
        
        $emailService->send($sender['email'], $subject, $message);
    }
}