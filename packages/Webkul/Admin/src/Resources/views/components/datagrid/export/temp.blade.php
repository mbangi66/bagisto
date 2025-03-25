<table>
    <thead>
        <tr>
            @foreach ($columns as $column)
                <th>{{ $column->getLabel() }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @foreach ($records as $record)
            <tr>
                @foreach($columns as $column)
                    @php $index = $column->getIndex(); @endphp
                    @if ($closure = $column->getClosure())
                        <td>
                            @if ($index == 'items')
                                {{-- Skip images or output a placeholder instead of the image HTML --}}
                                {{ 'Items count: ' . ($record->items_count ?? 'N/A') }}
                            @else
                                {!! $closure($record) !!}
                            @endif
                        </td>
                    @else
                        <td>
                            @if ($index == 'items')
                                {{-- Output alternative text if needed --}}
                                {{ 'Items data not available' }}
                            @else
                                {{ $record->{$index} }}
                            @endif
                        </td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
