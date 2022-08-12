<?php

declare(strict_types=1);

namespace PhpCfdi\Finkok\Tests\Integration\Services\Cancel;

use PhpCfdi\Finkok\Definitions\ReceiptType;
use PhpCfdi\Finkok\Services\Cancel\CancelSignatureService;
use PhpCfdi\Finkok\Services\Cancel\GetReceiptCommand;
use PhpCfdi\Finkok\Services\Cancel\GetReceiptService;
use PhpCfdi\Finkok\Tests\Integration\IntegrationTestCase;
use PhpCfdi\XmlCancelacion\Models\CancelDocument;

final class CancelServicesTest extends IntegrationTestCase
{
    /** @group large */
    public function testCreateCfdiThenGetSatStatusThenCancelSignatureThenGetReceipt(): void
    {
        $settings = $this->createSettingsFromEnvironment();

        // given a cfdi
        $cfdi = $this->stamp($this->newStampingCommand());
        $this->assertNotEmpty($cfdi->uuid(), 'Cannot create a CFDI to test against');

        // check that it has a correct status
        $beforeCancelStatus = $this->checkCanGetSatStatusOrFail(
            $cfdi->xml(),
            'Cannot assert cfdi before cancel status is not: No Encontrado'
        );
        $this->assertSame('Vigente', $beforeCancelStatus->cfdi());
        $this->assertStringStartsWith('Cancelable ', $beforeCancelStatus->cancellable());

        // Create cancel signature command from capsule
        $service = new CancelSignatureService($settings);

        // evaluate if known response was 205 or 708
        // this is common to happen on testing but not in production since the time
        // elapsed from stamping and cancelling is often more than 2 minutes
        $repeatUntil = $this->timePlusLongTestTimeOut();
        do {
            // build command on every request
            $command = $this->createCancelSignatureCommandFromDocument(
                CancelDocument::newWithErrorsUnrelated($cfdi->uuid())
            );
            // perform cancel
            $result = $service->cancelSignature($command);
            $document = $result->documents()->first();
            if ('300' === $result->statusCode()) {
                // 300: SAT authentication cancellation service fail
                $this->markTestSkipped('StatusCode 300: SAT authentication service fail. See tickets #17743 & #41594');
            }
            if ('304' === $result->statusCode()) {
                $this->fail('StatusCode 304: Certificado revocado o caduco. Do you must change the CSD?');
            }
            // do not try again if a SAT issue is **different** from:
            // 708: Finkok cannot connect to SAT
            // 300: SAT authentication cancellation service fail
            // 305: SAT thinks "Certificado Inválido", it might be because incorrect time verification
            // 205: SAT does not have the uuid available for cancellation
            if (
                ! in_array($result->statusCode(), ['708', '300', '305'], true) &&
                ! in_array($document->documentStatus(), ['205'], true)
            ) {
                break;
            }
            // do not try again if in the loop for more than allowed
            if (time() > $repeatUntil) {
                break;
            }
            // wait and repeat
            sleep(5);
        } while (true);

        if ('205' === $document->documentStatus()) {
            $this->markTestSkipped(<<<MESSAGE
                Unable to test CancelSignatureService::cancelSignature():
                SAT return 205 EstatusUUID: The CFDI was not received by SAT yet.
                MESSAGE);
        }

        // check result related document
        $this->assertSame(
            '201', // 201 - Petición de cancelación realizada exitosamente
            $document->documentStatus(),
            'SAT did not return 201 EstatusUUID on CancelSignature, is the service down?'
        );
        // check result properties
        $this->assertNotEmpty($result->voucher(), 'Finkok did not return voucher (Acuse) on CancelSignature');
        $this->assertNotEmpty($result->date(), 'Finkok did not return the cancellation date');
        $this->assertSame('EKU9003173C9', $result->rfc(), 'Finkok did not return expected RFC');

        // Consume GetReceiptService and assert that the response is the same (as XML and as string)
        $receipt = (new GetReceiptService($settings))->download(
            new GetReceiptCommand('EKU9003173C9', $cfdi->uuid(), ReceiptType::cancellation())
        );
        $this->assertXmlStringEqualsXmlString(
            $result->voucher(),
            $receipt->receipt(),
            'El acuse que proviene del método get_receipt no coincide con el acuse de la cancelación'
        );
        $this->assertSame(
            $result->voucher(),
            $receipt->receipt(),
            'El acuse que proviene del método get_receipt no es exactamente el mismo que el acuse de la cancelación'
        );
    }

    /**
     * This is the same test as above, set up with generated CFDI
     * To enable this test you must add "@test" annotation
     */
    public function manualGetSatStatusThenCancelSignatureThenGetReceipt(): void
    {
        $settings = $this->createSettingsFromEnvironment();

        $cfdiXmlFile = __DIR__ . '/cfdi-to-cancel.xml';
        if (! file_exists($cfdiXmlFile)) {
            $this->markTestIncomplete("File $cfdiXmlFile does not exists");
        }
        $cfdiXml = (string) file_get_contents($cfdiXmlFile);
        $cfdiUuid = '01B04C24-37CC-4F9E-BBA7-007A0AC3B543';

        // check that it has a correct status
        $beforeCancelStatus = $this->checkCanGetSatStatusOrFail(
            $cfdiXml,
            'Cannot assert cfdi before cancel status is not: No Encontrado'
        );

        $this->assertSame('Vigente', $beforeCancelStatus->cfdi());
        $this->assertStringStartsWith('Cancelable ', $beforeCancelStatus->cancellable());

        // Create cancel signature command from capsule
        $service = new CancelSignatureService($settings);

        $command = $this->createCancelSignatureCommandFromDocument(
            CancelDocument::newWithErrorsUnrelated($cfdiUuid)
        );

        // perform cancel
        $result = $service->cancelSignature($command);
        $document = $result->documents()->first();

        // check result related document
        $this->assertSame(
            '201', // 201 - Petición de cancelación realizada exitosamente
            $document->documentStatus(),
            'SAT did not return 201 EstatusUUID on CancelSignature, is the service down?'
        );

        // check result properties
        $this->assertNotEmpty($result->voucher(), 'Finkok did not return voucher (Acuse) on CancelSignature');
        $this->assertNotEmpty($result->date(), 'Finkok did not return the cancellation date');
        $this->assertSame('EKU9003173C9', $result->rfc(), 'Finkok did not return expected RFC');

        // Consume GetReceiptService and assert that the response is the same (as XML and as string)
        $receipt = (new GetReceiptService($settings))->download(
            new GetReceiptCommand('EKU9003173C9', $cfdiUuid, ReceiptType::cancellation())
        );
        $this->assertXmlStringEqualsXmlString(
            $result->voucher(),
            $receipt->receipt(),
            'El acuse que proviene del método get_receipt no coincide con el acuse de la cancelación'
        );
        $this->assertSame(
            $result->voucher(),
            $receipt->receipt(),
            'El acuse que proviene del método get_receipt no es exactamente el mismo que el acuse de la cancelación'
        );
    }
}
