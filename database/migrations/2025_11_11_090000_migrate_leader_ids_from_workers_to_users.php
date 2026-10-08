<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration attempts to correct leader_id values that refer to workers.id
        // by mapping them to the corresponding workers.user_id when applicable.
        DB::transaction(function () {
            $assignments = DB::table('leader_assignments')->get();

            foreach ($assignments as $a) {
                $leaderId = $a->leader_id;
                if ($leaderId === null) continue;

                // If leader_id already exists in users, nothing to do
                $userExists = DB::table('users')->where('id', $leaderId)->exists();
                if ($userExists) continue;

                // Otherwise check if leader_id refers to a worker id
                $worker = DB::table('workers')->where('id', $leaderId)->first();
                if ($worker?->user_id) {
                    // Update the assignment to point to the user id
                    DB::table('leader_assignments')
                        ->where('id', $a->id)
                        ->update(['leader_id' => $worker->user_id]);
                } else {
                    // If no mapping possible, set to null to avoid FK issues later
                    DB::table('leader_assignments')
                        ->where('id', $a->id)
                        ->update(['leader_id' => null]);
                }
            }
        });
    }

    public function down(): void
    {
        // Best-effort reverse: if leader_id refers to a user that has a worker record
        // and that worker is the only one for that user, set leader_id back to worker.id.
        DB::transaction(function () {
            $assignments = DB::table('leader_assignments')->get();

            foreach ($assignments as $a) {
                $leaderId = $a->leader_id;
                if ($leaderId === null) continue;

                $worker = DB::table('workers')->where('user_id', $leaderId)->first();
                if ($worker) {
                    DB::table('leader_assignments')
                        ->where('id', $a->id)
                        ->update(['leader_id' => $worker->id]);
                }
            }
        });
    }
};
