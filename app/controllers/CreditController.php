<?php

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;

use App\Services\CreditService;
use Exception;

class CreditController extends Controller
{
    /**
     * @var CreditService
     */
    protected $creditService;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->creditService = app()->make('CreditService');
    }
    /**
     * Display the credit purchase page with available credit packs.
     */
    public function index(Request $request): Response
    {
        $this->requireLogin('/login?redirect=/credits');
        $user = auth();
        $result = $this->creditService->getActiveCreditPacks();
        $creditPacks = $result['success'] ? $result['data'] : [];

        return $this->view('credits/index', [
            'title' => 'Purchase Credits',
            'user' => $user,
            'creditPacks' => $creditPacks,
            'stripe_publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY')
        ]);
    }

    /**
     * Handle the purchase of credits.
     */
    public function purchase(Request $request): Response
    {
        $this->requireLogin('/login?redirect=/credits');
        if (!csrf_verify('credit_purchase_form')) {
            return $this->json(['error' => 'Invalid security token. Please try again.'], 400);
        }

        $packId = sanitize_input($request->input('pack_id'));
        if (!$packId) {
            return $this->json(['error' => 'Credit pack ID is required.'], 400);
        }

        $user = auth();
        $result = $this->creditService->createStripeCheckoutSession($user->id, $packId);

        if ($result['success']) {
            return $this->json($result['data']);
        } else {
            return $this->json(['error' => $result['message']], 500);
        }
    }

    /**
     * Handle successful credit purchase (redirect from Stripe Checkout).
     */
    public function success(Request $request): Response
    {
        $this->requireLogin('/login');
        $sessionId = sanitize_input($request->query('session_id'));
        $user = auth();

        if (!$sessionId) {
            return $this->redirect('/credits?error=session_missing');
        }

        $result = $this->creditService->processStripeSuccess($sessionId, $user->id);

        if ($result['success']) {
            if (isset($result['data']['already_processed']) && $result['data']['already_processed']) {
                set_flash_message('success', 'Credits already added to your account!');
            } else {
                $creditsAwarded = $result['data']['credits_awarded'] ?? 0;
                set_flash_message('success', $creditsAwarded . ' credits have been successfully added to your account!');
            }
            return $this->redirect('/dashboard');
        } else {
            set_flash_message('error', $result['message']);
            return $this->redirect('/credits?error=processing_failed');
        }
    }

    /**
     * Handle canceled credit purchase.
     */
    public function cancel(Request $request): Response
    {
        $this->requireLogin('/login');
        
        // Set a flash message
        $request->flash('warning', 'Payment was cancelled.');
        
        // Redirect to credits page
        return $this->redirect('/credits');
    }

    /**
     * Display user's credit transaction history.
     *
     * @return Response
     */
    public function history(Request $request): Response
    {
        $this->requireLogin('/login?redirect=/credits/history');
        
        $user = auth();
        $result = $this->creditService->getUserTransactions($user->id);
        
        $transactions = $result['success'] ? $result['data'] : [];
        
        return $this->view('credits/history', [
            'title' => 'Credit History',
            'user' => $user,
            'transactions' => $transactions
        ]);
    }
}