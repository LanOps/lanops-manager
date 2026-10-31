<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A refund that can't be made, with a message safe to show the admin
 */
class RefundException extends RuntimeException
{
}
