<?php

namespace App\Console\Commands;

use App\Models\Subject;
use Illuminate\Console\Command;

class ConvertSubjectHtmlToMarkdown extends Command
{
    protected $signature = 'subjects:convert-html-to-markdown {subject? : ID du sujet a convertir}';

    protected $description = 'Convertit le corps HTML d un sujet en markdown';

    public function handle(): int
    {
        $subjectId = $this->argument('subject');

        $query = Subject::query();
        if ($subjectId) {
            $query->where('id', $subjectId);
        }

        $subjects = $query->whereRaw("body LIKE '%<%' AND body LIKE '%>%'")->get();

        foreach ($subjects as $subject) {
            $subject->update([
                'body' => Subject::convertHtmlToMarkdown($subject->body),
            ]);
            $this->info("Converted subject id={$subject->id}");
        }

        return self::SUCCESS;
    }
}
