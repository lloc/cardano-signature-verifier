<?php

declare(strict_types=1);

namespace lloc\CryptoVerifier;

final readonly class VerificationResult
{
    /**
     * @param string[] $warnings
     */
    public function __construct(
        public bool $valid,
        public ?string $message = null,
        public ?string $address = null,
        public ?string $publicKey = null,
        public ?string $stakeKey = null,
        public ?string $algorithm = null,
        public array $warnings = [],
        public ?VerificationErrorCode $errorCode = null,
    ) {
    }

    /**
     * @param string[] $warnings
     */
    public static function valid(
        ?string $message = null,
        ?string $address = null,
        ?string $publicKey = null,
        ?string $stakeKey = null,
        ?string $algorithm = null,
        array $warnings = []
    ): self {

        return new self(true, $message, $address, $publicKey, $stakeKey, $algorithm, $warnings);
    }

    /**
     * @param string[] $warnings
     */
    public static function invalid(VerificationErrorCode $errorCode, array $warnings = []): self
    {
        return new self(false, null, null, null, null, null, $warnings, $errorCode);
    }
}
