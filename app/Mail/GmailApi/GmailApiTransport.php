<?php

namespace App\Mail\GmailApi;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    public function __construct(
        private GmailApiClient $client,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct(null, $logger);
    }

    public function __toString(): string
    {
        return 'gmail_api';
    }

    protected function doSend(SentMessage $message): void
    {
        try {
            $gmailMessageId = $this->client->sendRawMessage(
                $this->base64UrlEncode($message->toString())
            );
        } catch (GmailApiException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        if ($gmailMessageId !== '') {
            $message->setMessageId($gmailMessageId);
        }
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
