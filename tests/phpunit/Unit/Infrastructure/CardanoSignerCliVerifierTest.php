<?php

declare(strict_types=1);

namespace lloc\CardanoSignatureVerifier\Tests\Unit\Infrastructure;

use lloc\CardanoSignatureVerifier\Infrastructure\CardanoSignerCliVerifier;
use lloc\CardanoSignatureVerifier\Tests\AbstractTestCase;
use lloc\CardanoSignatureVerifier\VerificationErrorCode;
use lloc\CardanoSignatureVerifier\VerifySignatureCommand;

class CardanoSignerCliVerifierTest extends AbstractTestCase
{
    private function createCommand(?string $address = null): VerifySignatureCommand
    {
        return new VerifySignatureCommand(
            message: 'hello',
            signature: 'sig123',
            publicKey: 'pk456',
            address: $address
        );
    }

    public function testVerifyReturnsValidOnSuccess(): void
    {
        $executor = static fn (string $cmd): string => '{"result":true}';

        $verifier = new CardanoSignerCliVerifier($executor, '/usr/bin/cardano-signer');
        $result = $verifier->verify($this->createCommand());

        $this->assertTrue($result->valid);
        $this->assertSame('pk456', $result->publicKey);
        $this->assertSame('Ed25519', $result->algorithm);
        $this->assertNull($result->errorCode);
    }

    public function testVerifyReturnsInvalidOnNoOutput(): void
    {
        $executor = static fn (string $cmd): ?string => null;

        $verifier = new CardanoSignerCliVerifier($executor);
        $result = $verifier->verify($this->createCommand());

        $this->assertFalse($result->valid);
        $this->assertSame(VerificationErrorCode::CLI_NO_OUTPUT, $result->errorCode);
    }

    public function testVerifyReturnsInvalidOnEmptyOutput(): void
    {
        $executor = static fn (string $cmd): string => '   ';

        $verifier = new CardanoSignerCliVerifier($executor);
        $result = $verifier->verify($this->createCommand());

        $this->assertFalse($result->valid);
        $this->assertSame(VerificationErrorCode::CLI_NO_OUTPUT, $result->errorCode);
    }

    public function testVerifyReturnsInvalidOnNonJsonOutput(): void
    {
        $executor = static fn (string $cmd): string => 'not json';

        $verifier = new CardanoSignerCliVerifier($executor);
        $result = $verifier->verify($this->createCommand());

        $this->assertFalse($result->valid);
        $this->assertSame(VerificationErrorCode::CLI_INVALID_JSON, $result->errorCode);
    }

    public function testVerifyReturnsInvalidOnFailedResult(): void
    {
        $executor = static fn (string $cmd): string => '{"result":false}';

        $verifier = new CardanoSignerCliVerifier($executor);
        $result = $verifier->verify($this->createCommand());

        $this->assertFalse($result->valid);
        $this->assertSame(VerificationErrorCode::INVALID_SIGNATURE, $result->errorCode);
    }

    public function testVerifyIncludesAddressWhenProvided(): void
    {
        $executedCmd = '';
        $executor = static function (string $cmd) use (&$executedCmd): string {
            $executedCmd = $cmd;

            return '{"result":true}';
        };

        $verifier = new CardanoSignerCliVerifier($executor);
        $result = $verifier->verify($this->createCommand('addr_test1'));

        $this->assertTrue($result->valid);
        $this->assertSame('addr_test1', $result->address);
        $this->assertStringContainsString('--address', $executedCmd);
    }
}
