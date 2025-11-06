<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatingsTopUpSeeder extends Seeder
{
    public function run(): void
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '2048M');

        // Hitung jumlah data yang sudah ada
        $currentCount = DB::table('ratings')->count();
        $target = 500000; // total akhir
        $needed = max(0, $target - $currentCount);

        if ($needed === 0) {
            echo "✅ Jumlah data ratings sudah mencapai {$target}, tidak perlu menambah.\n";
            return;
        }

        $batchSize = 1000;
        $numBatches = ceil($needed / $batchSize);

        echo "🔄 Menambahkan {$needed} data baru ke tabel ratings (target: {$target})...\n";

        for ($i = 0; $i < $numBatches; $i++) {
            $ratings = [];

            $currentBatchSize = min($batchSize, $needed - ($i * $batchSize));

            for ($j = 0; $j < $currentBatchSize; $j++) {
                $ratings[] = [
                    'book_id' => rand(1, 100000),
                    'user_identifier' => 'user_' . rand(1, 10000),
                    'rating' => rand(1, 10),
                    'created_at' => now()->subDays(rand(0, 365)),
                    'updated_at' => now(),
                ];
            }

            DB::table('ratings')->insert($ratings);

            echo "✅ Batch " . ($i + 1) . " berhasil disisipkan (" . count($ratings) . " record)\n";

            unset($ratings);
            gc_collect_cycles();
        }

        echo "🎉 Penambahan {$needed} data selesai! Total sekarang: {$target}\n";
    }
}
