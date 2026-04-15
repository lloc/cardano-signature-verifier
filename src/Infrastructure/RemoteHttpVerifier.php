<?php

declare(strict_types=1);

namespace lloc\CardanoSignatureVerifier\Infrastructure;

use lloc\CardanoSignatureVerifier\SignatureVerifierInterface;
use lloc\CardanoSignatureVerifier\VerificationErrorCode;
use lloc\CardanoSignatureVerifier\VerificationResult;
use lloc\CardanoSignatureVerifier\VerifySignatureCommand;

final readonly class RemoteHttpVerifier implements SignatureVerifierInterface
{
    public function __construct(
        private string $endpoint,
        private int $timeout = 10,
        private ?string $apiKey = null,
    ) {
    }

    public function verify(VerifySignatureCommand $command): VerificationResult
    {
        $payload = [
            'message' => $command->message,
            'signature' => $command->signature,
            'key' => $command->publicKey,
            'address' => $command->address,
            'format' => $command->format,
        ];

        $args = [
            'timeout' => $this->timeout,
            'headers' => array_filter([
                'Content-Type' => 'application/json',
                'Authorization' => $this->apiKey ? 'Bearer ' . $this->apiKey : null,
            ]),
            'body' => wp_json_encode($payload),
        ];

        $response = wp_remote_post($this->endpoint, $args);

        if (is_wp_error($response)) {
            return VerificationResult::invalid(VerificationErrorCode::VERIFIER_UNREACHABLE);
        }

        $status = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($status !== 200 || !is_array($data)) {
            return VerificationResult::invalid(VerificationErrorCode::INVALID_VERIFIER_RESPONSE);
        }

        if (!($data['valid'] ?? false)) {
            return VerificationResult::invalid(
                $data['error_code'] ?? VerificationErrorCode::INVALID_SIGNATURE,
                $data['warnings'] ?? []
            );
        }

        return VerificationResult::valid(
            message: $data['message'] ?? null,
            address: $data['address'] ?? null,
            publicKey: $data['public_key'] ?? null,
            stakeKey: $data['stake_key'] ?? null,
            algorithm: $data['algorithm'] ?? 'Ed25519',
            warnings: $data['warnings'] ?? [],
        );
    }
}
