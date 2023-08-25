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
        Schema::create(config('open_ai_conversations.database.conversations.table'), function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('model');
            $table->unsignedBigInteger('n_value')->default(1);
            $table->unsignedBigInteger('token_limit');
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
        Schema::dropIfExists(config('open_ai_conversations.database.conversations.table'));
    }
};
