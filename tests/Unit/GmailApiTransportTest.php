<?php

namespace Tests\Unit;

use App\Mail\GmailApi\GmailApiClient;
use App\Mail\GmailApi\GmailApiTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class GmailApiTransportTest extends TestCase
{
    public function test_transport_encodes_generated_mime_message_for_gmail_api(): void
    {
        $client = new CapturingGmailApiClient();
        $transport = new GmailApiTransport($client);

        $email = (new Email())
            ->from(new Address('sender@example.com', 'WarisanMakan'))
            ->to(new Address('contributor@example.com', 'Contributor'))
            ->subject('WarisanMakan Contribution Approved')
            ->text('Plain text fallback')
            ->html('<p>Your contribution has been approved.</p>');

        $sent = $transport->send($email);

        $this->assertNotNull($sent);
        $this->assertSame('gmail-message-id', $sent->getMessageId());
        $this->assertNotNull($client->rawMessage);
        $this->assertStringNotContainsString('+', $client->rawMessage);
        $this->assertStringNotContainsString('/', $client->rawMessage);
        $this->assertStringNotContainsString('=', $client->rawMessage);

        $mime = $this->base64UrlDecode($client->rawMessage);

        $this->assertStringContainsString('From: WarisanMakan <sender@example.com>', $mime);
        $this->assertStringContainsString('To: Contributor <contributor@example.com>', $mime);
        $this->assertStringContainsString('Subject: WarisanMakan Contribution Approved', $mime);
        $this->assertStringContainsString('Plain text fallback', $mime);
        $this->assertStringContainsString('Your contribution has been approved.', $mime);
    }

    private function base64UrlDecode(string $value): string
    {
        $padded = str_pad($value, strlen($value) + ((4 - strlen($value) % 4) % 4), '=');

        return base64_decode(strtr($padded, '-_', '+/'), true) ?: '';
    }
}

class CapturingGmailApiClient implements GmailApiClient
{
    public ?string $rawMessage = null;

    public function sendRawMessage(string $rawMessage): string
    {
        $this->rawMessage = $rawMessage;

        return 'gmail-message-id';
    }
}
