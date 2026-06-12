<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) { http_response_code(400); echo json_encode(['error'=>'Upload a CSV file']); exit; }

$pdo = getDBConnection();
$file = $_FILES['csv_file']['tmp_name'];
$extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

function parseXlsxFile(string $filePath): array {
    if (!class_exists('ZipArchive')) {
        throw new Exception('ZipArchive is required to import XLSX files');
    }
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new Exception('Cannot open XLSX archive');
    }

    $sharedStrings = [];
    if (($idx = $zip->locateName('xl/sharedStrings.xml')) !== false) {
        $xml = simplexml_load_string($zip->getFromIndex($idx));
        foreach ($xml->si as $si) {
            $sharedStrings[] = (string)$si->t;
        }
    }

    $sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml');
    if ($sheetIndex === false) {
        throw new Exception('Worksheet not found');
    }

    $sheetXml = simplexml_load_string($zip->getFromIndex($sheetIndex));
    $result = [];
    foreach ($sheetXml->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $ref = (string)$cell['r'];
            if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
                continue;
            }
            $col = $m[1];
            $colIndex = 0;
            for ($i = 0; $i < strlen($col); $i++) {
                $colIndex = $colIndex * 26 + (ord($col[$i]) - 64);
            }
            $value = '';
            $type = (string)$cell['t'];
            if ($type === 's') {
                $idx = (int)$cell->v;
                $value = $sharedStrings[$idx] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = (string)$cell->is->t;
            } else {
                $value = (string)$cell->v;
            }
            $rowData[$colIndex - 1] = $value;
        }
        ksort($rowData);
        $result[] = array_values($rowData);
    }
    $zip->close();
    return $result;
}

function normalizeGender(?string $input): ?string {
    if ($input === null || trim($input) === '') {
        return null;
    }
    $input = strtoupper(trim($input));
    $genderMap = [
        'M' => 'Male',
        'F' => 'Female',
        'MALE' => 'Male',
        'FEMALE' => 'Female',
        'OTHER' => 'Other',
        'O' => 'Other'
    ];
    return $genderMap[$input] ?? $input;
}

function parseDOB(?string $input): ?string {
    if ($input === null || trim($input) === '') {
        return null;
    }
    $input = trim($input);
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $input, $m)) {
        // dd/mm/yyyy format
        $day = $m[1];
        $month = $m[2];
        $year = $m[3];
        $formatted = sprintf('%04d-%02d-%02d', $year, $month, $day);
        if (strtotime($formatted) !== false) {
            return $formatted;
        }
    } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $input)) {
        // Already in YYYY-MM-DD format
        if (strtotime($input) !== false) {
            return $input;
        }
    }
    return null;
}

try {
    if ($extension === 'xlsx') {
        $rows = parseXlsxFile($file);
    } elseif ($extension === 'csv') {
        if (($handle = fopen($file, 'r')) === FALSE) {
            throw new Exception('Cannot read CSV file');
        }
        $rows = [];
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $rows[] = $data;
        }
        fclose($handle);
    } else {
        throw new Exception('Unsupported file type. Upload a .csv or .xlsx file.');
    }

    $pdo->beginTransaction();
    $imported = 0;
    $errors = [];
    $rowNumber = 0;
    foreach ($rows as $row) {
        $rowNumber++;
        if ($rowNumber === 1) {
            continue; // skip header
        }
        if (count($row) < 3) {
            continue;
        }

        // Pad the row to ensure we have at least 5 columns
        $row = array_pad($row, 5, '');

        $name = trim($row[0]);
        $adm = strtoupper(trim($row[1]));
        $classIdentifier = trim($row[2]);  // This is 'G1E', 'G2E', etc. or a class name
        $genderRaw = trim($row[3] ?? '');
        $dobRaw = trim($row[4] ?? '');

        // Normalize gender: M/F -> Male/Female, or keep full names
        $gender = normalizeGender($genderRaw);
        
        // Parse DOB: accepts dd/mm/yyyy or YYYY-MM-DD format
        $dob = parseDOB($dobRaw);

        if ($name === '' || $adm === '' || $classIdentifier === '') {
            $errors[] = "Row {$rowNumber}: Missing required data";
            continue;
        }

        // Map the class identifier to an actual class ID in your database
        // Option A: If your classes table has a 'stream_code' column for 'G1E', 'G2E', etc.
        $c = $pdo->prepare("SELECT id FROM classes WHERE stream_code = ? AND school_id = ?");
        $c->execute([$classIdentifier, $schoolId]);
        $classRow = $c->fetch();

        if (!$classRow) {
            // Option B: If no stream_code match, try finding by class name
            $c = $pdo->prepare("SELECT id FROM classes WHERE name = ? AND school_id = ?");
            $c->execute([$classIdentifier, $schoolId]);
            $classRow = $c->fetch();
        }

        if (!$classRow) {
            // Option C: If still no match, parse the identifier
            // Example: 'G1E' -> Grade 1 East
            if (preg_match('/^G(\d+)([A-Z])$/', $classIdentifier, $matches)) {
                $gradeLevel = $matches[1];
                $direction = $matches[2];
                $directionMap = ['E' => 'East', 'W' => 'West', 'N' => 'North', 'S' => 'South', 'C' => 'Central'];
                $directionName = $directionMap[$direction] ?? $direction;
                $className = "Grade {$gradeLevel} {$directionName}";
                $c = $pdo->prepare("SELECT id FROM classes WHERE name = ? AND school_id = ?");
                $c->execute([$className, $schoolId]);
                $classRow = $c->fetch();
            }
        }

        if (!$classRow) {
            $errors[] = "Row {$rowNumber}: Invalid class identifier '{$classIdentifier}' - no matching class found";
            continue;
        }

        $classId = $classRow['id'];

        $stmt = $pdo->prepare("INSERT IGNORE INTO students (school_id, name, admission_number, class_id, gender, dob, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$schoolId, $name, $adm, $classId, $gender, $dob]);
        if ($stmt->rowCount()) {
            $imported++;
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'imported' => $imported, 'errors' => array_slice($errors, 0, 10)]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>