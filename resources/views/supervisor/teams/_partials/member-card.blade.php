{{-- Supervisor Team Member Card --}}
<div class="col-md-6 col-xl-4 mb-3">
    <div class="card h-100 border hover-shadow" style="transition: all 0.2s ease;">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <img src="{{ $member->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($member->name) }}" 
                    class="rounded-circle me-3" 
                    style="width: 50px; height: 50px; object-fit: cover;">
                <div class="flex-grow-1 overflow-hidden">
                    <h6 class="mb-0 text-truncate">{{ $member->name }}</h6>
                    <small class="text-muted">{{ $member->employee_id }}</small>
                </div>
                @php
                    $statusClass = match($member->status) {
                        'active' => 'success',
                        'inactive' => 'secondary',
                        'suspended' => 'danger',
                        default => 'secondary'
                    };
                @endphp
                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($member->status) }}</span>
            </div>

            {{-- Contact Info --}}
            <div class="mb-3">
                <small class="text-muted d-block mb-1">
                    <i class="fas fa-phone me-1"></i> {{ $member->phone ?? 'No phone' }}
                </small>
                <small class="text-muted d-block text-truncate">
                    <i class="fas fa-envelope me-1"></i> {{ $member->email }}
                </small>
            </div>

            {{-- Coverage --}}
            @if($member->coverage_states && count($member->coverage_states) > 0)
            <div class="mb-3">
                @foreach(array_slice($member->coverage_states, 0, 3) as $state)
                    <span class="badge bg-light text-dark me-1 mb-1" style="font-size: 0.7rem;">{{ $state }}</span>
                @endforeach
                @if(count($member->coverage_states) > 3)
                    <span class="badge bg-secondary" style="font-size: 0.7rem;">+{{ count($member->coverage_states) - 3 }}</span>
                @endif
            </div>
            @endif

            {{-- Skills --}}
            @if($member->skill_tags && count($member->skill_tags) > 0)
            <div class="mb-3">
                @foreach(array_slice($member->skill_tags, 0, 2) as $skill)
                    <span class="badge bg-info me-1 mb-1" style="font-size: 0.7rem;">{{ $skill }}</span>
                @endforeach
                @if(count($member->skill_tags) > 2)
                    <span class="badge bg-dark" style="font-size: 0.7rem;">+{{ count($member->skill_tags) - 2 }}</span>
                @endif
            </div>
            @endif
        </div>

        <div class="card-footer bg-white border-top-0">
            <div class="d-flex justify-content-between">
                {{-- Quick Contact Buttons --}}
                <div class="btn-group btn-group-sm">
                    @if($member->phone)
                        <a href="tel:{{ $member->phone }}" class="btn btn-outline-primary" title="Call">
                            <i class="fas fa-phone"></i>
                        </a>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $member->phone) }}" 
                            target="_blank" class="btn btn-outline-success" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    @endif
                    <a href="mailto:{{ $member->email }}" class="btn btn-outline-info" title="Email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>

                {{-- View Details Button --}}
                <button type="button" class="btn btn-sm btn-primary view-member" data-id="{{ $member->id }}">
                    <i class="fas fa-eye me-1"></i> View
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-2px);
}
</style>
