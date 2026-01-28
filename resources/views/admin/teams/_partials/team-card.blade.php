{{-- Team Card for Supervisor --}}
<div class="col-md-6 col-xl-4 mb-4">
    <div class="card h-100 border-0 shadow-sm">
        <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center">
            <img src="{{ $supervisor->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($supervisor->name) }}" class="rounded-circle me-2" style="width: 40px; height: 40px;">
            <div class="flex-grow-1">
                <h6 class="mb-0">{{ $supervisor->name }}</h6>
                <small class="text-muted">{{ $supervisor->employee_id }}</small>
            </div>
            <span class="badge bg-primary">{{ $supervisor->technicians_count }}</span>
        </div>
        
        <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
            @if($supervisor->technicians->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($supervisor->technicians as $member)
                        <li class="list-group-item d-flex align-items-center py-2">
                            <img src="{{ $member->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($member->name) }}" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="mb-0 text-truncate">{{ $member->name }}</p>
                                <small class="text-muted">{{ $member->employee_id }}</small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link btn-sm text-muted" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item view-member" href="#" data-id="{{ $member->id }}"><i class="fas fa-eye me-2"></i> View</a></li>
                                    <li><a class="dropdown-item reassign-technician" href="#" data-id="{{ $member->id }}" data-name="{{ $member->name }}" data-supervisor="{{ $supervisor->id }}"><i class="fas fa-exchange-alt me-2"></i> Reassign</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-warning remove-from-team" href="#" data-id="{{ $member->id }}" data-name="{{ $member->name }}"><i class="fas fa-user-minus me-2"></i> Make Independent</a></li>
                                </ul>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-center text-muted py-4"><i class="fas fa-users fa-2x mb-2 opacity-50"></i><p class="mb-0">No members</p></div>
            @endif
        </div>
        
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-success me-1">{{ $supervisor->technicians->where('status', 'active')->count() }} Active</span>
                <span class="badge bg-secondary">{{ $supervisor->technicians->where('status', '!=', 'active')->count() }} Inactive</span>
            </div>
            <a href="{{ route('admin.teams.show', $supervisor->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-chart-bar me-1"></i> Stats</a>
        </div>
    </div>
</div>
