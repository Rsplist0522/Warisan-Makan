<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChatboxXssTest extends TestCase
{
    public function test_user_chat_html_is_rendered_as_literal_text(): void
    {
        $chatbox = view('partials.chatbox')->render();

        $this->assertStringContainsString("b.textContent = String(text ?? '');", $chatbox);
        $this->assertStringNotContainsString('b.innerHTML =', $chatbox);

        // With textContent, this payload is displayed as literal text and cannot create an H1 element.
        $payload = '<h1>TEST</h1>';
        $this->assertSame('&lt;h1&gt;TEST&lt;/h1&gt;', htmlspecialchars($payload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    public function test_ai_formatting_builds_safe_bold_and_internal_link_nodes(): void
    {
        $chatbox = view('partials.chatbox')->render();

        $this->assertStringContainsString("document.createElement('strong')", $chatbox);
        $this->assertStringContainsString("document.createElement('a')", $chatbox);
        $this->assertStringContainsString('document.createTextNode(', $chatbox);
        $this->assertStringContainsString('strong.textContent = match[1]', $chatbox);
        $this->assertStringContainsString('link.textContent = match[2]', $chatbox);
        $this->assertStringContainsString("!href.startsWith('/')", $chatbox);
        $this->assertStringContainsString("href.startsWith('//')", $chatbox);
        $this->assertStringContainsString("href.startsWith('/\\\\')", $chatbox);
        $this->assertStringContainsString('new URL(href, window.location.origin).origin === window.location.origin', $chatbox);
    }

    public function test_only_fixed_typing_indicator_markup_uses_inner_html(): void
    {
        $chatbox = view('partials.chatbox')->render();

        $this->assertSame(1, substr_count($chatbox, '.innerHTML'));
        $this->assertStringContainsString("typing.innerHTML = '<span></span><span></span><span></span>';", $chatbox);
    }
}
