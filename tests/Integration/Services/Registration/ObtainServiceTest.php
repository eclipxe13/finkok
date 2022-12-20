<?php

declare(strict_types=1);

namespace PhpCfdi\Finkok\Tests\Integration\Services\Registration;

use PhpCfdi\Finkok\Services\Registration\ObtainCommand;
use PhpCfdi\Finkok\Services\Registration\ObtainService;

final class ObtainServiceTest extends RegistrationIntegrationTestCase
{
    protected function createService(): ObtainService
    {
        $settings = $this->createSettingsFromEnvironment();
        return new ObtainService($settings);
    }

    public function testConsumeObtainServiceGettingNonExistentRecord(): void
    {
        $rfc = self::CUSTOMER_NON_EXISTENT;
        $this->assertNull($this->findCustomer($rfc), "For this test RFC $rfc must not exists");

        $service = $this->createService();
        $result = $service->obtain(new ObtainCommand($rfc));

        $this->assertSame('', $result->message());
        $this->assertGreaterThanOrEqual(0, count($result->customers()));
    }

    public function testConsumeObtainServiceGettingOwnRfc(): void
    {
        $service = $this->createService();
        $result = $service->obtain(new ObtainCommand('EKU9003173C9'));

        $this->assertSame('', $result->message());
        $this->assertSame(1, count($result->customers()));
        $customer = $result->customers()->getByRfc('EKU9003173C9');
        $this->assertSame('EKU9003173C9', $customer->rfc());
        $this->assertTrue($customer->status()->isActive());
        $this->assertTrue($customer->customerType()->isOndemand());
    }

    public function testConsumeObtainServiceGettingAllRecords(): void
    {
        $service = $this->createService();
        $result = $service->obtain(new ObtainCommand());
        if ('RFC Invalido' === $result->message()) {
            $this->markTestSkipped(<<< MESSAGE
                Finkok does not have implemented the list of customers.
                See https://support.finkok.com/support/tickets/66516
                MESSAGE);
        }

        $this->assertSame('', $result->message());
        $this->assertGreaterThanOrEqual(1, count($result->customers()));
        $this->assertSame('EKU9003173C9', $result->customers()->getByRfc('EKU9003173C9')->rfc());
    }
}
