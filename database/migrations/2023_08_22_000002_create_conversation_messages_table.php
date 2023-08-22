<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        Schema::create('open_ai_conversation_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('open_ai_id', 80)->nullable();
            $table->longtext('content');
            $table->unsignedBigInteger('actual_token_length')->default(0);
            $table->unsignedBigInteger('estimated_token_length')->default(0);
            $table->uuid('conversation_id');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('open_ai_conversation_messages', function (Blueprint $table) {
            $table->foreign('conversation_id')->references('id')->on('open_ai_conversations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('open_ai_conversation_messages');
    }
};
