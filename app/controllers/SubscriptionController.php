<?php

namespace App\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use Stripe\StripeClient;
use Exception;

class SubscriptionController extends BaseController
{
    /**
     * Display the user's current subscription status and management options.
     */
    public function index()
    {
        $this->requireLogin('/login?redirect=/subscription');
        $user = auth();
        $subscription = null;

        if ($user) {
            // Fetch the most recent subscription for the user
            // Prioritize active, then trialing, then non-expired canceled ones to show relevant info
            $subscription = Subscription::where('user_id', $user->id)
                                ->orderBy('created_at', 'desc') // Get the latest one
                                ->first();
            
            // If a subscription is found, ensure its plan is loaded for display
            if ($subscription) {
                $subscription->plan(); // This loads the plan relationship if not already loaded
            }
        }

        $this->view('subscription/index', [
            'title' => 'My Subscription',
            'user' => $user,
            'subscription' => $subscription
        ]);
    }

    /**
     * Handle the subscription creation process.
     */
    public function subscribe()
    {
        // This method is likely a placeholder or legacy.
        // Subscription initiation is handled by PaymentController@subscribeToPlan via /subscription/checkout/{planId}
        // Redirect users to the plan selection page if they land here directly.
        $this->redirect('/payment/plans');
    }

    /**
     * Handle subscription cancellation.
     */
    public function cancel()
    {
        $this->requireLogin('/login?redirect=/subscription');
        $user = auth();

        if (!csrf_verify('subscription_cancel', false)) { // Assuming a CSRF token for this action
            $this->setFlash('error_message', 'Invalid security token. Please try again.');
            $this->redirect('/subscription');
            return;
        }

        $subscription = Subscription::where('user_id', $user->id)
                            ->where('status', Subscription::STATUS_ACTIVE)
                            ->orderBy('created_at', 'desc') // Get the latest active one
                            ->first();

        if (!$subscription) {
            $this->setFlash('error_message', 'No active subscription found to cancel.');
            $this->redirect('/subscription');
            return;
        }

        if (empty(getenv('STRIPE_SECRET_KEY'))) {
            error_log('Stripe secret key is not set in the environment variables.');
            $this->setFlash('error_message', 'Payment system configuration error. Cannot cancel subscription.');
            $this->redirect('/subscription');
            return;
        }

        $stripe = new StripeClient(getenv('STRIPE_SECRET_KEY'));

        try {
            // Option 1: Cancel immediately
            // $stripe->subscriptions->cancel($subscription->stripe_subscription_id, []);
            // $subscription->status = Subscription::STATUS_CANCELED;
            // $subscription->canceled_at = date('Y-m-d H:i:s');
            // $subscription->ends_at = date('Y-m-d H:i:s'); // Or keep original end_date if Stripe handles pro-rata

            // Option 2: Cancel at period end (Stripe default behavior when deleting a subscription)
            // Or, to explicitly set cancel_at_period_end:
            $stripeSubscription = $stripe->subscriptions->update($subscription->stripe_subscription_id, [
                'cancel_at_period_end' => true,
            ]);

            // Update local subscription record
            // The status remains 'active' until period_end, but we mark it as canceled locally
            $subscription->canceled_at = date('Y-m-d H:i:s'); 
            // Stripe webhooks should ideally handle the final status update to 'canceled' when period_end is reached.
            // For now, we can reflect the intent to cancel.
            // If Stripe's cancel_at_period_end is true, Stripe will set status to 'canceled' at current_period_end.
            // We might want a local status like 'pending_cancellation' or rely on canceled_at and ends_at.
            // For simplicity, we'll use the model's cancel method which sets canceled_at.
            
            // The model's cancel() method by default sets status to active and updates canceled_at.
            // If we want to reflect Stripe's cancel_at_period_end behavior, this is appropriate.
            $subscription->cancel(true); // true for at_period_end

            // If Stripe's API call for cancel_at_period_end directly updates the status to 'canceled' in Stripe's response
            // (which it usually doesn't, it sets cancel_at_period_end=true and status remains active until period end),
            // then we would update our local status accordingly:
            // $subscription->status = $stripeSubscription->status; 
            // $subscription->ends_at = date('Y-m-d H:i:s', $stripeSubscription->current_period_end);
            // $subscription->canceled_at = date('Y-m-d H:i:s', $stripeSubscription->canceled_at); // if Stripe sets this immediately

            if ($subscription->save()) {
                $this->setFlash('success_message', 'Your subscription has been set to cancel at the end of the current billing period.');
            } else {
                $this->setFlash('error_message', 'Could not update subscription status locally. Please contact support.');
            }

        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API Error during cancellation: ' . $e->getMessage());
            $this->setFlash('error_message', 'Could not cancel subscription with payment provider: ' . $e->getError()->message);
        } catch (Exception $e) {
            error_log('Subscription Cancellation Error: ' . $e->getMessage());
            $this->setFlash('error_message', 'An unexpected error occurred while canceling your subscription.');
        }

        $this->redirect('/subscription');
    }
}