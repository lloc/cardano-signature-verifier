<?php

declare(strict_types=1);

namespace lloc\CardanoSignatureVerifier;

enum VerificationErrorCode: string
{
    case CLI_INVALID_JSON = 'cli_invalid_json';
    case CLI_NO_OUTPUT = 'cli_no_output';
    case INVALID_SIGNATURE = 'invalid_signature';
    case INVALID_VERIFIER_RESPONSE = 'invalid_verifier_response';
    case VERIFIER_UNREACHABLE = 'verifier_unreachable';
}
