<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OAuth avatar URLs (e.g. Google) are often longer than 255 characters.
     * Use TEXT so the full URL is stored and onboarding preview works.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'photo_url')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('photo_url')->nullable();
            });

            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => DB::statement('ALTER TABLE `users` MODIFY `photo_url` TEXT NULL'),
            'pgsql' => DB::statement('ALTER TABLE users ALTER COLUMN photo_url TYPE TEXT USING (photo_url::text)'),
            default => Schema::table('users', function (Blueprint $table) {
                $table->text('photo_url')->nullable()->change();
            }),
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'photo_url')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => DB::statement('ALTER TABLE `users` MODIFY `photo_url` VARCHAR(255) NULL'),
            'pgsql' => DB::statement('ALTER TABLE users ALTER COLUMN photo_url TYPE VARCHAR(255)'),
            default => Schema::table('users', function (Blueprint $table) {
                $table->string('photo_url', 255)->nullable()->change();
            }),
        };
    }
};
