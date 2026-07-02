<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            color: #1f2937;
            line-height: 1.5;
        }
        h1 {
            font-size: 18pt;
            color: #0f766e;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 6px;
            margin-bottom: 18px;
        }
        h2 {
            font-size: 13pt;
            color: #0f766e;
            margin-top: 20px;
            margin-bottom: 8px;
        }
        p { margin: 8px 0; }
        ul {
            list-style: none;
            padding-left: 0;
            margin: 8px 0;
        }
        li {
            margin: 4px 0;
            padding-left: 20px;
            position: relative;
        }
        li::before {
            content: '☐';
            position: absolute;
            left: 0;
            color: #0f766e;
        }
        blockquote {
            margin: 10px 0;
            padding: 8px 12px;
            border-left: 4px solid #10b981;
            background: #ecfdf5;
            font-style: italic;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10pt;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            font-weight: bold;
        }
        img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 10px 0;
        }
        .meta {
            font-size: 9pt;
            color: #6b7280;
            margin-bottom: 18px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <h1>{{ $subject->title }}</h1>
    <div class="meta">
        Thème : {{ $subject->theme }} — Statut : {{ $subject->status === 'published' ? 'Publié' : 'Brouillon' }} — Export du {{ now()->format('d/m/Y') }}
    </div>

    {!! $body !!}
</body>
</html>
