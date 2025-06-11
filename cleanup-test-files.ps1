# Script to clean up test email and test files
# Run this from the project root directory

# Files to delete
$filesToDelete = @(
    "phpmailer-direct-test.php",
    "phpmailer-test.php",
    "test-config.php",
    "test-direct-smtp.php",
    "test-email-direct.php",
    "test-email-env.php",
    "test-email-final.php",
    "test-email-manual.php",
    "test-email-simple.php",
    "test-email.php",
    "test-mail.php",
    "test-mailer.php",
    "test-mailtrap.php",
    "test-phpmailer.php",
    "test-smtp-connection.php",
    "test-smtp.php",
    "test-verification.php",
    "tests/EmailVerificationTest.php",
    "tests/Unit/EmailVerificationTest.php",
    "database/migrations/0002_seed_test_user.php"
)

# Remove files
foreach ($file in $filesToDelete) {
    $path = Join-Path -Path $PSScriptRoot -ChildPath $file
    if (Test-Path $path) {
        Write-Host "Removing: $file"
        Remove-Item -Path $path -Force
    } else {
        Write-Host "Not found (skipping): $file"
    }
}

# Check if test controller is safe to remove
$testController = Join-Path -Path $PSScriptRoot -ChildPath "app/controllers/TestController.php"
if (Test-Path $testController) {
    $content = Get-Content -Path $testController -Raw
    if ($content -match 'test|example|dummy|sample') {
        Write-Host "\nFound test controller with test code. Remove it? (y/n)" -ForegroundColor Yellow
        $response = Read-Host
        if ($response -eq 'y') {
            Remove-Item -Path $testController -Force
            Write-Host "Removed test controller"
        }
    }
}

Write-Host "\nCleanup complete." -ForegroundColor Green
