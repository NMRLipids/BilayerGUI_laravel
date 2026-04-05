@extends('layouts.app')
@section('content')
    <div class="container px-4 px-lg-5">
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-lg-12">
                <h3 class="text-white text-center mt-0">Simulations</h3>

                @if (!empty($trayectorias) && $trayectorias->count())
                    <div class="text-white text-center mt-0">

                    {{-- Toolbar: Best button, lipid filter, pagination --}}
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center">
                            @php
                                $isBest = ($sort ?? 'id') === 'best';
                                $bestDir = ($isBest && ($direction ?? 'asc') === 'desc') ? 'asc' : 'desc';
                            @endphp
                            <a href="{{ route('trayectorias.list', array_merge(request()->except('page', 'sort', 'direction'), ['sort' => 'best', 'direction' => $bestDir])) }}"
                               class="btn btn-sm {{ $isBest ? 'btn-warning' : 'btn-outline-warning' }}"
                               title="Rank product of order-parameter (NMR) and form-factor (X-ray) agreement; missing values rank last">
                                ★ Best{!! $isBest ? ($bestDir === 'asc' ? '&nbsp;<span style="font-size:0.7em;">▼</span>' : '&nbsp;<span style="font-size:0.7em;">▲</span>') : '' !!}
                            </a>
                            <small class="text-white ms-2" style="font-size:0.75em;">Rank(OP) &times; Rank(FF) &mdash; missing data ranks last</small>
                        </div>
                        <form method="GET" action="{{ route('trayectorias.list') }}" class="d-flex align-items-center">
                            @foreach (request()->except('lipid', 'page') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="text" name="lipid" id="lipid-filter" value="{{ request('lipid', '') }}"
                                   class="form-control form-control-sm me-1" style="max-width:160px;"
                                   placeholder="Filter by lipid…" autocomplete="off">
                            <button type="submit" class="btn btn-sm btn-outline-light me-1">Go</button>
                            @if (request('lipid'))
                                <a href="{{ route('trayectorias.list', request()->except('lipid', 'page')) }}"
                                   class="btn btn-sm btn-outline-danger">✕</a>
                            @endif
                        </form>
                        <div class="d-flex align-items-center">
                        <span class="me-2 align-self-center" style="font-size:0.85em;">Show:</span>
                        @foreach ([10, 20, 50, 'all'] as $size)
                            @if ((string)($per_page ?? 10) === (string)$size)
                                <span class="btn btn-sm btn-light me-1 disabled">{{ $size }}</span>
                            @else
                                <a href="{{ route('trayectorias.list', array_merge(request()->except('page', 'per_page'), ['per_page' => $size])) }}"
                                   class="btn btn-sm btn-outline-light me-1">{{ $size }}</a>
                            @endif
                        @endforeach
                        </div>
                    </div>

                    @php
                        $currentSort = $sort ?? 'id';
                        $currentDir = $direction ?? 'asc';
                        function simSortUrl($column, $currentSort, $currentDir) {
                            $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
                            return route('trayectorias.list', array_merge(request()->except('page', 'sort', 'direction'), ['sort' => $column, 'direction' => $dir]));
                        }
                        function simSortIcon($column, $currentSort, $currentDir) {
                            if ($currentSort !== $column) return '&nbsp;<span style="font-size: 0.5em;">▲▼</span>';
                            return $currentDir === 'asc' ? '&nbsp;<span style="font-size: 0.5em;">▲</span>' : '&nbsp;<span style="font-size: 0.5em;">▼</span>';
                        }
                    @endphp

                    <table class="table table-bordered table-striped table-sm table-glass table-hover">
                        <thead>
                            <tr>
                                <th scope="col"><a href="{{ simSortUrl('id', $currentSort, $currentDir) }}" class="text-white text-decoration-none">ID{!! simSortIcon('id', $currentSort, $currentDir) !!}</a></th>
                                <th scope="col"><a href="{{ simSortUrl('temperature', $currentSort, $currentDir) }}" class="text-white text-decoration-none">Temperature{!! simSortIcon('temperature', $currentSort, $currentDir) !!}</a></th>
                                <th scope="col"><a href="{{ simSortUrl('trj_length', $currentSort, $currentDir) }}" class="text-white text-decoration-none">Length{!! simSortIcon('trj_length', $currentSort, $currentDir) !!}</a></th>
                                <th scope="col">Force Field</th>
                                <th scope="col">Lipids</th>
                                <th scope="col"><a href="{{ simSortUrl('op_quality_total', $currentSort, $currentDir) }}" class="text-white text-decoration-none">OP Quality{!! simSortIcon('op_quality_total', $currentSort, $currentDir) !!}</a></th>
                                <th scope="col"><a href="{{ simSortUrl('ff_quality', $currentSort, $currentDir) }}" class="text-white text-decoration-none">FF Quality{!! simSortIcon('ff_quality', $currentSort, $currentDir) !!}</a></th>
                                <th scope="col">Experiments</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trayectorias as $trayectoria)
                            <tr>
                                <td>{{ $trayectoria->id }}</td>
                                <td>{{ $trayectoria->temperature ?? 'N/A' }}</td>
                                <td>{{ round($trayectoria->trj_length ?? 0, 0) }}</td>
                                <td>{{ $trayectoria->campo_de_fuerza?->name ?? 'N/A' }}</td>
                                <td>
                                    @foreach ($trayectoria->lipidos as $lipid)
                                        <a href="{{ route('lipid.show', $lipid->id) }}" title="{{ $lipid->displayTitle() }}">{{ $lipid->molecule }}</a>@if (!$loop->last), @endif
                                    @endforeach
                                </td>
                                <td>{{ optional($trayectoria->analisis)->op_quality_total ?? 'N/A' }}</td>
                                <td>{{ optional($trayectoria->analisis)->ff_quality ?? 'N/A' }}</td>
                                <td>{{ $trayectoria->countExperiments() ?: '-' }}</td>
                                <td><a href="{{ route('trayectorias.show', $trayectoria->id) }}" class="btn btn-primary btn-sm" title="{{ $trayectoria->displayTitle() }}">View</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        {{ $trayectorias->appends(request()->query())->links() }}
                    </div>
                    </div>
                @else
                    <p class="text-white text-center mt-0">No simulations found.</p>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
$(function() {
    $('#lipid-filter').autocomplete({
        source: function(request, response) {
            $.getJSON('/lipids', { term: request.term }, function(data) {
                response($.map(data, function(item) {
                    return { label: item.molecule, value: item.molecule };
                }));
            });
        },
        minLength: 1
    });
});
</script>
@endsection