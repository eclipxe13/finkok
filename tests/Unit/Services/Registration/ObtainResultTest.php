<?php

declare(strict_types=1);

namespace PhpCfdi\Finkok\Tests\Unit\Services\Registration;

use PhpCfdi\Finkok\Services\Registration\ObtainResult;
use PhpCfdi\Finkok\Tests\TestCase;
use stdClass;

final class ObtainResultTest extends TestCase
{
    public function testResultUsingPredefinedResponses(): void
    {
        /** @var stdClass $data */
        $data = json_decode($this->fileContentPath('registration-get-response.json'));
        $result = new ObtainResult($data);
        $this->assertSame('predefined-message', $result->message());
        $this->assertCount(1, $result->customers());
    }
}
