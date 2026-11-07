<?php

namespace App\Support\Xlsx;

use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Reads the first worksheet of an .xlsx file into rows of plain values.
 * Handles shared strings, inline strings, numbers and booleans, and keeps
 * cells in their column even when cells in between are empty.
 * Formulas are read as their last calculated value.
 */
class XlsxReader
{
    private const MAX_UNCOMPRESSED_BYTES = 50 * 1024 * 1024;

    /**
     * @return array<int, array<int, string|float|int|bool|null>>  rows (0-based), each row indexed by column (0-based)
     *
     * @throws RuntimeException when the file isn't a readable workbook
     */
    public function read(string $path, int $maxRows = 10000): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('This file is not a valid Excel (.xlsx) workbook.');
        }

        try {
            // Refuse "zip bombs": a small upload that expands to an enormous worksheet
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $total += (int) ($zip->statIndex($i)['size'] ?? 0);
            }
            if ($total > self::MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('This workbook is too large to import. Split it into smaller files.');
            }

            $sheetPath = $this->firstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new RuntimeException('The workbook has no worksheet.');
            }

            $shared = $this->sharedStrings($zip);

            return $this->rows($sheetXml, $shared, $maxRows);
        } finally {
            $zip->close();
        }
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbook && $rels) {
            $wb = $this->xml($workbook);
            $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $first = $wb->xpath('//m:sheets/m:sheet')[0] ?? null;
            $relId = $first ? (string) $first->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] : null;

            if ($relId) {
                $rx = $this->xml($rels);
                foreach ($rx->children() as $rel) {
                    if ((string) $rel['Id'] === $relId) {
                        $target = ltrim((string) $rel['Target'], '/');

                        return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                    }
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $strings = [];
        $reader = new XMLReader();
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        $current = null;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $current = '';
            } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 't' && $current !== null) {
                $current .= $reader->readString();
            } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'rPh') {
                $reader->next(); // phonetic hints are not part of the text
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'si') {
                $strings[] = $current;
                $current = null;
            }
        }
        $reader->close();

        return $strings;
    }

    private function rows(string $xml, array $shared, int $maxRows): array
    {
        $rows = [];
        $reader = new XMLReader();
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);

        $rowIndex = -1;
        $cell = null;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'row') {
                $rowIndex = $reader->getAttribute('r') ? (int) $reader->getAttribute('r') - 1 : $rowIndex + 1;
                if (count($rows) >= $maxRows) {
                    throw new RuntimeException("The file has more than {$maxRows} rows.");
                }
                $rows[$rowIndex] = [];
                $column = -1;
            } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'c') {
                $ref = (string) $reader->getAttribute('r');
                $column = $ref !== '' ? self::columnIndex($ref) : ($column ?? -1) + 1;
                $cell = ['type' => (string) $reader->getAttribute('t'), 'value' => null, 'column' => $column];
                if ($reader->isEmptyElement) {
                    $cell = null;
                }
            } elseif ($cell && $reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'v') {
                $cell['value'] = $reader->readString();
            } elseif ($cell && $reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'is') {
                $cell['value'] = $reader->readString();
            } elseif ($cell && $reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'c') {
                $rows[$rowIndex][$cell['column']] = $this->value($cell, $shared);
                $cell = null;
            }
        }
        $reader->close();

        ksort($rows);

        return array_map(function ($row) {
            if (empty($row)) {
                return [];
            }
            $filled = array_fill(0, max(array_keys($row)) + 1, null);

            return array_replace($filled, $row);
        }, $rows);
    }

    private function value(array $cell, array $shared): string|float|int|bool|null
    {
        $raw = $cell['value'];
        if ($raw === null) {
            return null;
        }

        return match ($cell['type']) {
            's' => $shared[(int) $raw] ?? null,
            'inlineStr', 'str' => (string) $raw,
            'b' => $raw === '1',
            'e' => null,
            default => is_numeric($raw) ? (str_contains($raw, '.') || str_contains(strtolower($raw), 'e') ? (float) $raw : (int) $raw) : (string) $raw,
        };
    }

    /** "C12" → 2 */
    public static function columnIndex(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function xml(string $content): \SimpleXMLElement
    {
        $xml = simplexml_load_string($content, \SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false) {
            throw new RuntimeException('The workbook is damaged.');
        }

        return $xml;
    }
}
