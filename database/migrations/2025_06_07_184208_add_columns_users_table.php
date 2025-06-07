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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('address')->nullable()->after('phone');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->after('address');
            $table->string('code')->nullable()->comment('codigo de usuario/abonado')->after('zone_id');
            $table->string('id_number')->nullable()->comment('cedula, pasaporte, etc')->after('code');
            //plans
            $table->foreignId('plan_id')->nullable()->constrained('plans')->after('id_number');
            $table->boolean('status')->default(true)->after('plan_id');
            //rol
            $table->tinyInteger('role')->default(1)->after('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('address');
            $table->dropColumn('zone_id');
            $table->dropColumn('code');
            $table->dropColumn('id_number');
            $table->dropColumn('plan_id');
            $table->dropColumn('status');
            $table->dropColumn('role');
        });
    }
};
