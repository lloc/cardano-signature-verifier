# Cardano Signature Verifier

Cardano Signature Verifier in PHP/WordPress for the Cardano Blockchain.

## Table of Contents

* [Installation](#installation)
* [Quickstart](#quickstart)
* [WP-CLI](#wp-cli)
* [Copyright and License](#copyright-and-license)
* [Contributing](#contributing)

## Installation

The best way to use this package is:

```shell
composer require lloc/cardano-signature-verifier
```

Requires PHP 8.3 or later.

## Quickstart

### Verify a signature with the local CLI

The `CardanoSignerCliVerifier` wraps the
[cardano-signer](https://github.com/gitmachtl/cardano-signer) binary:

```php
use lloc\CardanoSignatureVerifier\Infrastructure\CardanoSignerCliVerifier;
use lloc\CardanoSignatureVerifier\VerifySignatureCommand;

$verifier = new CardanoSignerCliVerifier(
    executor: static fn (string $cmd): ?string => shell_exec($cmd),
    binary: '/usr/local/bin/cardano-signer',   // default path
);

$command = new VerifySignatureCommand(
    message: 'Hello, Cardano!',
    signature: '<COSE-Sign1 hex>',
    publicKey: '<COSE key hex>',
    address: 'addr1...',                        // optional
);

$result = $verifier->verify($command);

if ($result->valid) {
    echo "Valid — algorithm: {$result->algorithm}";
} else {
    echo "Invalid — error: {$result->errorCode->value}";
}
```

### Verify a signature via a remote HTTP service

The `RemoteHttpVerifier` delegates verification to an external API. It uses the WordPress HTTP API internally and is designed for use within WordPress:

```php
use lloc\CardanoSignatureVerifier\Infrastructure\RemoteHttpVerifier;
use lloc\CardanoSignatureVerifier\VerifySignatureCommand;

$verifier = new RemoteHttpVerifier(
    endpoint: 'https://verify.example.com/api/verify',
    timeout: 10,                                // seconds, default
    apiKey: 'your-api-key',                     // optional Bearer token
);

$result = $verifier->verify(new VerifySignatureCommand(
    message: 'Hello, Cardano!',
    signature: '<COSE-Sign1 hex>',
    publicKey: '<COSE key hex>',
));

if ($result->valid) {
    echo "Address: {$result->address}, Stake key: {$result->stakeKey}";
}
```

### Use your own verifier

Implement `SignatureVerifierInterface` to plug in any verification backend:

```php
use lloc\CardanoSignatureVerifier\SignatureVerifierInterface;
use lloc\CardanoSignatureVerifier\VerificationResult;
use lloc\CardanoSignatureVerifier\VerifySignatureCommand;

final readonly class MyCustomVerifier implements SignatureVerifierInterface
{
    public function verify(VerifySignatureCommand $command): VerificationResult
    {
        // your verification logic here
    }
}
```

### Handle errors

Every call to `verify()` returns a `VerificationResult`. Check `$result->valid`
and inspect `$result->errorCode` on failure:

| Error code                   | Meaning                                      |
|------------------------------|----------------------------------------------|
| `cli_invalid_json`           | CLI returned non-JSON output                 |
| `cli_no_output`              | CLI produced no output                       |
| `invalid_signature`          | Signature did not pass verification          |
| `invalid_verifier_response`  | Remote service returned an unexpected response |
| `verifier_unreachable`       | Remote service could not be reached          |

```php
if (!$result->valid) {
    // $result->errorCode is a VerificationErrorCode enum
    match ($result->errorCode) {
        VerificationErrorCode::VERIFIER_UNREACHABLE => retry(),
        default => log($result->errorCode->value),
    };

    foreach ($result->warnings as $warning) {
        echo "Warning: {$warning}";
    }
}
```

## WP-CLI

A WP-CLI command is available when the verifier is registered:

```shell
wp crypto verify <signature> <public-key> [--address=<address>] [--message=<message>] [--format=<format>]
```

## Copyright and License

This package is [free software](https://www.gnu.org/philosophy/free-sw.en.html) distributed under
the terms of the GNU General Public License version 2 or (at your option) any later version. For the
full license, see [LICENSE](./LICENSE).

## Contributing

All feedback, bug reports and pull requests are welcome.
