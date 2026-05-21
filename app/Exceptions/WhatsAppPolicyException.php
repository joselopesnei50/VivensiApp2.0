<?php

namespace App\Exceptions;

class WhatsAppPolicyException extends \RuntimeException
{
    public string $policyCode;

    public function __construct(string $reason, string $policyCode, int $httpStatus = 422)
    {
        parent::__construct($reason, $httpStatus);
        $this->policyCode = $policyCode;
    }
}
