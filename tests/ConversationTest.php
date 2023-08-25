<?php

namespace Lanos\OpenAiConversations\Tests;

use ReflectionMethod;
use Illuminate\Support\Facades\Schema;
use Lanos\OpenAiConversations\Tests\TestCase;
use Lanos\OpenAiConversations\Enums\OpenAiRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lanos\OpenAiConversations\Models\Conversation;
use Lanos\OpenAiConversations\Models\ConversationMessage;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_checks_if_the_conversation_table_exists()
    {
        $tableName = (new Conversation())->getTable();

        $this->assertTrue(Schema::hasTable($tableName));
    }

    /** @test */
    public function it_prepares_message_payload_correctly()
    {
        $conversation = Conversation::create();

        $method = new ReflectionMethod(Conversation::class, 'prepareMessagePayload');

        $method->setAccessible(true);

        $return = $method->invoke($conversation, 'Sample message content');

        $this->assertEquals(
            [
                'role'    => OpenAiRole::USER,
                'content' => 'Sample message content',
            ],
            $return[0]
        );
    }

    /** @test */
    public function it_prepares_previous_conversation_correctly()
    {
        $conversation = Conversation::create();

        $method = new ReflectionMethod(Conversation::class, 'preparePreviousConversation');

        $method->setAccessible(true);

        $message1 = ConversationMessage::create([
            'open_ai_id' => 'sample_id_1',
            'content' => 'Message 1',
            'actual_token_length' => 10,
            'role' => OpenAiRole::USER,
            'conversation_id' => $conversation->id,
        ]);

        $message2 = ConversationMessage::create([
            'open_ai_id' => 'sample_id_2',
            'content' => 'Message 2',
            'actual_token_length' => 8,
            'role' => OpenAiRole::ASSISTANT,
            'conversation_id' => $conversation->id,
        ]);

        $formattedConversation = $method->invoke($conversation);

        $this->assertEquals([
            [
                'role' => 'user',
                'content' => $message1->content,
            ],
            [
                'role' => 'assistant',
                'content' => $message2->content,
            ]
        ], $formattedConversation);
    }

    /** @test */
    public function it_enforces_token_limit_correctly()
    {
        $conversation = Conversation::create();

        $method = new ReflectionMethod(Conversation::class, 'enforceTokenLimit');

        $method->setAccessible(true);

        ConversationMessage::create([
            'open_ai_id' => 'sample_id_1',
            'content' => 'Message 1',
            'actual_token_length' => $conversation->token_limit - 10,
            'role' => OpenAiRole::USER,
            'conversation_id' => $conversation->id,
        ]);

        ConversationMessage::create([
            'open_ai_id' => 'sample_id_2',
            'content' => 'Message 2',
            'actual_token_length' => 8,
            'role' => OpenAiRole::ASSISTANT,
            'conversation_id' => $conversation->id,
        ]);

        $newMessageLength = 15;

        $method->invoke($conversation, $newMessageLength);

        $this->assertEquals(1, $conversation->messages()->count());
    }
}
