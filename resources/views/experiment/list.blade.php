@extends('layouts.app')
@section('content')
    <!-- Main page -->
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-10">
                    <h3 class="text-white text-center mt-0">
                        Experiments
                    </h3>                 
                    @if (!empty($experiments_list) && $experiments_list->count())
                        <div class="text-white text-center mt-0">

                        {{-- Pagination size selector --}}
                        <div class="d-flex justify-content-end mb-2">
                            <span class="me-2 align-self-center" style="font-size:0.85em;">Show:</span>
                            @foreach ([10, 20, 50, 'all'] as $size)
                                @if ((string)($per_page ?? 10) === (string)$size)
                                    <span class="btn btn-sm btn-light me-1 disabled">{{ $size }}</span>
                                @else
                                    <a href="{{ route('experiments.list', array_merge(request()->except('page', 'per_page'), ['per_page' => $size])) }}"
                                       class="btn btn-sm btn-outline-light me-1">{{ $size }}</a>
                                @endif
                            @endforeach
                        </div>

                        @php
                            $currentSort = $sort ?? 'id';
                            $currentDir = $direction ?? 'asc';
                            function sortUrl($column, $currentSort, $currentDir) {
                                $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
                                return route('experiments.list', array_merge(request()->except('page', 'sort', 'direction'), ['sort' => $column, 'direction' => $dir]));
                            }
                            function sortIcon($column, $currentSort, $currentDir) {
                                if ($currentSort !== $column) return '&nbsp;<span style="font-size: 0.5em;">▲▼</span>';
                                return $currentDir === 'asc' ? '&nbsp;<span style="font-size: 0.5em;">▲</span>' : '&nbsp;<span style="font-size: 0.5em;">▼</span>';
                            }
                        @endphp

                        <table class="table table-bordered table-striped table-sm table-glass table-hover">
                            <thead>
                                <tr>
                                    <th scope="col"><a href="{{ sortUrl('id', $currentSort, $currentDir) }}" class="text-white text-decoration-none">ID{!! sortIcon('id', $currentSort, $currentDir) !!}</a></th>
                                    <th scope="col">Article DOI</th>
                                    <th scope="col">Data DOI</th>
                                    <th scope="col"><a href="{{ sortUrl('type', $currentSort, $currentDir) }}" class="text-white text-decoration-none">Type{!! sortIcon('type', $currentSort, $currentDir) !!}</a></th>
                                    <th scope="col"><a href="{{ sortUrl('temperature', $currentSort, $currentDir) }}" class="text-white text-decoration-none">Temperature{!! sortIcon('temperature', $currentSort, $currentDir) !!}</a></th>
                                    <th scope="col">Lipids</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($experiments_list as $experiment)
                                <tr>
                                    <td>{{ $experiment->id }}</td>
                                    <td>{!! renderDOI($experiment->article_doi) !!}</td>
                                    <td>{!! renderDOI($experiment->data_doi) !!}</td>
                                    <td>{{ $experiment->type }}</td>                                  
                                    <td>{{ $experiment->getTemperature()?->value ?? 'N/A' }}</td>
                                    <td>{!! implode(', ', array_map(fn($lipid) => "<a href=\"" . route('lipid.show', ['lipid_id' => $lipid]) . "\" title=\"Lipid: " . e($lipid) . "\">" . e($lipid) . "</a>", $experiment->getLipids()->toArray())) !!}</td>
                                    <td><a href="{{ route('experiments.show', ['type' => $experiment->type, 'path' => $experiment->path]) }}" class="btn btn-primary btn-sm" title="{{ $experiment->displayTitle() }}">View</a></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-center">
                        {{ $experiments_list->appends(request()->query())->links() }}
                        </div>
                    @else
                        <p class="text-white text-center mt-0">No experiments found.</p>
                    @endif    
                    </div>
                </div>
            </div>
        </div>
@endsection