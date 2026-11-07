<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a sale or adjustment would take stock below zero.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly string $itemName, public readonly int $available, public readonly int $requested)
    {
        parent::__construct($available > 0
            ? "Only {$available} of {$itemName} left in stock."
            : "{$itemName} is out of stock.");
    }
}
