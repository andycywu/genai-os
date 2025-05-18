<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('llms', function (Blueprint $table) {
            $table->longText('config')->default('{"react_btn":["feedback","quote","other"]}')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old default (with translate)
        Schema::table('llms', function (Blueprint $table) {
            $table->longText('config')->default('{"react_btn":["feedback","translate","quote","other"]}')->change();
        });
    }
};
