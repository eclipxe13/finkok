<?php

declare(strict_types=1);

namespace PhpCfdi\Finkok\Helpers;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Esta clase es un adaptador para convertir un mensaje de registro (log) que está
 * en formato Json y es decodificado y convertido en texto a través de la función
 * print_r, luego pasa el mensaje al logger con el que fue construido el objeto.
 *
 * Si el mensaje no es un Json no válido entonces pasa sin convertirse.
 *
 * Tiene algunas opciones:
 * - alsoLogJsonMessage: Envía los dos mensajes, tanto el texto como el json al logger.
 * - useJsonValidateIfAvailable: Usa \json_validate() si está disponible.
 */
final class JsonDecoderLogger extends AbstractLogger implements LoggerInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $useJsonValidateIfAvailable = true;

    /** @var bool */
    private $alsoLogJsonMessage = false;

    /** @var bool */
    private $lastMessageWasJsonValid = false;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Define si se utilizará la función \json_validate en caso de estar disponible.
     *
     * @param bool|null $value El nuevo estado, si se establece NULL entonces solo devuelve el espado previo.
     * @return bool El estado previo
     */
    public function setUseJsonValidateIfAvailable(bool $value = null): bool
    {
        $previous = $this->useJsonValidateIfAvailable;
        if (null !== $value) {
            $this->useJsonValidateIfAvailable = $value;
        }
        return $previous;
    }

    /**
     * Define si también se mandará el mensaje JSON al Logger.
     *
     * @param bool|null $value El nuevo estado, si se establece NULL entonces solo devuelve el espado previo.
     * @return bool El estado previo
     */
    public function setAlsoLogJsonMessage(bool $value = null): bool
    {
        $previous = $this->alsoLogJsonMessage;
        if (null !== $value) {
            $this->alsoLogJsonMessage = $value;
        }
        return $previous;
    }

    public function lastMessageWasJsonValid(): bool
    {
        return $this->lastMessageWasJsonValid;
    }

    /**
     * @inheritDoc
     * @param string|\Stringable $message
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $this->jsonDecode($message), $context);
        if ($this->lastMessageWasJsonValid && $this->alsoLogJsonMessage) {
            $this->logger->log($level, $message, $context);
        }
    }

    /** @param string|\Stringable $string */
    private function jsonDecode($string): string
    {
        $this->lastMessageWasJsonValid = false;
        $string = strval($string);

        // json_validate and json_decode
        if ($this->useJsonValidateIfAvailable && function_exists('\json_validate')) {
            if (\json_validate($string)) {
                $this->lastMessageWasJsonValid = true;
                return $this->varDump(json_decode($string));
            }

            return $string;
        }

        // json_decode only
        $decoded = json_decode($string);
        if (JSON_ERROR_NONE === json_last_error()) {
            $this->lastMessageWasJsonValid = true;
            return $this->varDump($decoded);
        }

        return $string;
    }

    /** @param mixed $var */
    private function varDump($var): string
    {
        return print_r($var, true);
    }
}
