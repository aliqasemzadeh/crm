<?php

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
        Schema::dropIfExists('user_internal_phones');

        Schema::create('user_internal_phones', function (Blueprint $table) {
            $table->id();
            // users table is MyISAM — no FK; keep unsignedBigInteger + index only
            $table->unsignedBigInteger('user_id');
            $table->string('device_id');
            $table->timestamps();

            $table->index('user_id');
            $table->unique(['user_id', 'device_id']);
        });

        if (Schema::hasColumn('users', 'internal_phone_id')) {
            $now = now();

            DB::table('users')
                ->whereNotNull('internal_phone_id')
                ->where('internal_phone_id', '!=', '')
                ->orderBy('id')
                ->select(['id', 'internal_phone_id'])
                ->chunkById(200, function ($users) use ($now): void {
                    $rows = [];
                    foreach ($users as $user) {
                        $rows[] = [
                            'user_id' => $user->id,
                            'device_id' => (string) $user->internal_phone_id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table('user_internal_phones')->insertOrIgnore($rows);
                    }
                });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('internal_phone_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'internal_phone_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('internal_phone_id')->nullable();
            });
        }

        if (Schema::hasTable('user_internal_phones')) {
            $firstPerUser = DB::table('user_internal_phones')
                ->select('user_id', DB::raw('MIN(id) as min_id'))
                ->groupBy('user_id');

            $phones = DB::table('user_internal_phones as uip')
                ->joinSub($firstPerUser, 'first', function ($join): void {
                    $join->on('uip.id', '=', 'first.min_id');
                })
                ->select('uip.user_id', 'uip.device_id')
                ->get();

            foreach ($phones as $phone) {
                DB::table('users')
                    ->where('id', $phone->user_id)
                    ->update(['internal_phone_id' => (string) $phone->device_id]);
            }

            Schema::dropIfExists('user_internal_phones');
        }
    }
};
