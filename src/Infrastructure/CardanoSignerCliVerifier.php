<?php

declare(strict_types=1);

namespace lloc\CryptoVerifier\Infrastructure;

use Closure;
use lloc\CryptoVerifier\SignatureVerifierInterface;
use lloc\CryptoVerifier\VerificationErrorCode;
use lloc\CryptoVerifier\VerificationResult;
use lloc\CryptoVerifier\VerifySignatureCommand;

final readonly class CardanoSignerCliVerifier implements SignatureVerifierInterface
{
    /**
     * @param Closure(string): (string|null) $executor
     */
    public function __construct(
        private Closure $executor,
        private string $binary = '/usr/local/bin/cardano-signer',
    ) {
    }

    public function verify(VerifySignatureCommand $command): VerificationResult
    {
        $parts = [
            escapeshellarg($this->binary),
            'verify',
            '--cip8',
            '--cose-sign1', escapeshellarg($command->signature),
            '--cose-key', escapeshellarg($command->publicKey),
        ];

        if ($command->address) {
            $parts[] = '--address';
            $parts[] = escapeshellarg($command->address);
        }

        $parts[] = '--json';

        $cmd = implode(' ', $parts) . ' 2>&1';
        $output = ($this->executor)($cmd);

        if (!is_string($output) || trim($output) === '') {
            return VerificationResult::invalid(VerificationErrorCode::CLI_NO_OUTPUT);
        }

        $data = json_decode($output, true);

        if (!is_array($data)) {
            return VerificationResult::invalid(VerificationErrorCode::CLI_INVALID_JSON);
        }

        if (!(bool) ($data['result'] ?? false)) {
            return VerificationResult::invalid(VerificationErrorCode::INVALID_SIGNATURE);
        }

        return VerificationResult::valid(
            address: $command->address,
            publicKey: $command->publicKey,
            algorithm: 'Ed25519'
        );
    }
}
