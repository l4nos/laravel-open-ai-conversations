<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lanos\OpenAiConversations\Models\Conversation;

/**
 * Creates the Stripe Account columns for the user.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('open_ai_conversations.database.messages.table'), function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('open_ai_id', 80)->nullable();
            $table->longtext('content');
            $table->unsignedBigInteger('actual_token_length')->default(0);
            $table->unsignedBigInteger('estimated_token_length')->default(0);
            $table->foreignUuid('conversation_id')->index()->constrained(config('open_ai_conversations.database.conversations.table'))->cascadeOnDelete();
            $table->string('role');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('open_ai_conversations.database.messages.table'));
    }
};
