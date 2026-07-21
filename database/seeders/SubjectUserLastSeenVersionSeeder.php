<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectUserLastSeenVersionSeeder extends Seeder
{
    public function run(): void
    {
        // For existing subjects, mark the latest version as seen by each visible collaborator and author
        $subjects = DB::table('subjects')->select('id', 'user_id')->get();
        foreach ($subjects as $subject) {
            $latest = DB::table('subject_versions')
                ->where('subject_id', $subject->id)
                ->orderBy('created_at', 'desc')
                ->value('id');
            if (! $latest) {
                continue;
            }

            $userIds = DB::table('subject_collaborators')
                ->where('subject_id', $subject->id)
                ->pluck('user_id')
                ->toArray();
            $userIds[] = $subject->user_id;
            $userIds = array_unique($userIds);

            foreach ($userIds as $uid) {
                DB::table('subject_user_last_seen_versions')->insertOrIgnore([
                    'user_id' => $uid,
                    'subject_id' => $subject->id,
                    'version_id' => $latest,
                    'seen_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
