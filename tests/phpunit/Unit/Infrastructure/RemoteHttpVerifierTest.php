<?php

declare(strict_types=1);

namespace lloc\CryptoVerifier\Tests\Unit\Infrastructure;

use Brain\Monkey\Functions;
use lloc\CryptoVerifier\Infrastructure\RemoteHttpVerifier;
use lloc\CryptoVerifier\Tests\AbstractTestCase;
use lloc\CryptoVerifier\VerificationErrorCode;
use lloc\CryptoVerifier\VerifySignatureCommand;

class RemoteHttpVerifierTest extends AbstractTestCase
{
    private function createCommand(): VerifySignatureCommand
    {
        return new VerifySignatureCommand(
            message: 'hello',
            signature: 'sig123',
            publicKey: 'pk456',
            address: 'addr789'
        );
    }

    public function testVerifyReturnsUnreachableOnWpError(): void
    {
        $wpError = \Mockery::mock('WP_Error');

        Functions\expect('wp_json_encode')->once()->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')->once()->andReturn($wpError);
        Functions\expect('is_wp_error')->once()->with($wpError)->andReturn(true);

        $verifier = new RemoteHttpVerifier('https://example.com/verify');
        $result = $verifier->verify($this->createCommand());

        $this->assertFalse($result->valid);
        $this->assertSame(VerificationErrorCode::VERIFIER_UNREACHABLE, $result->errorCode);
    }

    public function testVerifyReturnsInvalidResponseOnNon200Status(): void
    {
        $response = ['response' => ['code' => 500]];

        Functions\expect('wp_json_encode')->once()->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')->once()->andReturn($response);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(500);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('');

        $verifier = new RemoteHttpVerifier('https://example.com/verify');
        $result = $verifier->verify($this->createCommand());

        $this->assertFalse($result->valid);
        $this->assertSame(VerificationErrorCode::INVALID_VERIFIER_RESPONSE, $result->errorCode);
    }

    public function testVerifyReturnsInvalidResponseOnNonJsonBody(): void
    {
        Functions\expect('wp_json_encode')->once()->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('not json');

        $verifier = new RemoteHttpVerifier('https://example.com/verify');
        $result = $verifier->verify($this->createCommand());

        $this->assertFalse($result->valid);
        $this->assertSame(VerificationErrorCode::INVALID_VERIFIER_RESPONSE, $result->errorCode);
    }

    public function testVerifyReturnsInvalidSignatureOnFailedValidation(): void
    {
        $body = json_encode([
            'valid' => false,
            'warnings' => ['bad sig'],
        ]);

        Functions\expect('wp_json_encode')->once()->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn($body);

        $verifier = new RemoteHttpVerifier('https://example.com/verify');
        $result = $verifier->verify($this->createCommand());

        $this->assertFalse($result->valid);
        $this->assertSame(VerificationErrorCode::INVALID_SIGNATURE, $result->errorCode);
        $this->assertSame(['bad sig'], $result->warnings);
    }

    public function testVerifyReturnsValidOnSuccess(): void
    {
        $body = json_encode([
            'valid' => true,
            'message' => 'hello',
            'address' => 'addr789',
            'public_key' => 'pk456',
            'stake_key' => 'sk1',
            'algorithm' => 'Ed25519',
            'warnings' => [],
        ]);

        Functions\expect('wp_json_encode')->once()->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn($body);

        $verifier = new RemoteHttpVerifier('https://example.com/verify');
        $result = $verifier->verify($this->createCommand());

        $this->assertTrue($result->valid);
        $this->assertSame('hello', $result->message);
        $this->assertSame('addr789', $result->address);
        $this->assertSame('pk456', $result->publicKey);
        $this->assertSame('sk1', $result->stakeKey);
        $this->assertSame('Ed25519', $result->algorithm);
        $this->assertSame([], $result->warnings);
        $this->assertNull($result->errorCode);
    }

    public function testVerifyIncludesAuthorizationHeaderWhenApiKeyProvided(): void
    {
        $body = json_encode(['valid' => true]);

        Functions\expect('wp_json_encode')->once()->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')
            ->once()
            ->with(
                'https://example.com/verify',
                \Mockery::on(static function (array $args): bool {
                    return ($args['headers']['Authorization'] ?? '') === 'Bearer secret-key';
                })
            )
            ->andReturn([]);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn($body);

        $verifier = new RemoteHttpVerifier('https://example.com/verify', apiKey: 'secret-key');
        $result = $verifier->verify($this->createCommand());

        $this->assertTrue($result->valid);
    }

    public function testVerifyUsesDefaultAlgorithmWhenNotInResponse(): void
    {
        $body = json_encode([
            'valid' => true,
            'message' => 'hello',
        ]);

        Functions\expect('wp_json_encode')->once()->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn($body);

        $verifier = new RemoteHttpVerifier('https://example.com/verify');
        $result = $verifier->verify($this->createCommand());

        $this->assertTrue($result->valid);
        $this->assertSame('Ed25519', $result->algorithm);
    }
}
