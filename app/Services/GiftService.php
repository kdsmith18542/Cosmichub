<?php

namespace App\Services;

use App\Models\Gift;
use App\Models\Plan;
use App\Models\User;
use App\Models\CreditTransaction;
use App\Repositories\GiftRepository;
use App\Repositories\UserRepository;
use App\Services\BaseService;
use App\Services\EmailService;
use App\Services\PaymentService;
use Exception;
use Stripe\StripeClient;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Psr\Log\LoggerInterface;

class GiftService extends BaseService
{
    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    private $emailService;
    private $paymentService;
    private $stripe;
    private $giftRepository;
    private $userRepository;
    
    public function __construct(
        EmailService $emailService, 
        PaymentService $paymentService,
        GiftRepository $giftRepository,
        UserRepository $userRepository,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->emailService = $emailService;
        $this->paymentService = $paymentService;
        $this->giftRepository = $giftRepository;
        $this->userRepository = $userRepository;
        $this->stripe = new StripeClient(getenv('STRIPE_SECRET_KEY'));
        $this->logger = $logger;
    }
    
    /**
     * Get available credit packs for gifting
     */
    public function getAvailableCreditPacks()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM plans 
                WHERE type = 'credit_pack' 
                AND is_active = 1 
                ORDER BY credits ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('Error getting credit packs: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }
    
    /**
     * Process gift purchase
     */
    public function purchaseGift($userId, $planId, $recipientEmail, $recipientName, $giftMessage, $senderName, $paymentMethodId)
    {
        try {
            // Validate input
            if (!$planId || !$recipientEmail || !$recipientName || !$paymentMethodId) {
                return ['success' => false, 'message' => 'Missing required fields'];
            }
            
            if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid recipient email address'];
            }
            
            // Get the plan
            $result = $this->paymentService->getPlan($planId);
            if (!$result['success']) {
                return ['success' => false, 'message' => $result['message']];
            }
            
            $plan = $result['data'];
            if ($plan->credits <= 0) {
                return ['success' => false, 'message' => 'Invalid credit pack selected'];
            }
            
            // Get user info
            $user = $this->getUserById($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Create payment intent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $plan->price * 100, // Convert to cents
                'currency' => 'usd',
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'type' => 'gift_purchase',
                    'user_id' => $userId,
                    'plan_id' => $plan->id,
                    'recipient_email' => $recipientEmail,
                    'recipient_name' => $recipientName
                ]
            ]);
            
            if ($paymentIntent->status === 'succeeded') {
                // Payment successful, create gift record
                $giftCode = $this->generateGiftCode();
                
                $giftId = $this->createGift([
                    'gift_code' => $giftCode,
                    'sender_user_id' => $userId,
                    'sender_name' => $senderName ?: $user['username'],
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
                
                if ($giftId) {
                    // Send gift email to recipient
                    $this->sendGiftEmail($giftId);
                    
                    // Send confirmation email to sender
                    $this->sendGiftConfirmationEmail($user, $recipientName, $recipientEmail, $plan->credits);
                    
                    return [
                        'success' => true,
                        'message' => 'Gift sent successfully!',
                        'gift_code' => $giftCode
                    ];
                } else {
                    return ['success' => false, 'message' => 'Failed to create gift record'];
                }
            } else {
                return ['success' => false, 'message' => 'Payment failed. Please try again.'];
            }
            
        } catch (CardException $e) {
            return ['success' => false, 'message' => 'Payment failed: ' . $e->getError()->message];
        } catch (Exception $e) {
            $this->logger->error('Gift purchase error: ' . $e->getMessage(), ['exception' => $e]);
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    /**
     * Redeem a gift code
     */
    public function redeemGift($giftCode, $userId)
    {
        try {
            // Get gift by code
            $gift = $this->getGiftByCode($giftCode);
            if (!$gift) {
                return ['success' => false, 'message' => 'Invalid gift code'];
            }
            
            // Check if gift is already redeemed
            if ($gift['status'] === 'redeemed') {
                return ['success' => false, 'message' => 'This gift has already been redeemed'];
            }
            
            // Check if gift is expired
            if (strtotime($gift['expires_at']) < time()) {
                return ['success' => false, 'message' => 'This gift has expired'];
            }
            
            // Get user info
            $user = $this->getUserById($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Add credits to user account
            $this->addCreditsToUser($userId, $gift['credits_amount'], 'Gift redemption: ' . $giftCode);
            
            // Mark gift as redeemed
            $this->updateGiftStatus($gift['id'], 'redeemed', $userId);
            
            // Send redemption confirmation email
            $this->sendRedemptionConfirmationEmail($user, $gift);
            
            return [
                'success' => true,
                'message' => 'Gift redeemed successfully!',
                'credits_added' => $gift['credits_amount']
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Gift redemption error: ' . $e->getMessage(), ['exception' => $e]);
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    /**
     * Get gift by code
     */
    public function getGiftByCode($giftCode)
    {
        try {
            return $this->giftRepository->findByCode($giftCode);
        } catch (Exception $e) {
            $this->logger->error('Error getting gift by code: ' . $e->getMessage(), ['exception' => $e]);
            return null;
        }
    }
    
    /**
     * Get gifts sent by user
     */
    public function getGiftsBySender($userId)
    {
        try {
            return $this->giftRepository->getBySender($userId);
        } catch (Exception $e) {
            $this->logger->error('Error getting gifts by sender: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }
    
    /**
     * Generate unique gift code
     */
    private function generateGiftCode()
    {
        do {
            $code = 'GIFT-' . strtoupper(bin2hex(random_bytes(4)));
            $existing = $this->getGiftByCode($code);
        } while ($existing);
        
        return $code;
    }
    
    /**
     * Create gift record
     */
    private function createGift($data)
    {
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            return $this->giftRepository->create($data);
        } catch (Exception $e) {
            $this->logger->error('Error creating gift: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Update gift status
     */
    private function updateGiftStatus($giftId, $status, $redeemedByUserId = null)
    {
        try {
            $updateData = ['status' => $status];
            if ($redeemedByUserId) {
                $updateData['redeemed_by_user_id'] = $redeemedByUserId;
                $updateData['redeemed_at'] = date('Y-m-d H:i:s');
            }
            return $this->giftRepository->update($giftId, $updateData);
        } catch (Exception $e) {
            $this->logger->error('Error updating gift status: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Add credits to user account
     */
    private function addCreditsToUser($userId, $credits, $description)
    {
        try {
            // Update user credits
            $stmt = $this->db->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$credits, $userId]);
            
            // Create credit transaction record
            $stmt = $this->db->prepare("
                INSERT INTO credit_transactions (
                    user_id, amount, type, description, created_at
                ) VALUES (?, ?, 'gift_redemption', ?, NOW())
            ");
            $stmt->execute([$userId, $credits, $description]);
            
            return true;
        } catch (Exception $e) {
            $this->logger->error('Error adding credits to user: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($userId)
    {
        try {
            return $this->userRepository->find($userId);
        } catch (Exception $e) {
            $this->logger->error('Error getting user by ID: ' . $e->getMessage(), ['exception' => $e]);
            return null;
        }
    }
    
    /**
     * Send gift email to recipient
     */
    private function sendGiftEmail($giftId)
    {
        try {
            $gift = $this->getGiftById($giftId);
            if (!$gift) return false;
            
            $subject = "You've received a Cosmic Gift!";
            $message = "
                <h2>You've received a cosmic gift!</h2>
                <p>Hello {$gift['recipient_name']},</p>
                <p>{$gift['sender_name']} has sent you {$gift['credits_amount']} cosmic credits!</p>
                <p><strong>Gift Message:</strong> {$gift['gift_message']}</p>
                <p><strong>Gift Code:</strong> {$gift['gift_code']}</p>
                <p>To redeem your gift, visit our website and enter the gift code above.</p>
                <p>This gift expires on " . date('F j, Y', strtotime($gift['expires_at'])) . "</p>
            ";
            
            return $this->emailService->sendEmail($gift['recipient_email'], $subject, $message);
        } catch (Exception $e) {
            $this->logger->error('Error sending gift email: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Send gift confirmation email to sender
     */
    private function sendGiftConfirmationEmail($user, $recipientName, $recipientEmail, $credits)
    {
        try {
            $subject = "Gift Sent Successfully";
            $message = "
                <h2>Your gift has been sent!</h2>
                <p>Hello {$user['username']},</p>
                <p>Your gift of {$credits} cosmic credits has been successfully sent to {$recipientName} ({$recipientEmail}).</p>
                <p>They will receive an email with instructions on how to redeem their gift.</p>
                <p>Thank you for spreading the cosmic love!</p>
            ";
            
            return $this->emailService->sendEmail($user['email'], $subject, $message);
        } catch (Exception $e) {
            $this->logger->error('Error sending gift confirmation email: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Send redemption confirmation email
     */
    private function sendRedemptionConfirmationEmail($user, $gift)
    {
        try {
            $subject = "Gift Redeemed Successfully";
            $message = "
                <h2>Gift redeemed successfully!</h2>
                <p>Hello {$user['username']},</p>
                <p>You have successfully redeemed {$gift['credits_amount']} cosmic credits!</p>
                <p>Gift from: {$gift['sender_name']}</p>
                <p>Your new credit balance will be updated shortly.</p>
                <p>Enjoy your cosmic journey!</p>
            ";
            
            return $this->emailService->sendEmail($user['email'], $subject, $message);
        } catch (Exception $e) {
            $this->logger->error('Error sending redemption confirmation email: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Get gift by ID
     */
    private function getGiftById($giftId)
    {
        try {
            return $this->giftRepository->find($giftId);
        } catch (Exception $e) {
            $this->logger->error('Error getting gift by ID: ' . $e->getMessage(), ['exception' => $e]);
            return null;
        }
    }
    
    /**
     * Get user's sent gifts
     */
    public function getUserGifts($userId)
    {
        try {
            return $this->giftRepository->getBySender($userId);
        } catch (Exception $e) {
            $this->logger->error('Error getting user gifts: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin($user)
    {
        return isset($user['role']) && $user['role'] === 'admin';
    }
}