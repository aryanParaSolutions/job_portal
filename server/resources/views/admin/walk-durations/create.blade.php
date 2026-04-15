@extends('admin.pages.dashboard')

@section('content')
    <div class="container mt-4">
        <h2>Add Service Duration</h2>

        <div class="card mt-3">
            <div class="card-body">
                <form action="{{ route('walk-duration.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="duration" class="form-label">Duration</label>
                        <input type="text" name="duration" id="duration" class="form-control" placeholder="e.g. 30 minutes" required>
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label">Price (₹)</label>
                        <input type="number" name="price" id="price" class="form-control" placeholder="e.g. 150" step="0.01" required>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                        <label for="is_active" class="form-check-label">Active</label>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success">Save</button>
                        <a href="{{ route('walk-duration.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
