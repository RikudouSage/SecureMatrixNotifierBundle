<?php
if ($argc < 2) {
    fwrite(STDERR, "Usage: php bin/generate-coverage-summary.php <coverage.xml>\n");
    exit(1);
}

$file = $argv[1];
if (!is_file($file)) {
    fwrite(STDERR, "Coverage file not found: {$file}\n");
    exit(1);
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($file);
if ($xml === false) {
    fwrite(STDERR, "Unable to parse coverage file.\n");
    foreach (libxml_get_errors() as $error) {
        fwrite(STDERR, trim($error->message) . "\n");
    }
    exit(1);
}

function formatPercentage(?float $rate): string
{
    if ($rate === null) {
        return 'N/A';
    }

    return sprintf('%.2f%%', $rate * 100);
}

function formatRatio(?int $covered, ?int $valid): string
{
    if ($covered === null || $valid === null) {
        return 'N/A';
    }

    return sprintf('%d / %d', $covered, $valid);
}

$linesCovered = isset($xml['lines-covered']) ? (int) $xml['lines-covered'] : null;
$linesValid = isset($xml['lines-valid']) ? (int) $xml['lines-valid'] : null;
$branchesCovered = isset($xml['branches-covered']) ? (int) $xml['branches-covered'] : null;
$branchesValid = isset($xml['branches-valid']) ? (int) $xml['branches-valid'] : null;
$lineRate = isset($xml['line-rate']) ? (float) $xml['line-rate'] : null;
$branchRate = isset($xml['branch-rate']) ? (float) $xml['branch-rate'] : null;

if ($linesValid === 0) {
    $lineRate = null;
}

if ($branchesValid === 0) {
    $branchRate = null;
}

$packages = [];
if (isset($xml->packages->package)) {
    foreach ($xml->packages->package as $package) {
        $name = (string) $package['name'];
        $packageLinesCovered = isset($package['lines-covered']) ? (int) $package['lines-covered'] : null;
        $packageLinesValid = isset($package['lines-valid']) ? (int) $package['lines-valid'] : null;
        $packageLineRate = isset($package['line-rate']) ? (float) $package['line-rate'] : null;

        if ($packageLinesValid === 0) {
            $packageLineRate = null;
        }

        $packages[] = [
            'name' => $name !== '' ? $name : '(root)',
            'lines' => formatRatio($packageLinesCovered, $packageLinesValid),
            'lineRate' => formatPercentage($packageLineRate),
        ];
    }
}

$files = [];
if (isset($xml->packages->package)) {
    foreach ($xml->packages->package as $package) {
        if (!isset($package->classes->class)) {
            continue;
        }

        foreach ($package->classes->class as $class) {
            $fileName = (string) $class['filename'];
            $classLinesCovered = isset($class['lines-covered']) ? (int) $class['lines-covered'] : null;
            $classLinesValid = isset($class['lines-valid']) ? (int) $class['lines-valid'] : null;
            $classLineRate = isset($class['line-rate']) ? (float) $class['line-rate'] : null;

            if ($classLinesValid === 0) {
                $classLineRate = null;
            }

            $files[] = [
                'file' => $fileName,
                'lines' => formatRatio($classLinesCovered, $classLinesValid),
                'lineRate' => formatPercentage($classLineRate),
            ];
        }
    }
}

$files = array_slice($files, 0, 10);

echo "# Code Coverage Summary\n\n";
echo "| Metric | Covered / Total | Percentage |\n";
echo "| --- | --- | --- |\n";
echo '| Lines | ' . formatRatio($linesCovered, $linesValid) . ' | ' . formatPercentage($lineRate) . " |\n";
echo '| Branches | ' . formatRatio($branchesCovered, $branchesValid) . ' | ' . formatPercentage($branchRate) . " |\n";

echo "\n";

echo "## Coverage by Package\n\n";
if ($packages === []) {
    echo "No package coverage data available.\n";
} else {
    echo "| Package | Lines | Line % |\n";
    echo "| --- | --- | --- |\n";
    foreach ($packages as $package) {
        echo '| ' . $package['name'] . ' | ' . $package['lines'] . ' | ' . $package['lineRate'] . " |\n";
    }
}

echo "\n";

echo "## Top Files\n\n";
if ($files === []) {
    echo "No file coverage data available.\n";
} else {
    echo "| File | Lines | Line % |\n";
    echo "| --- | --- | --- |\n";
    foreach ($files as $fileSummary) {
        echo '| ' . $fileSummary['file'] . ' | ' . $fileSummary['lines'] . ' | ' . $fileSummary['lineRate'] . " |\n";
    }
}

echo "";
