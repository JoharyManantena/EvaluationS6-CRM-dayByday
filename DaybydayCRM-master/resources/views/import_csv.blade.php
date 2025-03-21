<!-- resources/views/import_csv.blade.php -->
@extends('layouts.master')

@section('heading')
    {{__('Import CSV File')}}
@stop

@section('content')

    <div class="row">
        <form action="{{ route('import_csv') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="col-sm-8">
                <div class="tablet">
                    <div class="tablet__body">
                        <!-- Affichage des messages de succès ou d'erreur -->
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="csv_file" class="control-label thin-weight">@lang('Choose CSV File')</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" required>
                        </div>

                        <div class="form-group">
                            <label for="table_name" class="control-label thin-weight">@lang('Table Name')</label>
                            <input type="text" class="form-control" id="table_name" name="table_name" value="{{ $tableName }}" required>
                        </div>

                        <div class="form-group">
                            <input type="submit" class="btn btn-md btn-brand movedown" value="{{__('Import')}}">
                        </div>
                         
                    </div>
                </div>
            </div>
            
        </form>
    </div>

@stop

@push('scripts')
    <script>
        $(document).ready(function () {
            // Handling form submission if necessary
        });
    </script>
@endpush
