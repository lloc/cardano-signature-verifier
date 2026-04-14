<?php

declare(strict_types=1);

namespace lloc\CryptoVerifier\Tests\Unit;

use lloc\CryptoVerifier\Tests\AbstractTestCase;
use lloc\CryptoVerifier\VerificationErrorCode;
use lloc\CryptoVerifier\VerificationResult;

class VerificationResultTest extends AbstractTestCase
{
    public function testValidFactoryReturnsValidResult(): void
    {
        $result = VerificationResult::valid(
            message: 'hello',
            address: 'addr1',
            publicKey: 'pk1',
            stakeKey: 'sk1',
            algorithm: 'Ed25519',
            warnings: ['warn1']
        );

        $this->assertTrue($result->valid);
        $this->assertSame('hello', $result->message);
        $this->assertSame('addr1', $result->address);
        $this->assertSame('pk1', $result->publicKey);
        $this->assertSame('sk1', $result->stakeKey);
        $this->assertSame('Ed25519', $result->algorithm);
        $this->assertSame(['warn1'], $result->warnings);
        $this->assertNull($result->errorCode);
    }

    public function testValidFactoryWithDefaults(): void
    {
        $result = VerificationResult::valid();

        $this->assertTrue($result->valid);
        $this->assertNull($result->message);
        $this->assertNull($result->address);
        $this->assertNull($result->publicKey);
        $this->assertNull($result->stakeKey);
        $this->assertNull($result->algorithm);
        $this->assertSame([], $result->warnings);
        $this->assertNull($result->errorCode);
    }

    public function testInvalidFactoryReturnsInvalidResult(): void
    {
        $result = VerificationResult::invalid(
            VerificationErrorCode::INVALID_SIGNATURE,
            ['something went wrong']
        );

        $this->assertFalse($result->valid);
        $this->assertNull($result->message);
        $this->assertNull($result->address);
        $this->assertNull($result->publicKey);
        $this->assertNull($result->stakeKey);
        $this->assertNull($result->algorithm);
        $this->assertSame(['something went wrong'], $result->warnings);
        $this->assertSame(VerificationErrorCode::INVALID_SIGNATURE, $result->errorCode);
    }

    public function testInvalidFactoryWithDefaultWarnings(): void
    {
        $result = VerificationResult::invalid(VerificationErrorCode::VERIFIER_UNREACHABLE);

        $this->assertFalse($result->valid);
        $this->assertSame([], $result->warnings);
        $this->assertSame(VerificationErrorCode::VERIFIER_UNREACHABLE, $result->errorCode);
    }
}
