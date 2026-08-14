@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">User Details</h1>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Name</dt>
                    <dd class="col-sm-9">{{ $user->name }}</dd>

                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $user->email }}</dd>

                    <dt class="col-sm-3">Roles</dt>
                    <dd class="col-sm-9">
                        @forelse ($user->roles as $role)
                            <span class="badge bg-secondary me-1">{{ $role->name }}</span>
                        @empty
                            <span class="text-muted">No role assigned</span>
                        @endforelse
                    </dd>
                </dl>
            </div>
        </div>
    </div>
@endsection
