<?php

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\PaymentService;
use App\Services\UserService;
use App\Services\AuthService;
use App\Services\SubscriptionService;
use App\Core\View\View;
use Psr\Log\LoggerInterface;
use Exception;

class SubscriptionController extends Controller
{
    private PaymentService $paymentService;
    private UserService $userService;
    private AuthService $authService;
    private SubscriptionService $subscriptionService;
    
    public function __construct(
        PaymentService $paymentService,
        UserService $userService,
        AuthService $authService,
        SubscriptionService $subscriptionService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->paymentService = $paymentService;
        $this->userService = $userService;
        $this->authService = $authService;
        $this->subscriptionService = $subscriptionService;
        $this->logger = $logger;
    }
    /**
     * Display the user's current subscription status and management options.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        if (!$this->authService->isLoggedIn($request)) {
            return $response->redirect('/login?redirect=/subscription');
        }
        
        try {
            $user = $this->authService->getCurrentUser($request);
            $subscription = null;

            if ($user) {
                $result = $this->subscriptionService->getUserActiveSubscription($user->id);
                if ($result['success']) {
                    $subscription = $result['data'];
                }
            }

            return $response->render('subscription/index', [
                'title' => 'My Subscription',
                'user' => $user,
                'subscription' => $subscription
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Failed to load subscription page: ' . $e->getMessage());
            $request->flash('error', 'Failed to load subscription information. Please try again later.');
            return $response->redirect('dashboard');
        }
    }

    /**
     * Handle the subscription creation process.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function subscribe(Request $request, Response $response): Response
    {
        // This method is likely a placeholder or legacy.
        // Subscription initiation is handled by PaymentController@subscribeToPlan via /subscription/checkout/{planId}
        // Redirect users to the plan selection page if they land here directly.
        return $response->redirect('/payment/plans');
    }

    /**
     * Handle subscription cancellation.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function cancel(Request $request, Response $response): Response
    {
        if (!$this->authService->isLoggedIn($request)) {
            return $response->redirect('/login?redirect=/subscription');
        }
        
        if (!$this->verifyCsrfToken($request, 'subscription_cancel')) {
            $request->flash('error_message', 'Invalid security token. Please try again.');
            return $response->redirect('/subscription');
        }

        try {
            $user = $this->authService->getCurrentUser($request);
            
            // Get user's active subscription
            $result = $this->subscriptionService->getUserActiveSubscription($user['id']);
            if (!$result['success']) {
                $request->flash('error_message', 'No active subscription found to cancel.');
                return $response->redirect('/subscription');
            }
            
            $subscription = $result['data'];
            
            // Cancel subscription using PaymentService
            $cancelResult = $this->paymentService->cancelSubscription($subscription['id']);
            
            if ($cancelResult['success']) {
                $request->flash('success_message', 'Your subscription has been set to cancel at the end of the current billing period.');
            } else {
                $request->flash('error_message', $cancelResult['message'] ?? 'Failed to cancel subscription. Please try again.');
            }

        } catch (Exception $e) {
            $this->logger->error('Subscription Cancellation Error: ' . $e->getMessage());
            $request->flash('error_message', 'An unexpected error occurred while canceling your subscription.');
        }

        return $response->redirect('/subscription');
    }
}