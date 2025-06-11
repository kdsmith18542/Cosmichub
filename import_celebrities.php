<?php
// Usage: php import_celebrities.php celebrities.csv
require_once __DIR__ . '/bootstrap.php';
use App\Models\CelebrityReport;
use App\Models\Archetype;

if ($argc < 2) {
    echo "Usage: php import_celebrities.php celebrities.csv\n";
    exit(1);
}

$csvFile = $argv[1];
if (!file_exists($csvFile)) {
    echo "File not found: $csvFile\n";
    exit(1);
}

$handle = fopen($csvFile, 'r');
if (!$handle) {
    echo "Failed to open file: $csvFile\n";
    exit(1);
}

$header = fgetcsv($handle);
if (!$header) {
    echo "CSV file is empty or invalid.\n";
    exit(1);
}

$created = 0;
while (($row = fgetcsv($handle)) !== false) {
    $data = array_combine($header, $row);
    if (!$data['name'] || !$data['birth_date']) {
        continue;
    }
    $existing = CelebrityReport::where('name', $data['name'])->first();
    if ($existing) {
        echo "Skipping existing: {$data['name']}\n";
        continue;
    }
    $reportData = [
        'zodiac_sign' => $data['zodiac_sign'] ?? '',
        'chinese_zodiac' => $data['chinese_zodiac'] ?? '',
        'birthstone' => $data['birthstone'] ?? '',
        'birth_flower' => $data['birth_flower'] ?? '',
        'rarity_score' => $data['rarity_score'] ?? '',
        'day_in_history' => $data['day_in_history'] ?? '',
        'archetype' => $data['archetype'] ?? '',
        'planetary_influence' => $data['planetary_influence'] ?? '',
        'life_path_number' => $data['life_path_number'] ?? '',
    ];
    $celebrity = new CelebrityReport();
    $celebrity->name = trim($data['name']);
    $celebrity->birth_date = $data['birth_date'];
    $celebrity->report_content = json_encode($reportData);
    $celebrity->slug = CelebrityReport::generateSlug($data['name']);
    $celebrity->save();
    // Attach archetype if present
    if (!empty($data['archetype'])) {
        $archetype = Archetype::where('name', $data['archetype'])->first();
        if ($archetype) {
            $celebrity->archetypes()->sync([$archetype->id]);
        }
    }
    $created++;
    echo "Imported: {$data['name']}\n";
}
fclose($handle);
echo "\nImported $created celebrities.\n";