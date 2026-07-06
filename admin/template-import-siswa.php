<?php
require_once '../includes/admin_auth.php';
requireAdminLogin();

/**
 * Download template import siswa.
 * - format=xlsx (default) : real .xlsx generated via ZipArchive
 * - format=csv            : simple CSV
 */

$format = strtolower(trim((string) ($_GET['format'] ?? 'xlsx')));
if (!in_array($format, ['xlsx', 'csv'], true)) {
    $format = 'xlsx';
}

$headers = ['NISN /NIS', 'NAMA LENGKAP', 'L/P', 'KELAS'];
$sample = ['0134837594 / 007', 'ALFARO SYAHRIL MAULID', 'L', 'Kelas 7A'];

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template-import-siswa.csv"');
    $out = fopen('php://output', 'wb');
    // UTF-8 BOM for Excel compatibility
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    fputcsv($out, $sample);
    fclose($out);
    exit;
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo 'ZipArchive tidak tersedia. Aktifkan ekstensi ZIP di PHP.';
    exit;
}

// Minimal XLSX using inlineStr (no sharedStrings)
$tmp = tempnam(sys_get_temp_dir(), 'kaih_tpl_');
if ($tmp === false) {
    http_response_code(500);
    echo 'Gagal membuat file sementara.';
    exit;
}
$tmpXlsx = $tmp . '.xlsx';
@unlink($tmp);

$zip = new ZipArchive();
if ($zip->open($tmpXlsx, ZipArchive::CREATE) !== true) {
    http_response_code(500);
    echo 'Gagal membuat file template.';
    exit;
}

$zip->addFromString('[Content_Types].xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML);

$zip->addFromString('_rels/.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);

$zip->addFromString('xl/workbook.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Data" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);

$zip->addFromString('xl/_rels/workbook.xml.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML);

$zip->addFromString('xl/styles.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="12"/><color theme="1"/><name val="Times New Roman"/><family val="1"/></font>
  </fonts>
  <fills count="1"><fill><patternFill patternType="none"/></fill></fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thick"/><right style="thick"/><top style="thick"/><bottom style="thick"/>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>
  </cellXfs>
  <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML);

// Build sheet1 with two rows (headers + sample)
$cells = [
    $headers,
    $sample,
];

// Helper to create inline string cell
$makeCell = function (string $col, int $row, string $val, int $styleIndex = 0): string {
    $val = htmlspecialchars($val, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    $ref = $col . $row;
    $styleAttr = $styleIndex > 0 ? (' s="' . $styleIndex . '"') : '';
    return '<c r="' . $ref . '"' . $styleAttr . ' t="inlineStr"><is><t>' . $val . '</t></is></c>';
};

$cols = ['A', 'B', 'C', 'D'];
$rowsXml = '';
for ($ri = 0; $ri < count($cells); $ri++) {
    $rowNum = $ri + 1;
    $rowCells = '';
    for ($ci = 0; $ci < count($cols); $ci++) {
        $styleIndex = ($rowNum === 1) ? 1 : 0; // header style
        $rowCells .= $makeCell($cols[$ci], $rowNum, (string) ($cells[$ri][$ci] ?? ''), $styleIndex);
    }
    $rowsXml .= '<row r="' . $rowNum . '">' . $rowCells . '</row>';
}

$zip->addFromString('xl/worksheets/sheet1.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    {$rowsXml}
  </sheetData>
</worksheet>
XML);

$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="template-import-siswa.xlsx"');
header('Content-Length: ' . filesize($tmpXlsx));
readfile($tmpXlsx);
@unlink($tmpXlsx);
exit;

