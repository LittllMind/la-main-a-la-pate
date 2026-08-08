<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export des sujets</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.4;
        }
        .cover {
            text-align: center;
            padding-top: 120px;
        }
        .cover h1 {
            font-size: 24pt;
            color: #0f766e;
            margin-bottom: 12px;
        }
        .cover p {
            font-size: 11pt;
            color: #6b7280;
        }
        .toc {
            margin-top: 40px;
            page-break-after: always;
        }
        .toc h2 { color: #0f766e; }
        .toc ul { list-style: none; padding-left: 0; }
        .toc li { margin: 6px 0; }
        .subject {
            page-break-before: always;
        }
        .subject h1 {
            font-size: 16pt;
            color: #0f766e;
            border-bottom: 1.5px solid #0f766e;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .subject h2 {
            font-size: 12pt;
            color: #0f766e;
            margin-top: 16px;
            margin-bottom: 6px;
        }
        ul {
            list-style: none;
            padding-left: 0;
            margin: 6px 0;
        }
        li {
            margin: 3px 0;
            padding-left: 18px;
            position: relative;
        }
        li::before {
            content: '☐';
            position: absolute;
            left: 0;
            color: #0f766e;
        }
        blockquote {
            margin: 8px 0;
            padding: 6px 10px;
            border-left: 3px solid #10b981;
            background: #ecfdf5;
            font-style: italic;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 9pt;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #f3f4f6; font-weight: bold; }
        img { max-width: 100%; height: auto; display: block; margin: 8px 0; }
        .meta {
            font-size: 8pt;
            color: #6b7280;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="cover">
        <h1>Export des sujets — La Main à la Pâte</h1>
        <p>{{ $subjects->count() }} sujet{{ $subjects->count() > 1 ? 's' : '' }} — {{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="toc">
        <h2>Sommaire</h2>
        <ul>
            @foreach($subjects as $subject)
                <li>{{ $subject->title }}</li>
            @endforeach
        </ul>
    </div>

    @foreach($subjects as $subject)
        @php
            $pdfBody = $subject->bodyFor(auth()->user()) ?? '';
        @endphp
        <div class="subject">
            <h1>{{ $subject->title }}</h1>
            <div class="meta">Thème : {{ $subject->theme }} — Statut : {{ $subject->status === 'published' ? 'Publié' : 'Brouillon' }}</div>

            {!! $pdfBody !!}
        </div>
    @endforeach
</body>
</html>
