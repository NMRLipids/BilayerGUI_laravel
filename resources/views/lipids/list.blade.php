@extends('layouts.app')

@section('content')
    <!-- Main page -->
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-10">
                    <h3 class="text-white text-center mt-0">Lipids</h3>
                        
                        <div class="text-white text-center mt-0">
                        <table class="table table-bordered table-striped table-sm table-glass table-hover">
                            <thead>
                                <tr>
                                    <th>Accession</th>
                                    <th>Chemical name</th>
                                    <th>InChIKey</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lipids as $lipid)
                                    <tr>
                                        <td>{{ $lipid->molecule }}</td>
                                        <td>{{ $lipid->name }}</td>
                                        <td>{{ $lipid->getInchiKeyAttribute() }}</td>
                                        
                                        <td>
                                            <a href="{{ route('lipid.show', $lipid->id) }}" 
                                            class="btn btn-sm btn-primary" title="{{ $lipid->displayTitle() }}">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (empty($showAll))
                    <div class="d-flex justify-content-center">
                        {{ $lipids->appends(request()->query())->links() }}
                    </div>
                    @endif
                </div>
            </div> 
        </div>
@endsection             