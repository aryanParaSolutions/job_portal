@extends('admin.pages.dashboard')

@section('content')

<section class="section">
    <div class="container-fluid">

        <!-- ========== Title ========== -->
        <div class="title-wrapper pt-30 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2>Edit Dog Service</h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('dog-services.index') }}" class="btn btn-secondary">
                        ← Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- ========== Form Card ========== -->
        <div class="card shadow-sm border-0">
            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('dog-services.update', $service->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Service Name <span class="text-danger">*</span></label>
                            <input type="text" name="service_name" class="form-control" placeholder="Enter service name" 
                                   value="{{ old('service_name', $service->service_name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Thumbnail (optional)</label>
                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            @if($service->thumbnail)
                                <div class="mt-2">
                                    <img src="{{ asset('public/storage/' . $service->thumbnail) }}" width="80" alt="Thumbnail">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" 
                                       {{ old('is_active', $service->is_active) ? 'checked' : '' }}>
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">Update Service</button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</section>

@endsection
