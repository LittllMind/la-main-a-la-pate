<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectVersion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Integration idempotente du sujet "Videoprotection au Rozier".
 *
 * Sources canoniques :
 *   INSTRUCTION V0.2  SHA : ba4d881902e34ba879add6f79305a871d0a53b273da463207d19d0b2c7940a42
 *   CITIZEN V0.4    SHA : 95a8d1c35ff6160930fd59595fe2eabb4348a8de339b662b000ea692d34ad793 (contrat externe, non local)
 *   CIVITAS EXPORT  SHA : e0e57cd0613e853b655bbfad0912523219d54d75cbd9922ad88ca28eb183ba86
 *   PUBLIC          ABSENT (public_body = NULL).
 */
class VideoprotectionSubjectSeeder extends Seeder
{
    public const INSTRUCTION_SHA = 'ba4d881902e34ba879add6f79305a871d0a53b273da463207d19d0b2c7940a42';
    public const CITIZEN_SHA     = '95a8d1c35ff6160930fd59595fe2eabb4348a8de339b662b000ea692d34ad793';

    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->command?->warn('VideoprotectionSubjectSeeder : aucun admin trouve — skip.');
            return;
        }

        $category = Category::where('name', 'Conseil municipal & Gouvernance')->first();
        if (! $category) {
            $this->command?->error('VideoprotectionSubjectSeeder : categorie non trouvee.');
            return;
        }

        $subCategory = SubCategory::firstOrCreate(
            [
                'category_id' => $category->id,
                'name'        => 'Sécurité & tranquillité publique',
            ],
            [
                'slug'  => Str::slug('Sécurité & tranquillité publique'),
                'color' => '#bae6fd',
            ]
        );

        $body = file_get_contents(
            base_path('database/seeders/data/videoprotection-instruction-v0.2.md')
        );

        if (hash('sha256', $body) !== self::INSTRUCTION_SHA) {
            throw new \RuntimeException(
                'VideoprotectionSubjectSeeder : SHA INSTRUCTION V0.2 non conforme. '
                .'Attendu : '.self::INSTRUCTION_SHA.' ; Obtenu : '.hash('sha256', $body)
            );
        }

        $citizenPath = base_path('database/seeders/data/videoprotection-citizen-v0.4.md');
        $citizenBody = file_exists($citizenPath) ? file_get_contents($citizenPath) : null;
        if ($citizenBody !== null && hash('sha256', $citizenBody) !== self::CITIZEN_SHA) {
            throw new \RuntimeException(
                'VideoprotectionSubjectSeeder : SHA CITIZEN V0.4 non conforme.'
            );
        }

        $subject = Subject::firstOrCreate(
            ['slug' => 'videoprotection'],
            [
                'user_id'          => $admin->id,
                'theme'            => 'Conseil municipal & Gouvernance',
                'category_id'      => $category->id,
                'sub_category_id'  => $subCategory->id,
                'title'            => 'Videoprotection au Rozier',
                'body'             => $body,
                'citizen_body'     => $citizenBody,
                'public_body'      => null,
                'status'           => 'draft',
                'citizen_status'   => 'draft',
                'public_status'    => 'draft',
                'public_is_listed' => false,
                'visibility'       => 'citoyen',
            ]
        );

        if (! $subject->wasRecentlyCreated) {
            $this->command?->info('VideoprotectionSubjectSeeder : sujet existant (id='.$subject->id.') — NO-OP.');
            return;
        }

        SubjectVersion::create([
            'subject_id'     => $subject->id,
            'user_id'        => $admin->id,
            'body'           => $body,
            'citizen_body'   => $citizenBody,
            'public_body'    => null,
            'change_summary' => 'Ingestion initiale INSTRUCTION V0.2 + CITIZEN V0.4 — videoprotection',
        ]);

        $this->command?->info('VideoprotectionSubjectSeeder : sujet cree (id='.$subject->id.').');
    }
}
