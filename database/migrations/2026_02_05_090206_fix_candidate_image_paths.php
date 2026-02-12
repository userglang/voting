<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('candidates')
            ->whereNotNull('image')
            ->update([
                'image' => \Illuminate\Support\Facades\DB::raw(
                    "SUBSTRING_INDEX(image, '/', -1)"
                )
            ]);
    }

    public function down(): void
    {
        // If you need to rollback
        \Illuminate\Support\Facades\DB::table('candidates')
            ->whereNotNull('image')
            ->update([
                'image' => \Illuminate\Support\Facades\DB::raw(
                    "CONCAT('candidates/images/', image)"
                )
            ]);
    }
};
