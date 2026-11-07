<?php

namespace App\Services\Shipping;

use RuntimeException;

/**
 * A shipping rate couldn't be obtained. The message is safe to show customers.
 */
class ShippingQuoteException extends RuntimeException
{
}
