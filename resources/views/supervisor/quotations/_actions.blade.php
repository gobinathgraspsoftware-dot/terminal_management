<div class="btn-group" role="group">
    <a href="{{ route('supervisor.quotations.show', $quotation) }}" 
       class="btn btn-sm btn-info" 
       title="View">
        <i class="bi bi-eye"></i>
    </a>
    
    @if($quotation->isEditable())
        <a href="{{ route('supervisor.quotations.edit', $quotation) }}" 
           class="btn btn-sm btn-warning" 
           title="Edit">
            <i class="bi bi-pencil"></i>
        </a>
    @endif
    
    @if($quotation->isDraft())
        <button class="btn btn-sm btn-success submit-approval-btn" 
                data-id="{{ $quotation->id }}"
                title="Submit for Approval">
            <i class="bi bi-check-circle"></i>
        </button>
        
        <button class="btn btn-sm btn-danger delete-btn" 
                data-id="{{ $quotation->id }}"
                title="Delete">
            <i class="bi bi-trash"></i>
        </button>
    @endif
    
    <a href="{{ route('supervisor.quotations.print', $quotation) }}" 
       class="btn btn-sm btn-secondary" 
       target="_blank"
       title="Print">
        <i class="bi bi-printer"></i>
    </a>
</div>
