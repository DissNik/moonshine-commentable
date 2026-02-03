<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up():void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            $table->morphs('commentable');

            $table->nullableMorphs('author');

            $table->unsignedBigInteger('parent_id')->nullable();

            $table->text('text');

            $table->json('payload')->nullable();

            $table->timestamps();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['commentable_id', 'commentable_type']);
            $table->index('parent_id');
            $table->index('created_at');

            $table->foreign('parent_id')
                ->references('id')
                ->on('comments')
                ->cascadeOnDelete();
        });
    }

    public function down():void
    {
        if (app()->isLocal()) {
            Schema::dropIfExists('comments');
        }
    }
};
