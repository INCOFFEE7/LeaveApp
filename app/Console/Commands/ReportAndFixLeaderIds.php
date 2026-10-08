<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportAndFixLeaderIds extends Command
{
    protected $signature = 'leader:report-fix {--apply : Apply the mapping fixes} {--backup : Save a CSV backup of mappings to storage} {--dry-run : Alias of no flags, show preview}';

    protected $description = 'Report leader_id values that point to workers and optionally map them to users.user_id. Safe for production: shows preview and can backup mapping before applying.';

    public function handle(): int
    {
        $this->info('Scanning leader_assignments for inconsistent leader_id values...');

        $total = DB::table('leader_assignments')->count();
        $this->line("Total leader_assignments rows: {$total}");

        // leader_id that already exists in users
        $validCount = DB::table('leader_assignments')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('users')
                  ->whereColumn('users.id', 'leader_assignments.leader_id');
            })->count();

        $this->line("Rows with leader_id present in users: {$validCount}");

        // leader_id that references workers.id
        $legacyRows = DB::table('leader_assignments as la')
            ->leftJoin('users as u', 'u.id', '=', 'la.leader_id')
            ->leftJoin('workers as w', 'w.id', '=', 'la.leader_id')
            ->select('la.id', 'la.leader_id as original_leader_id', 'w.user_id as mapped_user_id')
            ->whereNull('u.id')
            ->whereNotNull('w.id')
            ->get();

        $legacyCount = $legacyRows->count();
        $this->line("Rows where leader_id seems like workers.id (legacy): {$legacyCount}");

        // rows unmapped (leader_id neither in users nor workers)
        $unmappedCount = DB::table('leader_assignments as la')
            ->leftJoin('users as u', 'u.id', '=', 'la.leader_id')
            ->leftJoin('workers as w', 'w.id', '=', 'la.leader_id')
            ->whereNull('u.id')
            ->whereNull('w.id')
            ->count();

        $this->line("Rows with leader_id neither in users nor workers: {$unmappedCount}");

        if ($legacyCount === 0) {
            $this->info('No legacy mappings detected. No action required.');
            return 0;
        }

        // Show sample of mappings (limit 25)
        $this->info('Sample mappings (legacy worker_id -> users.user_id):');
        $this->table([
            'assignment_id', 'legacy_leader_id', 'mapped_user_id'
        ], $legacyRows->take(25)->map(function ($r) {
            return [
                'assignment_id' => $r->id,
                'legacy_leader_id' => $r->original_leader_id,
                'mapped_user_id' => $r->mapped_user_id,
            ];
        })->toArray());

        if ($this->option('backup')) {
            $filename = 'leader_assignment_mappings_' . now()->format('Ymd_His') . '.csv';
            $path = storage_path('app/' . $filename);
            $this->info("Creating backup CSV at storage/app/{$filename}...");

            $handle = fopen($path, 'w');
            fputcsv($handle, ['assignment_id', 'original_leader_id', 'mapped_user_id']);
            foreach ($legacyRows as $r) {
                fputcsv($handle, [$r->id, $r->original_leader_id, $r->mapped_user_id]);
            }
            fclose($handle);

            $this->info('Backup saved.');
        }

        if ($this->option('apply')) {
            $this->info('Applying mapping fixes...');
            DB::beginTransaction();
            try {
                $updated = 0;
                foreach ($legacyRows as $r) {
                    if ($r->mapped_user_id) {
                        DB::table('leader_assignments')
                            ->where('id', $r->id)
                            ->update(['leader_id' => $r->mapped_user_id]);
                        $updated++;
                    } else {
                        // set null to avoid FK issues later
                        DB::table('leader_assignments')
                            ->where('id', $r->id)
                            ->update(['leader_id' => null]);
                    }
                }
                DB::commit();
                $this->info("Applied mappings. Updated rows: {$updated}");
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error('Error applying mappings: ' . $e->getMessage());
                return 2;
            }
        } else {
            $this->info('No changes applied. Run with --apply to update records.');
        }

        $this->info('Done.');
        return 0;
    }
}
