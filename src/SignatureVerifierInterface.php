<?php

declare(strict_types=1);

namespace lloc\CryptoVerifier;

interface SignatureVerifierInterface
{
    public function verify(VerifySignatureCommand $command): VerificationResult;
}
