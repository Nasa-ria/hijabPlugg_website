<?php

namespace App\Support\Xlsx;

use RuntimeException;
use XMLWriter;
use ZipArchive;

/**
 * Small, dependency-free writer for Excel .xlsx workbooks (Office Open XML).
 * Supports several sheets, a bold header row, frozen header, column widths,
 * numbers, money (#,##0.00) and plain text. Text is always written as text,
 * so values like "=1+1" are never treated as formulas.
 *
 *   $xlsx = new XlsxWriter();
 *   $xlsx->addSheet('Stock', ['SKU', 'Qty'], [['NSR-1', 4]], ['Qty' => 'integer']);
 *   $xlsx->save($path);
 */
class XlsxWriter
{
    /** Cell style ids (see styles()) */
    private const STYLE_TEXT = 0;
    private const STYLE_HEADER = 1;
    private const STYLE_MONEY = 2;
    private const STYLE_INTEGER = 3;
    private const STYLE_DECIMAL = 4;
    private const STYLE_PERCENT = 5;

    private array $sheets = [];

    /**
     * @param  string[]  $headers
     * @param  iterable<array>  $rows  values in header order
     * @param  array<string, string>  $formats  header => money|integer|decimal|percent|text
     */
    public function addSheet(string $name, array $headers, iterable $rows, array $formats = [], array $widths = []): self
    {
        $name = mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name), 0, 31) ?: 'Sheet' . (count($this->sheets) + 1);
        $this->sheets[] = compact('name', 'headers', 'rows', 'formats', 'widths');

        return $this;
    }

    public function save(string $path): void
    {
        if (empty($this->sheets)) {
            $this->addSheet('Sheet1', [], []);
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create $path");
        }

        // Same entry order as Excel, so file-type detection recognises the workbook
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheet($sheet));
        }
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('docProps/core.xml', $this->core());
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Nasaari Store</Application></Properties>');

        $zip->close();
    }

    private function sheet(array $sheet): string
    {
        $x = new XMLWriter();
        $x->openMemory();
        $x->startDocument('1.0', 'UTF-8', 'yes');
        $x->startElement('worksheet');
        $x->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $x->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        // Freeze the header row
        if ($sheet['headers']) {
            $x->startElement('sheetViews');
            $x->startElement('sheetView');
            $x->writeAttribute('workbookViewId', '0');
            $x->startElement('pane');
            $x->writeAttribute('ySplit', '1');
            $x->writeAttribute('topLeftCell', 'A2');
            $x->writeAttribute('activePane', 'bottomLeft');
            $x->writeAttribute('state', 'frozen');
            $x->endElement();
            $x->endElement();
            $x->endElement();
        }

        if ($sheet['headers']) {
            $x->startElement('cols');
            foreach ($sheet['headers'] as $i => $header) {
                $x->startElement('col');
                $x->writeAttribute('min', (string) ($i + 1));
                $x->writeAttribute('max', (string) ($i + 1));
                $x->writeAttribute('width', (string) ($sheet['widths'][$header] ?? max(10, min(48, mb_strlen($header) + 4))));
                $x->writeAttribute('customWidth', '1');
                $x->endElement();
            }
            $x->endElement();
        }

        $x->startElement('sheetData');
        $rowNumber = 0;

        if ($sheet['headers']) {
            $this->row($x, ++$rowNumber, $sheet['headers'], array_fill(0, count($sheet['headers']), self::STYLE_HEADER), true);
        }

        $styles = array_map(fn ($h) => match ($sheet['formats'][$h] ?? 'auto') {
            'money' => self::STYLE_MONEY,
            'integer' => self::STYLE_INTEGER,
            'decimal' => self::STYLE_DECIMAL,
            'percent' => self::STYLE_PERCENT,
            'text' => -1, // force text
            default => null,
        }, $sheet['headers']);

        foreach ($sheet['rows'] as $values) {
            $this->row($x, ++$rowNumber, array_values($values), $styles);
        }

        $x->endElement(); // sheetData

        if ($sheet['headers'] && $rowNumber > 1) {
            $x->startElement('autoFilter');
            $x->writeAttribute('ref', 'A1:' . self::column(count($sheet['headers']) - 1) . $rowNumber);
            $x->endElement();
        }

        $x->endElement(); // worksheet
        $x->endDocument();

        return $x->outputMemory();
    }

    private function row(XMLWriter $x, int $number, array $values, array $styles, bool $header = false): void
    {
        $x->startElement('row');
        $x->writeAttribute('r', (string) $number);

        foreach ($values as $i => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $style = $styles[$i] ?? null;
            $ref = self::column($i) . $number;
            $x->startElement('c');
            $x->writeAttribute('r', $ref);

            $numeric = (is_int($value) || is_float($value)) && $style !== -1 && ! $header;

            if ($numeric) {
                if ($style !== null) {
                    $x->writeAttribute('s', (string) $style);
                }
                $x->writeElement('v', is_float($value) ? rtrim(rtrim(sprintf('%.10F', $value), '0'), '.') : (string) $value);
            } else {
                if ($header) {
                    $x->writeAttribute('s', (string) self::STYLE_HEADER);
                }
                $x->writeAttribute('t', 'inlineStr');
                $x->startElement('is');
                $x->startElement('t');
                $x->writeAttribute('xml:space', 'preserve');
                $x->text($this->clean(is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value));
                $x->endElement();
                $x->endElement();
            }

            $x->endElement();
        }

        $x->endElement();
    }

    /** Remove characters that are not allowed in XML */
    private function clean(string $text): string
    {
        return preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text) ?? '';
    }

    /** 0 → A, 25 → Z, 26 → AA */
    public static function column(int $index): string
    {
        $name = '';
        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $name = chr(65 + (($index - 1) % 26)) . $name;
        }

        return $name;
    }

    private function contentTypes(): string
    {
        $sheets = '';
        foreach ($this->sheets as $i => $_) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . $sheets . '</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function core(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Nasaari Store</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function workbook(): string
    {
        $sheets = '';
        foreach ($this->sheets as $i => $sheet) {
            $sheets .= '<sheet name="' . htmlspecialchars($sheet['name'], ENT_XML1 | ENT_QUOTES) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets></workbook>';
    }

    private function workbookRels(): string
    {
        $rels = '';
        foreach ($this->sheets as $i => $_) {
            $rels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . (count($this->sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private function styles(): string
    {
        // cellXfs order must match the STYLE_* constants
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF3A1234"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="10" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
