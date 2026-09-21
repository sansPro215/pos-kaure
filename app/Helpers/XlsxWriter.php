<?php

declare(strict_types=1);

namespace App\Helpers;

use ZipArchive;

/**
 * Clean, lightweight, native XLSX Writer for Cashier / POS Reports.
 * Produces 100% compliant Microsoft Excel OpenXML (.xlsx) files with rich styling,
 * auto-filter, frozen panes, custom number formats, and clean layout without any external dependencies.
 */
class XlsxWriter
{
    private string $sheetTitle = 'Laporan Transaksi';
    private array $columnWidths = [];
    private array $rows = [];
    private array $merges = [];
    private ?string $autoFilterRange = null;
    private ?int $freezeRow = null;

    public function __construct(string $sheetTitle = 'Sheet1')
    {
        $this->sheetTitle = $sheetTitle;
    }

    public function setSheetTitle(string $title): self
    {
        $this->sheetTitle = $title;
        return $this;
    }

    public function setColumnWidths(array $widths): self
    {
        $this->columnWidths = $widths;
        return $this;
    }

    public function setAutoFilter(string $range): self
    {
        $this->autoFilterRange = $range;
        return $this;
    }

    public function setFreezePanes(int $row): self
    {
        $this->freezeRow = $row;
        return $this;
    }

    public function addMerge(string $range): self
    {
        $this->merges[] = $range;
        return $this;
    }

    /**
     * Add a styled row to the sheet
     *
     * @param array $cells Array of cell specs:
     *   Each item can be:
     *     - primitive (string/int/float) -> default string/number cell
     *     - array with keys:
     *         'v' => value (string|int|float|null)
     *         't' => 's' (inlineStr), 'n' (number/currency), 'formula'
     *         's' => styleId (int)
     * @param float|null $height Row height in points
     */
    public function addRow(array $cells, ?float $height = null): self
    {
        $this->rows[] = [
            'cells' => $cells,
            'height' => $height
        ];
        return $this;
    }

    /**
     * Convert 0-indexed column number to Excel column letters (0 => A, 1 => B, 26 => AA, etc.)
     */
    public static function colLetter(int $colIndex): string
    {
        $letter = '';
        $colIndex++;
        while ($colIndex > 0) {
            $modulo = ($colIndex - 1) % 26;
            $letter = chr(65 + $modulo) . $letter;
            $colIndex = (int)(($colIndex - $modulo) / 26);
        }
        return $letter;
    }

    /**
     * Escape string for XML
     */
    private static function xmlEscape(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Build the xl/styles.xml content
     */
    private function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="2">
    <numFmt numFmtId="164" formatCode="&quot;Rp &quot;#,##0;(&quot;Rp &quot;#,##0);&quot;Rp &quot;0"/>
    <numFmt numFmtId="165" formatCode="#,##0"/>
  </numFmts>
  <fonts count="10">
    <!-- 0: Normal 10pt Slate -->
    <font><sz val="10"/><color rgb="FF1E293B"/><name val="Segoe UI"/><family val="2"/></font>
    <!-- 1: Header 10pt Bold White -->
    <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/><family val="2"/></font>
    <!-- 2: Title 14pt Bold Navy -->
    <font><b/><sz val="14"/><color rgb="FF0F172A"/><name val="Segoe UI"/><family val="2"/></font>
    <!-- 3: Subtitle 9pt Regular Muted Slate -->
    <font><sz val="9"/><color rgb="FF64748B"/><name val="Segoe UI"/><family val="2"/></font>
    <!-- 4: Total Label 10pt Bold Dark -->
    <font><b/><sz val="10"/><color rgb="FF0F172A"/><name val="Segoe UI"/><family val="2"/></font>
    <!-- 5: Profit Green 10pt Bold -->
    <font><b/><sz val="10"/><color rgb="FF047857"/><name val="Segoe UI"/><family val="2"/></font>
    <!-- 6: Badge Green Paid 9.5pt Bold -->
    <font><b/><sz val="9.5"/><color rgb="FF065F46"/><name val="Segoe UI"/><family val="2"/></font>
    <!-- 7: Badge Red Cancelled 9.5pt Bold -->
    <font><b/><sz val="9.5"/><color rgb="FF991B1B"/><name val="Segoe UI"/><family val="2"/></font>
    <!-- 8: Card Label 8.5pt Bold Muted -->
    <font><b/><sz val="8.5"/><color rgb="FF475569"/><name val="Segoe UI"/><family val="2"/></font>
    <!-- 9: Card Value 12pt Bold Navy -->
    <font><b/><sz val="12"/><color rgb="FF0F172A"/><name val="Segoe UI"/><family val="2"/></font>
  </fonts>
  <fills count="8">
    <!-- 0: none (required) -->
    <fill><patternFill patternType="none"/></fill>
    <!-- 1: gray125 (required) -->
    <fill><patternFill patternType="gray125"/></fill>
    <!-- 2: Header Dark Slate/Navy #1E293B -->
    <fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/><bgColor indexed="64"/></patternFill></fill>
    <!-- 3: Zebra Alternating Row #F8FAFC -->
    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill>
    <!-- 4: Summary / Total Row #E2E8F0 -->
    <fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/><bgColor indexed="64"/></patternFill></fill>
    <!-- 5: Badge Green Fill #D1FAE5 -->
    <fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/><bgColor indexed="64"/></patternFill></fill>
    <!-- 6: Badge Red Fill #FEE2E2 -->
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/><bgColor indexed="64"/></patternFill></fill>
    <!-- 7: Card Background #F1F5F9 -->
    <fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="5">
    <!-- 0: None -->
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <!-- 1: Light Grid Border #CBD5E1 -->
    <border>
      <left style="thin"><color rgb="FFCBD5E1"/></left>
      <right style="thin"><color rgb="FFCBD5E1"/></right>
      <top style="thin"><color rgb="FFCBD5E1"/></top>
      <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>
      <diagonal/>
    </border>
    <!-- 2: Total Row Border (Top thin #1E293B, Bottom double #1E293B) -->
    <border>
      <left style="thin"><color rgb="FFCBD5E1"/></left>
      <right style="thin"><color rgb="FFCBD5E1"/></right>
      <top style="thin"><color rgb="FF1E293B"/></top>
      <bottom style="double"><color rgb="FF1E293B"/></bottom>
      <diagonal/>
    </border>
    <!-- 3: Card Border (Left accent #2563EB) -->
    <border>
      <left style="medium"><color rgb="FF2563EB"/></left>
      <right style="thin"><color rgb="FFCBD5E1"/></right>
      <top style="thin"><color rgb="FFCBD5E1"/></top>
      <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>
      <diagonal/>
    </border>
    <!-- 4: Thin box border -->
    <border>
      <left style="thin"><color rgb="FFCBD5E1"/></left>
      <right style="thin"><color rgb="FFCBD5E1"/></right>
      <top style="thin"><color rgb="FFCBD5E1"/></top>
      <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="25">
    <!-- 0: Normal Left (no border) -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"><alignment vertical="center"/></xf>
    <!-- 1: Title (14pt Bold) -->
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>
    <!-- 2: Subtitle/Meta (9pt Muted) -->
    <xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>
    <!-- 3: Table Header Left (Dark Fill, White Bold) -->
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
    <!-- 4: Table Header Center (Dark Fill, White Bold) -->
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <!-- 5: Table Header Right (Dark Fill, White Bold) -->
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center" wrapText="1"/></xf>
    <!-- 6: Data Text Left (Normal) -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>
    <!-- 7: Data Text Center -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 8: Data Currency Right ("Rp "#,##0) -->
    <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <!-- 9: Data Number Integer Right (#,##0) -->
    <xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <!-- 10: Zebra Text Left -->
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>
    <!-- 11: Zebra Text Center -->
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 12: Zebra Currency Right ("Rp "#,##0) -->
    <xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <!-- 13: Total Row Label Right (Bold, #E2E8F0 fill, Double bottom) -->
    <xf numFmtId="0" fontId="4" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <!-- 14: Total Row Center (Bold, #E2E8F0 fill, Double bottom) -->
    <xf numFmtId="0" fontId="4" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 15: Total Row Currency Right (Bold, #E2E8F0 fill, Double bottom) -->
    <xf numFmtId="164" fontId="4" fillId="4" borderId="2" xfId="0" applyFont="1" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <!-- 16: Total Row Profit Currency Right (Bold Green, #E2E8F0 fill, Double bottom) -->
    <xf numFmtId="164" fontId="5" fillId="4" borderId="2" xfId="0" applyFont="1" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <!-- 17: Badge PAID Center (Green Pill) -->
    <xf numFmtId="0" fontId="6" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 18: Badge CANCELLED/REFUND Center (Red Pill) -->
    <xf numFmtId="0" fontId="7" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 19: KPI Card Text -->
    <xf numFmtId="0" fontId="8" fillId="7" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>
    <!-- 20: KPI Card Value Currency (12pt Bold Navy) -->
    <xf numFmtId="164" fontId="9" fillId="7" borderId="4" xfId="0" applyFont="1" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>
    <!-- 21: KPI Card Value Profit (12pt Bold Green) -->
    <xf numFmtId="164" fontId="5" fillId="7" borderId="4" xfId="0" applyFont="1" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>
    <!-- 22: KPI Card Value Number (12pt Bold) -->
    <xf numFmtId="165" fontId="9" fillId="7" borderId="4" xfId="0" applyFont="1" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>
    <!-- 23: Zebra Badge PAID Center -->
    <xf numFmtId="0" fontId="6" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 24: Zebra Badge CANCELLED/REFUND Center -->
    <xf numFmtId="0" fontId="7" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
</styleSheet>';
    }

    /**
     * Build the xl/worksheets/sheet1.xml content
     */
    private function buildSheetXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";

        // Freeze Panes if configured
        if ($this->freezeRow !== null && $this->freezeRow > 0) {
            $nextRow = $this->freezeRow + 1;
            $xml .= '  <sheetViews>' . "\n";
            $xml .= '    <sheetView tabSelected="1" workbookViewId="0">' . "\n";
            $xml .= '      <pane ySplit="' . $this->freezeRow . '" topLeftCell="A' . $nextRow . '" activePane="bottomLeft" state="frozen"/>' . "\n";
            $xml .= '    </sheetView>' . "\n";
            $xml .= '  </sheetViews>' . "\n";
        }

        $xml .= '  <sheetFormatPr defaultRowHeight="20"/>' . "\n";

        // Column widths
        if (!empty($this->columnWidths)) {
            $xml .= '  <cols>' . "\n";
            foreach ($this->columnWidths as $idx => $width) {
                $colNum = $idx + 1;
                $xml .= '    <col min="' . $colNum . '" max="' . $colNum . '" width="' . (float)$width . '" customWidth="1"/>' . "\n";
            }
            $xml .= '  </cols>' . "\n";
        }

        // Sheet Data
        $xml .= '  <sheetData>' . "\n";
        $rowNum = 1;
        foreach ($this->rows as $r) {
            $cells = $r['cells'];
            $heightAttr = $r['height'] ? ' ht="' . (float)$r['height'] . '" customHeight="1"' : '';
            $xml .= '    <row r="' . $rowNum . '"' . $heightAttr . '>' . "\n";

            $colIdx = 0;
            foreach ($cells as $cell) {
                $colLetter = self::colLetter($colIdx);
                $cellRef = $colLetter . $rowNum;

                if (!is_array($cell)) {
                    // Primitive string or number
                    if (is_numeric($cell) && !is_string($cell)) {
                        $xml .= '      <c r="' . $cellRef . '" s="8"><v>' . $cell . '</v></c>' . "\n";
                    } else {
                        $valStr = self::xmlEscape((string)$cell);
                        $xml .= '      <c r="' . $cellRef . '" t="inlineStr"><is><t>' . $valStr . '</t></is></c>' . "\n";
                    }
                } else {
                    $val = $cell['v'] ?? null;
                    $style = isset($cell['s']) ? (int)$cell['s'] : 0;
                    $type = $cell['t'] ?? null;

                    if ($val === null || $val === '') {
                        $xml .= '      <c r="' . $cellRef . '" s="' . $style . '"/>' . "\n";
                    } elseif ($type === 'n' || (is_numeric($val) && $type !== 's')) {
                        $xml .= '      <c r="' . $cellRef . '" s="' . $style . '"><v>' . $val . '</v></c>' . "\n";
                    } else {
                        $valStr = self::xmlEscape((string)$val);
                        $xml .= '      <c r="' . $cellRef . '" s="' . $style . '" t="inlineStr"><is><t>' . $valStr . '</t></is></c>' . "\n";
                    }
                }
                $colIdx++;
            }

            $xml .= '    </row>' . "\n";
            $rowNum++;
        }
        $xml .= '  </sheetData>' . "\n";

        // AutoFilter
        if ($this->autoFilterRange) {
            $xml .= '  <autoFilter ref="' . self::xmlEscape($this->autoFilterRange) . '"/>' . "\n";
        }

        // Merges
        if (!empty($this->merges)) {
            $xml .= '  <mergeCells count="' . count($this->merges) . '">' . "\n";
            foreach ($this->merges as $merge) {
                $xml .= '    <mergeCell ref="' . self::xmlEscape($merge) . '"/>' . "\n";
            }
            $xml .= '  </mergeCells>' . "\n";
        }

        $xml .= '</worksheet>';
        return $xml;
    }

    /**
     * Generate the complete binary XLSX content
     */
    public function generate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Tidak dapat membuat file temporary ZIP untuk XLSX.");
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $cleanSheetName = self::xmlEscape(substr($this->sheetTitle, 0, 31));
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . $cleanSheetName . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml
        $zip->addFromString('xl/styles.xml', $this->buildStylesXml());

        // 6. xl/worksheets/sheet1.xml
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->buildSheetXml());

        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return (string)$content;
    }

    /**
     * Send direct HTTP download response
     */
    public function download(string $filename): void
    {
        $content = $this->generate();

        // Clean buffer
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        header('Expires: 0');

        echo $content;
        exit;
    }
}
