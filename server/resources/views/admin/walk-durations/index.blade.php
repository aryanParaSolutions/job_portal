@extends('admin.pages.dashboard')

@section('content')
    <div class="container mt-4">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex justify-content-between align-items-center"
                role="alert">
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex justify-content-between align-items-center"
                role="alert">
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <section class="section">
        <div class="container-fluid">
            <div class="title-wrapper pt-30 mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2>Service Durations</h2>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="{{ route('walk-duration.create') }}" class="btn btn-primary">Add Service Duration</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table id="walkDurationsTable" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Sr No</th>
                                <th>Duration</th>
                                <th>Price (₹)</th>
                                <th>Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($walkdurations as $duration)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $duration->duration }}</td>
                                    <td>{{ $duration->price }}</td>
                                    <td>{{ $duration->is_active ? 'Yes' : 'No' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('walk-duration.edit', $duration->id) }}"
                                            class="text-secondary me-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('walk-duration.destroy', $duration->id) }}"
                                            class="text-danger me-3" title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this duration?')">
                                            <i class="fas fa-trash"></i>
                                        </a>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
@endsection

@section('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#walkDurationsTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true
            });
        });
    </script>
@endsection
