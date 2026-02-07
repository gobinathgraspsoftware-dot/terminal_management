<div class="btn-group" role="group">
    <a href="{{ route('admin.stock-transfers.show', $transfer) }}" 
       class="btn btn-sm btn-info" title="View">
        <i class="bi bi-eye"></i>
    </a>
    
    @if($transfer->status === 'draft')
        @can('edit_stock_transfers')
        <a href="{{ route('admin.stock-transfers.edit', $transfer) }}" 
           class="btn btn-sm btn-warning" title="Edit">
            <i class="bi bi-pencil"></i>
        </a>
        @endcan
    @endif
    
    @if($transfer->status === 'pending_approval')
        @can('approve_stock_transfers')
        <a href="{{ route('admin.stock-transfers.approve-form', $transfer) }}" 
           class="btn btn-sm btn-success" title="Approve">
            <i class="bi bi-check-circle"></i>
        </a>
        @endcan
    @endif
    
    @if(in_array($transfer->status, ['in_transit', 'approved']))
        @can('receive_stock_transfers')
        <a href="{{ route('admin.stock-transfers.receive-form', $transfer) }}" 
           class="btn btn-sm btn-success" title="Receive">
            <i class="bi bi-box-arrow-in-down"></i>
        </a>
        @endcan
    @endif
    
    <a href="{{ route('admin.stock-transfers.print', $transfer) }}" 
       class="btn btn-sm btn-secondary" target="_blank" title="Print">
        <i class="bi bi-printer"></i>
    </a>
</div>
