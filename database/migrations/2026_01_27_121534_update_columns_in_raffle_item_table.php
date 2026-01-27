<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('raffle_item', function (Blueprint $table) {
            $table->renameColumn('quantity', 'initial_quantity');
            $table->integer('remaining_quantity')->after('initial_quantity')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raffle_item', function (Blueprint $table) {
            $table->renameColumn('initial_quantity', 'quantity');
            $table->dropColumn('remaining_quantity');

        });
    }
};
