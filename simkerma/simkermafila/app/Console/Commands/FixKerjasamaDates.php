<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class FixKerjasamaDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-kerjasama-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Kerjasama dates from text to DATE format.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database migration for Kerjasama dates...');

        // 1. Add new columns
        if (!Schema::hasColumn('kerjasama', 'new_awal')) {
            Schema::table('kerjasama', function (Blueprint $table) {
                $table->date('new_awal')->nullable();
                $table->date('new_akhir')->nullable();
            });
            $this->info('Added temporary DATE columns.');
        }

        // 2. Process data
        $kerjasamas = DB::table('kerjasama')->get();
        $this->info('Parsing and migrating ' . $kerjasamas->count() . ' records...');

        $bar = $this->output->createProgressBar($kerjasamas->count());
        
        $totalFails = 0;

        foreach ($kerjasamas as $row) {
            $update = [];
            
            foreach (['tanggal_awal', 'tanggal_akhir'] as $field) {
                $val = strtolower(trim($row->{$field} ?? ''));
                if (empty($val) || $val === '-' || $val === '0000-00-00' || $val === '0000-00-00 00:00:00' || $val === '0') {
                    $update['new_' . str_replace('tanggal_', '', $field)] = null;
                    continue;
                }

                $val = str_replace(['20227', '20303', '20230', '20205'], ['2027', '2030', '2030', '2020'], $val);
                $val = str_replace('71/2025', '01-07-2025', $val);
                $val = str_replace('6/1/202/', '06-01-2020', $val);

                $dateStr = strtr($val, [
                    'januari' => 'january', 'februari' => 'february', 'maret' => 'march', 'mei' => 'may', 'juni' => 'june',
                    'juli' => 'july', 'agustus' => 'august', 'oktober' => 'october', 'desember' => 'december',
                    'jan' => 'jan', 'feb' => 'feb', 'mar' => 'mar', 'apr' => 'apr', 'mei' => 'may', 'jun' => 'jun',
                    'jul' => 'jul', 'agu' => 'aug', 'sep' => 'sep', 'okt' => 'oct', 'nov' => 'nov', 'des' => 'dec'
                ]);
                
                $parsed = null;
                try {
                    $parsed = \Carbon\Carbon::parse($dateStr);
                } catch (\Exception $e) {
                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr)) {
                        try {
                            $parsed = \Carbon\Carbon::parse(str_replace('/', '-', $dateStr));
                        } catch (\Exception $e2) { }
                    } else if (preg_match('/^TA\s+\d{4}/i', $dateStr) || strpos($dateStr, 'ta ') === 0) {
                        $parsed = null;
                    }
                }
                
                if ($parsed && $parsed->year > 9999) {
                    $parsed->year((int) substr((string)$parsed->year, 0, 4));
                }

                if (isset($parsed) && $parsed instanceof \Carbon\Carbon) {
                    $update['new_' . str_replace('tanggal_', '', $field)] = $parsed->format('Y-m-d');
                } else if (strpos($dateStr, 'ta ') !== 0) {
                    $this->error("\nFailed to parse $field for ID {$row->id}: {$row->{$field}}");
                    $totalFails++;
                }
            }

            if (!empty($update)) {
                DB::table('kerjasama')->where('id', $row->id)->update($update);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($totalFails > 0) {
            $this->error("Completed with $totalFails failures. Stopping before column rename.");
            return;
        }

        $this->info('Data migration successful. Renaming columns...');

        // 3. Rename old text columns to backup
        Schema::table('kerjasama', function (Blueprint $table) {
            $table->renameColumn('tanggal_awal', 'tanggal_awal_old_backup');
            $table->renameColumn('tanggal_akhir', 'tanggal_akhir_old_backup');
        });

        // 4. Rename new DATE columns to final names
        Schema::table('kerjasama', function (Blueprint $table) {
            $table->renameColumn('new_awal', 'tanggal_awal');
            $table->renameColumn('new_akhir', 'tanggal_akhir');
        });

        $this->info('Column rename completed successfully! Database is now using native DATE types.');
    }
}
