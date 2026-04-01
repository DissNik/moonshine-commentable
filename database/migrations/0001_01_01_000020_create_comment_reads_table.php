<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reads', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->morphs('commentable');
            $table->morphs('reader');
            $table->timestamp('last_read_at');
            $table->timestamps();

            $table->unique(
                ['commentable_id', 'commentable_type', 'reader_id', 'reader_type'],
                'comment_reads_commentable_reader_unique',
            );
            $table->index(['commentable_id', 'commentable_type']);
            $table->index(['reader_id', 'reader_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reads');
    }
};
