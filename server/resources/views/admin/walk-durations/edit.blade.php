@extends('admin.pages.dashboard')

@section('content')
    <div class="container mt-4">
        <h2>Edit Service Duration</h2>

        <div class="card mt-3">
            <div class="card-body">
                <form action="{{ route('walk-duration.update', $walkduration->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="duration" class="form-label">Duration</label>
                        <input type="text" name="duration" id="duration" value="{{ $walkduration->duration }}" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label">Price (₹)</label>
                        <input type="number" name="price" id="price" value="{{ $walkduration->price }}" class="form-control" step="1" required>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" {{ $walkduration->is_active ? 'checked' : '' }}>
                        <label for="is_active" class="form-check-label">Active</label>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{ route('walk-duration.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
