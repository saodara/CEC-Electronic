@if($bill->status === 'paid')
    <span class="status">Paid</span>
@elseif($bill->isOverdue())
    <span class="status danger">Overdue</span>
@else
    <span class="status warning">Unpaid</span>
@endif
