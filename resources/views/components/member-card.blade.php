{{--
    Supervisor Team Member Card Partial
    Path: resources/views/supervisor/teams/_partials/member-card.blade.php
    Used by: supervisor/teams/index.blade.php
--}}
<div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
            <img src="{{ $member->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($member->name) }}"
                 class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
            <div class="flex-grow-1">
                <h6 class="mb-0">{{ $member->name }}</h6>
                <small class="text-muted">{{ $member->employee_id }}</small>
                <div class="mt-1">
                    <span class="badge bg-{{ $member->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($member->status) }}
                    </span>
                    @if($member->skill_tags)
                        @foreach(array_slice($member->skill_tags, 0, 2) as $tag)
                            <span class="badge bg-light text-dark">{{ $tag }}</span>
                        @endforeach
                    @endif
                </div>
            </div>
            <a href="{{ route('supervisor.teams.show', $member->id) }}" class="btn btn-sm btn-outline-info">
                <i class="bi bi-eye"></i>
            </a>
        </div>
    </div>
</div>
