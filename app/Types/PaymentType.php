<?php

namespace App\Types;

enum PaymentType: string
{
    case APPLE = "Apple";
    case GOOGLE_PLAY = "Google Play";
    case STRIPE = "Stripe";
}
