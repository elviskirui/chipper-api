<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable()->change();
            $table->nullableMorphs('favoritable');
            $table->index(['favoritable_id', 'favoritable_type']);
        });

        // Migrate existing data to the new polymorphic columns - Could wrap this in a transaction if needed
        DB::table('favorites')->whereNotNull('post_id')->update([
            'favoritable_type' => Post::class,
            'favoritable_id' => DB::raw('post_id'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex(['favoritable_id', 'favoritable_type']);
            $table->dropMorphs('favoritable');
            $table->unsignedBigInteger('post_id')->nullable(false)->change();
            $table->foreignId('user_id')->constrained();
        });
    }
};
