<?php
/**
 * Credit Transactions History View
 * 
 * @var array $transactions Array of transaction objects
 * @var \App\Models\User $user Current user
 */
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Credit Transaction History</h4>
                    <div>
                        <span class="badge bg-primary">Current Balance: <?= number_format($user->credits) ?> credits</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                        <div class="alert alert-info mb-0">
                            No transactions found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Balance</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $txn): ?>
                                        <tr>
                                            <td>
                                                <?= date('M j, Y', strtotime($txn->created_at)) ?><br>
                                                <small class="text-muted"><?= date('g:i A', strtotime($txn->created_at)) ?></small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($txn->description) ?></strong>
                                                <?php if (!empty($txn->metadata['plan_name'])): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($txn->metadata['plan_name']) ?>
                                                        <?php if (!empty($txn->metadata['payment_intent'])): ?>
                                                            (<?= substr($txn->metadata['payment_intent'], -8) ?>)
                                                        <?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <span class="<?= $txn->amount >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= $txn->amount >= 0 ? '+' : '' ?>
                                                    <?= number_format($txn->amount) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <?= number_format($txn->running_balance) ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = [
                                                    'completed' => 'success',
                                                    'pending' => 'warning',
                                                    'failed' => 'danger',
                                                    'refunded' => 'info',
                                                    'cancelled' => 'secondary'
                                                ][$txn->status] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>">
                                                    <?= ucfirst($txn->status) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <?php if (!empty($txn->metadata['receipt_url'])): ?>
                                                    <a href="<?= htmlspecialchars($txn->metadata['receipt_url']) ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="View Receipt">
                                                        <i class="fas fa-receipt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <nav aria-label="Transaction pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagination['current_page'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" 
                                               href="?page=<?= $pagination['current_page'] - 1 ?>" 
                                               aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" 
                                               href="?page=<?= $pagination['current_page'] + 1 ?>" 
                                               aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
.transaction-row {
    transition: background-color 0.2s ease;
}
.transaction-row:hover {
    background-color: #f8f9fa;
}
</style>
