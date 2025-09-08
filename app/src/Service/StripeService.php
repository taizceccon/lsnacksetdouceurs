<?php

namespace App\Service;

use Stripe\Stripe;

class StripeService
{
    public function __construct(string $stripeSecretKey)
    {
        Stripe::setApiKey($stripeSecretKey);
    }

    // Exemple de méthode pour créer une session Checkout
    public function createCheckoutSession(array $lineItems, string $successUrl, string $cancelUrl)
    {
        return \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }
}