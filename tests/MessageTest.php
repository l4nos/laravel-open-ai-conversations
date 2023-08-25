<?php

namespace Lanos\OpenAiConversations\Tests;

use Illuminate\Support\Facades\Schema;
use Lanos\OpenAiConversations\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lanos\OpenAiConversations\Models\Conversation;
use Lanos\OpenAiConversations\Models\ConversationMessage;

class ConversationMessageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_checks_if_the_database_and_table_are_created()
    {
        $this->assertTrue(Schema::hasTable((new ConversationMessage())->getTable()));

        $conversation = Conversation::create([
            'model' => 'your_model_name',
            'n_value' => 42,
            'token_limit' => 1000,
        ]);

        $message = ConversationMessage::create([
            'open_ai_id'             => 'sample_id',
            'content'                => 'Sample message content',
            'actual_token_length'    => 10,
            'estimated_token_length' => 8,
            'role'                   => 'user',
            'conversation_id'        => $conversation->id,
        ]);

        $this->assertNotNull($message);
        $this->assertEquals('sample_id', $message->open_ai_id);
    }
}
