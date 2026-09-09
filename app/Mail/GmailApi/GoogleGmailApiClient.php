<?php

namespace App\Mail\GmailApi;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Psr\Log\LoggerInterface;
use Throwable;

class GoogleGmailApiClient implements GmailApiClient
{
    public function __construct(
        private HttpFactory $http,
        private LoggerInterface $logger,
        private array $config,
    ) {
    }

    public function sendRawMessage(string $rawMessage): string
    {
        $accessToken = $this->accessToken();

        try {
            $response = $this->http
                ->withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeout())
                ->post($this->sendEndpoint(), [
                    'raw' => $rawMessage,
                ]);
        } catch (Throwable $exception) {
            $this->logHttpException('Gmail API message send HTTP request failed.', $exception);

            throw new GmailApiException(
                'Gmail API message send HTTP request failed: '.$exception::class,
                previous: $exception
            );
        }

        if (! $response->successful()) {
            $context = $this->safeResponseContext($response);

            $this->logger->warning('Gmail API message send failed.', $context);

            throw new GmailApiException($this->failureMessage('message send', $context));
        }

        $messageId = $response->json('id');

        return is_string($messageId) ? $messageId : '';
    }

    private function accessToken(): string
    {
        $this->assertConfigured();

        try {
            $response = $this->http
                ->asForm()
                ->acceptJson()
                ->timeout($this->timeout())
                ->post($this->tokenEndpoint(), [
                    'client_id' => $this->stringConfig('client_id'),
                    'client_secret' => $this->stringConfig('client_secret'),
                    'refresh_token' => $this->stringConfig('refresh_token'),
                    'grant_type' => 'refresh_token',
                ]);
        } catch (Throwable $exception) {
            $this->logHttpException('Gmail API OAuth token HTTP request failed.', $exception);

            throw new GmailApiException(
                'Gmail API OAuth token HTTP request failed: '.$exception::class,
                previous: $exception
            );
        }

        if (! $response->successful()) {
            $context = $this->safeResponseContext($response);

            $this->logger->warning('Gmail API OAuth token exchange failed.', $context);

            throw new GmailApiException($this->failureMessage('OAuth token exchange', $context));
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            $context = ['http_status' => $response->status(), 'provider_error' => 'missing_access_token'];

            $this->logger->warning('Gmail API OAuth token response did not contain an access token.', $context);

            throw new GmailApiException($this->failureMessage('OAuth token exchange', $context));
        }

        return $token;
    }

    private function assertConfigured(): void
    {
        foreach (['client_id', 'client_secret', 'refresh_token'] as $key) {
            if ($this->stringConfig($key) === '') {
                $this->logger->warning('Gmail API mailer is missing required configuration.', [
                    'missing_config_key' => $key,
                ]);

                throw new GmailApiException('Gmail API mailer is missing required configuration: '.$key);
            }
        }
    }

    private function stringConfig(string $key): string
    {
        $value = $this->config[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    private function tokenEndpoint(): string
    {
        return $this->stringConfig('token_endpoint') ?: 'https://oauth2.googleapis.com/token';
    }

    private function sendEndpoint(): string
    {
        return $this->stringConfig('send_endpoint') ?: 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';
    }

    private function timeout(): int
    {
        $timeout = (int) ($this->config['timeout'] ?? 10);

        return $timeout > 0 ? $timeout : 10;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function safeResponseContext(Response $response): array
    {
        $json = $response->json();
        $error = is_array($json) ? ($json['error'] ?? null) : null;

        $providerError = null;
        $providerMessage = null;

        if (is_string($error)) {
            $providerError = $error;
            $providerMessage = is_array($json) && is_string($json['error_description'] ?? null)
                ? $json['error_description']
                : null;
        } elseif (is_array($error)) {
            $status = $error['status'] ?? null;
            $code = $error['code'] ?? null;

            $providerError = is_string($status)
                ? $status
                : (is_int($code) ? (string) $code : null);
            $providerMessage = is_string($error['message'] ?? null) ? $error['message'] : null;
        }

        return array_filter([
            'http_status' => $response->status(),
            'provider_error' => $this->safeText($providerError),
            'provider_message' => $this->safeText($providerMessage),
        ], fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, int|string|null>  $context
     */
    private function failureMessage(string $operation, array $context): string
    {
        $message = 'Gmail API '.$operation.' failed';

        if (isset($context['http_status'])) {
            $message .= ' (HTTP '.$context['http_status'].')';
        }

        if (isset($context['provider_error'])) {
            $message .= ': '.$context['provider_error'];
        }

        if (isset($context['provider_message'])) {
            $message .= ' - '.$context['provider_message'];
        }

        return $message;
    }

    private function safeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr(preg_replace('/[[:cntrl:]]+/', ' ', $value) ?? $value, 0, 240);
    }

    private function logHttpException(string $message, Throwable $exception): void
    {
        $this->logger->warning($message, [
            'exception' => $exception::class,
        ]);
    }
}
