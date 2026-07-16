<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11pt; line-height: 1.6; color: #1e293b; }
        h1 { font-size: 18pt; font-weight: 700; margin-bottom: 12pt; border-bottom: 1px solid #cbd5e1; padding-bottom: 6pt; }
        h2 { font-size: 14pt; font-weight: 600; margin-top: 18pt; margin-bottom: 8pt; color: #334155; }
        h3 { font-size: 12pt; font-weight: 600; margin-top: 14pt; margin-bottom: 6pt; color: #475569; }
        table { border-collapse: collapse; width: 100%; margin: 10pt 0; }
        th, td { border: 1px solid #cbd5e1; padding: 6pt 8pt; text-align: left; font-size: 10pt; }
        th { background: #f1f5f9; font-weight: 600; }
        blockquote { margin: 10pt 0; padding: 8pt 12pt; border-left: 3px solid #cbd5e1; background: #f8fafc; color: #475569; font-style: italic; }
        ul, ol { margin: 8pt 0; padding-left: 20pt; }
        li { margin-bottom: 3pt; }
        .meta { font-size: 9pt; color: #64748b; margin-bottom: 16pt; }
    </style>
</head>
<body>
    <div class="meta">Document genere depuis le sujet « {{ $subject->title }} » — {{ now()->format('d/m/Y') }}</div>
    {!! $htmlBody !!}
</body>
</html>