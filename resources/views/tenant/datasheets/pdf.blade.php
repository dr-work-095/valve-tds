<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $snapshot['document_number'] ?? $datasheet->document_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1e293b; font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        .header { display: table; width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 8px; }
        .header > div { display: table-cell; vertical-align: middle; }
        .logo { max-height: 24mm; max-width: 42mm; object-fit: contain; }
        .company { width: 58%; }
        .document { width: 42%; text-align: right; }
        .title { margin: 10mm 0 5mm; font-size: 18pt; }
        .grid { display: table; width: 100%; table-layout: fixed; }
        .grid > div { display: table-cell; width: 50%; vertical-align: top; padding: 3mm; border: 1px solid #cbd5e1; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-size: 8pt; }
        h2 { margin: 5mm 0 2mm; font-size: 12pt; }
        .notes { min-height: 18mm; border: 1px solid #cbd5e1; padding: 3mm; white-space: pre-wrap; }
        .footer { position: fixed; bottom: 0; width: 100%; border-top: 1px solid #cbd5e1; padding-top: 3mm; text-align: center; color: #64748b; font-size: 8pt; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">
            @if (! empty($snapshot['company']['logo_path']))
                @php
                    $logoPath = storage_path('app/tenants/'.$snapshot['company']['logo_path']);
                    $logoDataUri = null;
                    if (is_file($logoPath) && is_readable($logoPath)) {
                        $logoMime = mime_content_type($logoPath) ?: 'image/png';
                        $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoPath));
                    }
                @endphp
                @if ($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="Company logo">
                @endif
            @endif
            <strong>{{ $snapshot['company']['company_name'] ?? '' }}</strong><br>
            {{ $snapshot['company']['address'] ?? '' }}<br>
            {{ $snapshot['company']['tax_id_gst'] ?? '' }}
        </div>
        <div class="document">
            <strong>{{ $datasheet->document_number }}</strong><br>
            {{ $datasheet->revision }}<br>
            {{ $datasheet->created_at?->format('Y-m-d') }}
        </div>
    </div>
    <h1 class="title">{{ $snapshot['model']['name'] ?? 'Valve Datasheet' }}</h1>
    <div class="grid">
        <div>
            <h2>Client / Project</h2>
            <p><strong>Client:</strong> {{ $snapshot['client_name'] }}</p>
            <p><strong>Project:</strong> {{ $snapshot['project_reference'] ?: '-' }}</p>
            <h2>Standards</h2>
            <p>Design: {{ $snapshot['model']['design_standard'] ?: '-' }}</p>
            <p>Testing: {{ $snapshot['model']['testing_standard'] ?: '-' }}</p>
            <p>End connection: {{ $snapshot['model']['end_connection_standard'] ?: '-' }}</p>
        </div>
        <div>
            <h2>Selected Dimension</h2>
            @if (! empty($snapshot['dimension']))
                <table>
                    <tr><th>Size</th><td>{{ $snapshot['dimension']['size_inch'] ?: '-' }}</td></tr>
                    <tr><th>DN</th><td>{{ $snapshot['dimension']['size_dn'] ?: '-' }}</td></tr>
                    <tr><th>Pressure class</th><td>{{ $snapshot['dimension']['pressure_class'] ?: '-' }}</td></tr>
                    <tr><th>End connection</th><td>{{ $snapshot['dimension']['end_connection'] ?: '-' }}</td></tr>
                    <tr><th>Weight</th><td>{{ $snapshot['dimension']['weight_kg'] !== null ? $snapshot['dimension']['weight_kg'].' kg' : '-' }}</td></tr>
                </table>
            @else
                <p>Not configured</p>
            @endif
        </div>
    </div>
    <h2>Bill of Materials</h2>
    <table>
        <thead><tr><th>Part</th><th>Name</th><th>Material</th></tr></thead>
        <tbody>
            @forelse (($snapshot['bom'] ?? []) as $row)
                <tr><td>{{ $row['part_number'] }}</td><td>{{ $row['part_name'] }}</td><td>{{ $row['material_code'] }} - {{ $row['material_name'] }}</td></tr>
            @empty
                <tr><td colspan="3">No BOM rows configured</td></tr>
            @endforelse
        </tbody>
    </table>
    <h2>Notes</h2>
    <div class="notes">{{ $snapshot['notes'] ?: '-' }}</div>
    <div class="footer">{{ $snapshot['company']['footer_text'] ?? '' }}</div>
</body>
</html>
