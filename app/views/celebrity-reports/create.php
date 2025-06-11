<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container mt-5">
    <h2>Create New Celebrity Report</h2>
    <hr>

    <?php flash('error'); ?>
    <?php flash('success'); ?>

    <form action="/celebrity-reports" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Celebrity Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="birth_date" class="form-label">Birth Date</label>
            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
            <div class="form-text">Please use YYYY-MM-DD format.</div>
        </div>
        <div class="mb-3">
            <label for="archetype_ids" class="form-label">Archetypes</label>
            <select class="form-control" id="archetype_ids" name="archetype_ids[]" multiple required>
                <?php if (isset($archetypes) && !empty($archetypes)): ?>
                    <?php foreach ($archetypes as $archetype): ?>
                        <option value="<?= htmlspecialchars($archetype->id) ?>"><?= htmlspecialchars($archetype->name) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div class="form-text">Hold Ctrl (Windows) or Command (Mac) to select multiple archetypes.</div>
        </div>
        <button type="submit" class="btn btn-primary">Create Report</button>
        <a href="/celebrity-reports" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>