@php
    $rows = $rows ?? collect();
@endphp
<div class="bg-white border rounded-lg p-4">
    <h2 class="text-lg font-semibold mb-3">{{ $title }}</h2>
    @if ($rows->isEmpty())
        <p class="text-sm text-slate-500">Aucune donnée.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody class="divide-y">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="py-2 truncate max-w-xs" title="{{ $row->{$col} }}">
                                @if (isset($row->title) && $row->title)
                                    {{ $row->title }}
                                @else
                                    {{ $row->{$col} }}
                                @endif
                            </td>
                            <td class="py-2 text-right font-mono">{{ $row->total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
