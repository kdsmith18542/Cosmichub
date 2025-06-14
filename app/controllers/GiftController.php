<?php
/**
 * Gift Controller
 * 
 * Handles gift report functionality - allowing users to purchase credit packs as gifts
 */

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\GiftService;
use Exception;

class GiftController extends Controller
{
    private $giftService;
    
    public function __construct()
    {
        parent::__construct();
        $this->giftService = $this->resolve(GiftService::class);
    }
    
    /**
     * Display gift purchase page
     */
    public function index(Request $request): Response
    {
        $this->requireLogin('/login?redirect=/gift');
        
        $creditPacks = $this->giftService->getAvailableCreditPacks();
        
        return $this->view('gift/index', [
            'title' => 'Send a Cosmic Gift',
            'creditPacks' => $creditPacks
        ]);
    }
    
    /**
     * Process gift purchase
     */
    public function purchase(Request $request): Response
    {
        $this->requireLogin();
        
        $giftData = [
            'plan_id' => $request->input('plan_id'),
            'recipient_email' => trim($request->input('recipient_email', '')),
            'recipient_name' => trim($request->input('recipient_name', '')),
            'gift_message' => trim($request->input('gift_message', '')),
            'sender_name' => trim($request->input('sender_name', '')),
            'payment_method_id' => $request->input('payment_method_id')
        ];
        
        $result = $this->giftService->purchaseGift($giftData, auth());
        
        if ($result['success']) {
            return $this->json([
                'success' => true,
                'message' => $result['message'],
                'gift_code' => $result['gift_code']
            ]);
        } else {
            return $this->json(['error' => $result['message']], $result['status_code'] ?? 400);
        }
    }
    
    /**
     * Display gift redemption page
     */
    public function redeem(Request $request): Response
    {
        $giftCode = $request->query('code', '');
        
        $result = $this->giftService->getGiftDetails($giftCode);
        
        return $this->view('gift/redeem', [
            'title' => 'Redeem Cosmic Gift',
            'gift' => $result['gift'],
            'error' => $result['error'],
            'giftCode' => $giftCode
        ]);
    }
    
    /**
     * Process gift redemption
     */
    public function processRedemption(Request $request): Response
    {
        $giftCode = trim($request->input('gift_code', ''));
        $user = auth();
        
        if (!$user) {
            // Store gift code in session for after login
            $request->setSession('pending_gift_code', $giftCode);
            return $this->json([
                'redirect' => '/register?gift=' . urlencode($giftCode)
            ]);
        }
        
        $result = $this->giftService->redeemGift($giftCode, $user);
        
        if ($result['success']) {
            return $this->json([
                'success' => true,
                'message' => $result['message'],
                'credits_added' => $result['credits_added']
            ]);
        } else {
            return $this->json(['error' => $result['message']], $result['status_code'] ?? 400);
        }
    }
    
    /**
     * Display user's sent gifts
     */
    public function myGifts(Request $request): Response
    {
        $this->requireLogin('/login?redirect=/gift/my-gifts');
        
        $user = auth();
        $gifts = $this->giftService->getUserGifts($user['id']);
        
        return $this->view('gift/my-gifts', [
            'title' => 'My Gifts',
            'gifts' => $gifts
        ]);
    }
}