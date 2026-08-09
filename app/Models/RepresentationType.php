<?php

namespace App\Models;

enum RepresentationType: string
{
    case Original = 'original';
    case Copy = 'copy';
    case Scan = 'scan';
    case Ocr = 'ocr';
    case Transcription = 'transcription';
    case Excerpt = 'excerpt';

    public function label(): string
    {
        return match ($this) {
            self::Original => 'Original',
            self::Copy => 'Copie',
            self::Scan => 'Numérisation',
            self::Ocr => 'OCR',
            self::Transcription => 'Transcription',
            self::Excerpt => 'Extrait',
        };
    }
}
