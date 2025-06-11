<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Create Your Cosmic Report</h4>
                    <p class="text-muted mb-0">Generate a personalized report with historical events from your birth date</p>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['_flash']['error'])): ?>
                        <div class="alert alert-danger">
                            <?= flash('error') ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['_flash']['success'])): ?>
                        <div class="alert alert-success">
                            <?= flash('success') ?>
                        </div>
                    <?php endif; ?>

                    <form action="/reports" method="POST" id="reportForm">
                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Birth Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="birth_date" 
                                   name="birth_date" 
                                   required 
                                   max="<?= date('Y-m-d') ?>"
                                   value="<?= old('birth_date') ?>">
                            <div class="form-text">We'll find historical events that happened on your birth date</div>
                        </div>

                        <div class="mb-3">
                            <label for="report_title" class="form-label">Report Title</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="report_title" 
                                   name="report_title" 
                                   placeholder="My Cosmic Report" 
                                   value="<?= old('report_title') ?>">
                            <div class="form-text">Optional: Give your report a custom title</div>
                        </div>

                        <div class="mb-3">
                            <label for="include_events" class="form-label">Include Historical Events</label>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="include_events" 
                                       name="include_events" 
                                       value="1" 
                                       checked>
                                <label class="form-check-label" for="include_events">
                                    Include major historical events from your birth date
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="include_births" class="form-label">Include Famous Births</label>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="include_births" 
                                       name="include_births" 
                                       value="1" 
                                       checked>
                                <label class="form-check-label" for="include_births">
                                    Include famous people born on your birth date
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="include_deaths" class="form-label">Include Notable Deaths</label>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="include_deaths" 
                                       name="include_deaths" 
                                       value="1">
                                <label class="form-check-label" for="include_deaths">
                                    Include notable deaths from your birth date
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-magic me-2"></i>Generate My Cosmic Report
                            </button>
                            <a href="/reports" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Reports
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add some client-side validation and UX improvements
document.getElementById('reportForm').addEventListener('submit', function(e) {
    const birthDate = document.getElementById('birth_date').value;
    if (!birthDate) {
        e.preventDefault();
        alert('Please select your birth date.');
        return;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating Report...';
    submitBtn.disabled = true;
    
    // Re-enable button after 30 seconds as fallback
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 30000);
});

// Set max date to today
document.getElementById('birth_date').max = new Date().toISOString().split('T')[0];
</script>

<?php require_once '../app/views/layouts/footer.php'; ?>