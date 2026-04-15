<?php

declare(strict_types=1);

namespace lloc\CardanoSignatureVerifier;

interface SignatureVerifierInterface
{
    public function verify(VerifySignatureCommand $command): VerificationResult;
}
