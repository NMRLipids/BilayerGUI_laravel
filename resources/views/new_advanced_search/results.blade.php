@extends('layouts.app')

<style>
    .filters-list {
        list-style-type: none;
        padding-left: 0;
    }
    .filters-list li {
        display: inline-block;
        margin-right: 10px;
        background-color: #343a40;
        padding: 5px 10px;
        border-radius: 5px;
    }
</style>

@section('content')
   <div class="container">
        <div class="row justify-content-center">
            <div class="">
                <h3 class="text-white text-center">Simulation Search Results</h3>
                <p><a href="{{ route('new_advanced_search.form') }}" class="text-white">Back to Search</a></p>
                @if (!empty($filters))
                <p>Filtered by: 
                    <ul class="filters-list">
                    @foreach ($filters as $filter)
                        <li>{{ $filter }}</li>
                    @endforeach
                    </ul>
                </p>
                @endif
                 @if (session('message'))
                    <div class="alert alert-info">
                        {{ session('message') }}
                    </div>
                @endif
                @if (empty($trayectorias) || $trayectorias->isEmpty())
                    <p class="text-white text-center">No results found.</p>
                @else
                <div class="text-white text-center table-responsive">
                    <table class="table table-bordered table-striped table-sm table-glass table-hover">
                        <thead>
                            <tr>
                                @php
                                    $sortableColumns = [
                                        'id' => 'ID',
                                        'temperature' => 'Temperature',
                                        'length' => 'Length (ps)',
                                        'area_per_lipid' => 'Area per lipid',
                                        'op_quality_total' => 'OP Quality: total',
                                        'ff_quality' => 'Form factor quality',
                                    ];
                                    $currentSort = $sort ?? 'id';
                                    $currentDirection = $direction ?? 'asc';
                                @endphp
                                
                                @foreach($sortableColumns as $column => $label)
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => ($currentSort === $column && $currentDirection === 'asc') ? 'desc' : 'asc']) }}" 
                                        class="text-white text-decoration-none"
                                        title="Sort by {{ $label }} {{ ($currentSort === $column) ? (($currentDirection === 'asc') ? 'descending' : 'ascending') : 'ascending' }}"
                                        data-bs-toggle="tooltip" data-bs-placement="top"  
                                        >
                                            
                                            @if($currentSort === $column)
                                                @if($currentDirection === 'asc')
                                                {{ $label }}&nbsp;<span style="font-size: 0.5em;">▲</span>
                                                 @elseif($currentDirection === 'desc')
                                                {{ $label }}&nbsp;<span style="font-size: 0.5em;">▼</span>
                                                 @else
                                                {{ $label }}&nbsp;<span style="font-size: 0.5em;">▲▼</span>

                                                @endif
                                               
                                            @else
                                               {{ $label }}&nbsp;<span style="font-size: 0.5em;">▲▼</span>

                                            @endif
                                        </a>
                                    </th>
                                @endforeach
                                 <th>Quality: headgroups, tails</th>
                                <th>Software</th>
                               
                                <th>Force field</th>
                                <th>Lipids</th>
                                <th>Ions</th>
                                <th>Experimental Data</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trayectorias as $trayectoria)
                                <tr>
                                    <td>{{ $trayectoria->id }}</td>
                                    <td>{{ $trayectoria->temperature ?? 'N/A' }}</td>
                                    <td>{{ round($trayectoria->trj_length ?? 0, 0) }}</td>
                                    <td>{{ round(optional($trayectoria->analisis)->area_per_lipid ?? 0, 2) }}</td>
                                    <td>{{ optional($trayectoria->analisis)->op_quality_total ?? 'N/A' }}</td>
                                   <td> {{ optional($trayectoria->analisis)->ff_quality ?? 'N/A' }}</td>
                                    <td><ul>
                                        <li>{{ optional($trayectoria->analisis)->op_quality_headgroups ?? 'N/A' }}</li>
                                        <li>{{ optional($trayectoria->analisis)->op_quality_tails ?? 'N/A' }}</li>
                                    </ul></td>
                                     <td>{{ $trayectoria->software ?? 'N/A' }}</td>
                                    <td>{{ optional($trayectoria->campo_de_fuerza)->name ?? 'N/A' }}</td>
                                    <td>
                                        @if ($trayectoria->lipidos->isEmpty())
                                            N/A
                                        @else
                                        @foreach ($trayectoria->lipidos as $lipid)
                                            {{ $lipid->molecule }}@if (!$loop->last), @endif
                                        @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if ($trayectoria->iones->isEmpty())
                                            -
                                        @else
                                        @foreach ($trayectoria->iones as $ion)
                                            {{ $ion->molecule }}@if (!$loop->last), @endif
                                        @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if (($n = $trayectoria->countExperiments()) == 0)
                                            No
                                        @else
                                            {{ $n }}
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('trayectorias.show', $trayectoria->id) }}" class="btn btn-sm btn-primary" title="{{ $trayectoria->displayTitle() }}">View</a>
                               
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $trayectorias->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div> 
    </div>
@endsection

