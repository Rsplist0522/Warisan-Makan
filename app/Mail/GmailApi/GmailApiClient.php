<?php

namespace App\Mail\GmailApi;

interface GmailApiClient
{
    public function sendRawMessage(string $rawMessage): string;
}
