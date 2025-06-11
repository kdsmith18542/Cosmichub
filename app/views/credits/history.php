<?php extend_view('layouts/default', ['title' => $title ?? 'Credit Transaction History']); ?>

<div class="container mt-5">
    <h2><?php echo htmlspecialchars($title ?? 'Credit Transaction History'); ?></h2>

    <?php display_flash_messages(); ?>

    <?php if (!empty($transactions)): ?>
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction->created_at->format('Y-m-d H:i:s')); ?></td>
                        <td><?php echo htmlspecialchars($transaction->description); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $transaction->transaction_type))); ?></td>
                        <td>
                            <?php 
                                $amount = htmlspecialchars($transaction->amount);
                                if ($transaction->transaction_type === \App\Models\CreditTransaction::TYPE_DEBIT || $transaction->transaction_type === 'report_deduction') { // Assuming 'report_deduction' might be a type
                                    echo '-' . $amount;
                                } else {
                                    echo '+' . $amount;
                                }
                            ?> 
                            credits
                        </td>
                        <td>
                            <?php if ($transaction->reference_id && $transaction->reference_type): ?>
                                <?php echo htmlspecialchars(ucfirst($transaction->reference_type)) . ': ' . htmlspecialchars($transaction->reference_id); ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="mt-4">You have no credit transactions yet.</p>
    <?php endif; ?>

    <div class="mt-4">
        <a href="<?php echo base_url('/credits'); ?>" class="btn btn-primary">Purchase Credits</a>
        <a href="<?php echo base_url('/dashboard'); ?>" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php end_view(); ?>