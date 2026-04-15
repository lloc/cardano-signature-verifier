<?php

declare(strict_types=1);

namespace lloc\CardanoSignatureVerifier;

final readonly class VerifySignatureCommand
{
    public function __construct(
        public string $message,
        public string $signature,
        public string $publicKey,
        public ?string $address = null,
        public ?string $walletName = null,
        public ?string $format = 'cip30'
    ) {
    }
}
