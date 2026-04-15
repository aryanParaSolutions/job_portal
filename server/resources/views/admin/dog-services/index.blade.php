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
                        <h2>Dog Services</h2>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="{{ route('dog-services.create') }}" class="btn btn-primary">Add New Service</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table id="dogServicesTable" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Sr No</th>
                                <th>Service Name</th>
                                <th>Thumbnail</th>
                                <th>Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($services as $service)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $service->service_name }}</td>
                                    <td class="text-center">
                                        @if ($service->thumbnail)
                                            <img src="{{ asset('public/storage/' . $service->thumbnail) }}" width="60"
                                                alt="">
                                        @endif
                                    </td>
                                    <td>{{ $service->is_active ? 'Yes' : 'No' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('dog-services.edit', $service->id) }}" class="text-secondary me-3"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('dog-services.destroy', $service->id) }}" class="text-danger me-3"
                                            title="Delete"
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
            $('#dogServicesTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true
            });
        });
    </script>
@endsection
