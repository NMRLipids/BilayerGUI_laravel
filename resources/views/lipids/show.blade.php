@extends('layouts.app')

@section('content')
 
    <!-- Main page -->
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-10">
                    <hr class="divider divider-light" />
                    <h3 class="text-white text-center mt-0">{{ $entity['name'] }}</h3>
                    <?php 
                        $e2ntity = $entity ?? [];
                        $properties = $entity['properties'] ?? []; 
                        $cross_refs = $entity['cross_references'] ?? [];

                    ?>

                    <!-- Bootstrap Tabs -->
                    <ul class="nav nav-pills justify-content-start" id="lipidTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="properties-tab" data-bs-toggle="tab" data-bs-target="#properties" type="button" role="tab">Properties</button>
                        </li>
                        @if(!empty($cross_refs))
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="crossrefs-tab" data-bs-toggle="tab" data-bs-target="#crossrefs" type="button" role="tab">Cross References</button>
                        </li>
                        @endif
                    </ul>

                    <!-- Tab Contents -->
                    <div class="tab-content bg-dark text-white p-4 rounded-bottom" id="lipidTabContent">
                        
                        <!-- Overview -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm table-dark mb-0">
                                    <tbody>
                                        @foreach ($entity as $key => $value)
                                            @if($key === 'jsonLd' ||
                                            $key === 'id' ||
                                            $key === 'properties' ||
                                            $key === 'cross_references' ||
                                            $key === 'properties_flat')
                                             @continue @endif
                                            <tr>
                                                <th scope="row">{{ ucfirst($key) }}</th>
                                                <td>{{ $value }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Properties -->
                        <div class="tab-pane fade" id="properties" role="tabpanel">
                            @if(!empty($properties))
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-sm table-dark">
                                        <thead>
                                            <tr>
                                                <th scope="col">Name</th>
                                                <th scope="col">Value</th>
                                                <th scope="col">Unit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($properties as $x)
                                                <tr>
                                                    <td>{{ $x->name }}</td>
                                                    <td>{{ $x->value }}</td>
                                                    <td>{{ $x->unit }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p>No properties available.</p>
                            @endif
                        </div>

                        <!-- Cross References -->
                        @if(!empty($cross_refs))
                        <div class="tab-pane fade" id="crossrefs" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm table-dark">
                                    <thead>
                                        <tr>
                                            <th scope="col">Database</th>
                                            <th scope="col">External ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cross_refs as $xref)
                                            <tr>
                                                <td>{{ $xref->database ?? 'Database' }}</td>
                                                <td>
                                                    @if(!empty($xref->url))
                                                        <a href="{{ $xref->url }}" target="_blank" class="text-white-75">{{ $xref->external_id ?? '' }}</a>
                                                    @else
                                                        <a href="https://identifiers.org/{{ urlencode($xref->database) }}/{{ urlencode($xref->external_id) }}" target="_blank" class="text-white-75">{{ $xref->external_id ?? '' }}</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                    </div>
                    <div style="margin-top:1rem; flex:1 0 auto;">
                        @include('bioschemas.json_pre', ['entity' => $entity]) 
                    </div>    
                </div>
            </div>
               

        </div>
    </div>
    <div style="display: block; height: 200%;">
        &nbsp;
    &nbsp;
    </div>



    <!-- Bootstrap core JS--><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Core theme JS-->


@endsection

@section('meta-tags')
    
@endsection

