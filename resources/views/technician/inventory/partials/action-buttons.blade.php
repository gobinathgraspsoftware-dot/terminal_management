<div class="btn-group btn-group-sm">
    <a href="{{ route('technician.inventory.show', $serial->id) }}" 
       class="btn btn-sm btn-info" 
       title="View Details">
        <i class="bi bi-eye"></i>
    </a>
    <a href="{{ route('technician.inventory.return-request') }}?serial_id={{ $serial->id }}" 
       class="btn btn-sm btn-danger" 
       title="Request Return">
        <i class="bi bi-arrow-return-left"></i>
    </a>
</div>
