@extends('layouts.embed')

@section('content')
    <!-- Main page -->
        <div class="">
            <table class="table table-bordered table-striped table-sm">
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
                                 target="_blank"
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
         
@endsection         