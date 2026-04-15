<?php

declare(strict_types=1);

namespace lloc\CardanoSignatureVerifier\Cli;

use lloc\CardanoSignatureVerifier\SignatureVerifierInterface;
use lloc\CardanoSignatureVerifier\VerifySignatureCommand;
use WP_CLI;

final readonly class VerifyCommand
{
    public function __construct(
        private SignatureVerifierInterface $verifier
    ) {
    }

    /**
     * Verifies a Cardano CIP-8 signature.
     *
     * ## OPTIONS
     *
     * <signature>
     * : The COSE-Sign1 signature.
     *
     * <public-key>
     * : The COSE public key.
     *
     * [--address=<address>]
     * : Optional Cardano address.
     *
     * [--message=<message>]
     * : The signed message.
     *
     * [--format=<format>]
     * : Signature format.
     * ---
     * default: cip30
     * ---
     *
     * ## EXAMPLES
     *
     *     wp crypto verify <sig> <key> --address=addr_test1...
     *
     * @when after_wp_load
     *
     * @param list<string> $args
     * @param array<string, string> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $command = new VerifySignatureCommand(
            message: $assocArgs['message'] ?? '',
            signature: $args[0],
            publicKey: $args[1],
            address: $assocArgs['address'] ?? null,
            format: $assocArgs['format'] ?? 'cip30',
        );

        $result = $this->verifier->verify($command);

        if ($result->valid) {
            WP_CLI::success('Signature verified.');

            return;
        }

        WP_CLI::error(
            sprintf('Verification failed: %s', $result->errorCode->value ?? 'unknown')
        );
    }
}
