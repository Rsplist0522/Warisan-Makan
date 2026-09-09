<?php

namespace Tests\Unit;

use App\Mail\GmailApi\GoogleGmailApiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GoogleGmailApiClientTest extends TestCase
{
    public function test_client_refreshes_access_token_and_sends_raw_message_without_real_http(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3599,
                'token_type' => 'Bearer',
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'id' => 'gmail-api-id',
            ]),
        ]);

        $client = new GoogleGmailApiClient(Http::getFacadeRoot(), Log::getFacadeRoot(), [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'refresh_token' => 'test-refresh-token',
        ]);

        $messageId = $client->sendRawMessage('encoded-message');

        $this->assertSame('gmail-api-id', $messageId);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://oauth2.googleapis.com/token'
                && $request->isForm()
                && $request['client_id'] === 'test-client-id'
                && $request['grant_type'] === 'refresh_token';
        });

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send'
                && $request->hasHeader('Authorization', 'Bearer test-access-token')
                && $request['raw'] === 'encoded-message';
        });

        Http::assertSentCount(2);
    }
}
