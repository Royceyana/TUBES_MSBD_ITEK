<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Pembuat file .xlsx sederhana (tanpa library tambahan, hanya butuh ext-zip bawaan PHP).
 * Dipakai oleh laporan owner (penjualan, laba kotor, stok) agar tombol "Cetak" menghasilkan spreadsheet.
 *
 * Isi sel:
 *   - string            => teks
 *   - int|float         => angka
 *   - ['f' => '=SUM()', 'v' => 123] => rumus (v = nilai cache agar tampil di semua aplikasi)
 *   - null              => kosong
 *
 * Gaya sel (konstanta S_*) dipilih lewat argumen style per baris/sel.
 */
class XlsxExport
{
    public const S_DEFAULT = 0;
    public const S_TITLE = 1;
    public const S_NOTE = 2;
    public const S_HEAD = 3;
    public const S_TEXT = 4;
    public const S_INT = 5;
    public const S_DEC = 6;
    public const S_TOTAL_TEXT = 7;
    public const S_TOTAL_INT = 8;
    public const S_PCT = 9;
    public const S_LABEL = 10;
    public const S_TOTAL_PCT = 11;
    public const S_CENTER = 12;

    /** @var array<int, array<int, array{0: mixed, 1: int}>> baris => kolom => [nilai, gaya] */
    private array $rows = [];

    /** @var array<int, float> */
    private array $widths = [];

    private int $cursor = 1;

    public function __construct(private string $sheetName = 'Laporan')
    {
        // Nama sheet maks 31 karakter dan tanpa karakter terlarang.
        $this->sheetName = mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $sheetName), 0, 31) ?: 'Laporan';
    }

    /** Nomor baris berikutnya yang akan ditulis (mulai dari 1). */
    public function nextRow(): int
    {
        return $this->cursor;
    }

    /** Tambah satu baris. $cells = daftar nilai mulai kolom A; $styles = gaya per kolom (atau satu gaya untuk semua). */
    public function addRow(array $cells, int|array $styles = self::S_DEFAULT): int
    {
        $r = $this->cursor++;
        foreach (array_values($cells) as $i => $v) {
            $s = is_array($styles) ? ($styles[$i] ?? self::S_DEFAULT) : $styles;
            $this->rows[$r][$i + 1] = [$v, $s];
        }

        return $r;
    }

    public function skipRow(int $n = 1): void
    {
        $this->cursor += $n;
    }

    /** @param array<int, float> $widths lebar kolom mulai dari A */
    public function setWidths(array $widths): void
    {
        foreach (array_values($widths) as $i => $w) {
            $this->widths[$i + 1] = $w;
        }
    }

    /** Huruf kolom Excel dari nomor kolom (1 => A). */
    public static function col(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m).$s;
            $n = intdiv($n - 1, 26);
        }

        return $s;
    }

    /**
     * Data pratinjau (HTML) dengan isi yang sama persis dengan berkas .xlsx.
     *
     * @return array{name: string, cols: int, letters: array<int, string>, widths: array<int, int>, rows: array<int, array<int, array{t: string, c: string, span: int}|null>>}
     */
    public function preview(): array
    {
        $cols = max([1, ...array_keys($this->widths), ...array_map(fn ($r) => max(array_keys($r)), $this->rows)]);
        $bare = [self::S_DEFAULT, self::S_TITLE, self::S_NOTE, self::S_LABEL]; // gaya tanpa border

        $out = [];
        for ($r = 1; $r < $this->cursor; $r++) {
            $line = [];
            $cells = $this->rows[$r] ?? [];

            // Baris yang hanya berisi satu teks di kolom A: lebarkan sepanjang tabel (seperti teks meluap di Excel).
            $rest = array_filter(array_slice($cells, 1, null, true), fn ($c) => $c[0] !== null && $c[0] !== '');
            if (isset($cells[1]) && $cells[1][0] !== null && $cells[1][0] !== '' && ! $rest) {
                $line[] = ['t' => $this->previewText($cells[1][0], $cells[1][1]), 'c' => $this->previewClass($cells[1][1], $bare), 'span' => $cols];
                $out[$r] = $line;

                continue;
            }

            for ($c = 1; $c <= $cols; $c++) {
                $line[] = isset($cells[$c])
                    ? ['t' => $this->previewText($cells[$c][0], $cells[$c][1]), 'c' => $this->previewClass($cells[$c][1], $bare), 'span' => 1]
                    : null;
            }
            $out[$r] = $line;
        }

        return [
            'name' => $this->sheetName,
            'cols' => $cols,
            'letters' => array_map(fn ($c) => self::col($c), range(1, $cols)),
            'widths' => array_map(fn ($c) => (int) round(($this->widths[$c] ?? 10) * 7 + 5), range(1, $cols)),
            'rows' => $out,
        ];
    }

    private function previewClass(int $style, array $bare): string
    {
        $k = match ($style) {
            self::S_TITLE => 'title',
            self::S_NOTE => 'note',
            self::S_LABEL => 'label',
            self::S_HEAD => 'head',
            self::S_TOTAL_TEXT, self::S_TOTAL_INT, self::S_TOTAL_PCT => 'total',
            self::S_CENTER => 'center',
            default => 'text',
        };
        $num = in_array($style, [self::S_INT, self::S_DEC, self::S_PCT, self::S_TOTAL_INT, self::S_TOTAL_PCT], true) ? ' num' : '';

        return $k.$num.(in_array($style, $bare, true) ? '' : ' b');
    }

    /** Teks sel sebagaimana tampil di Excel (format angka Indonesia). */
    private function previewText(mixed $v, int $style): string
    {
        if (is_array($v)) {
            $v = $v['v'] ?? null; // rumus: tampilkan nilai hasil
        }
        if ($v === null || $v === '') {
            return '';
        }
        if (! is_int($v) && ! is_float($v)) {
            return (string) $v;
        }

        return match ($style) {
            self::S_DEC => number_format($v, 2, ',', '.'),
            self::S_PCT, self::S_TOTAL_PCT => number_format($v * 100, 1, ',', '.').'%',
            self::S_INT, self::S_TOTAL_INT => number_format($v, 0, ',', '.'),
            default => (string) $v,
        };
    }

    /** Tulis ke berkas sementara lalu kembalikan path-nya. */
    public function save(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP "zip" belum aktif. Aktifkan extension=zip di php.ini lalu restart server.');
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat berkas spreadsheet sementara.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$this->esc($this->sheetName).'" sheetId="1" r:id="rId1"/></sheets>'
            .'<calcPr calcId="191029" fullCalcOnLoad="1"/>'
            .'</workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());
        $zip->close();

        return $path;
    }

    private function sheetXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>';

        if ($this->widths) {
            $xml .= '<cols>';
            foreach ($this->widths as $c => $w) {
                $xml .= '<col min="'.$c.'" max="'.$c.'" width="'.$w.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        ksort($this->rows);
        foreach ($this->rows as $r => $cols) {
            ksort($cols);
            $xml .= '<row r="'.$r.'">';
            foreach ($cols as $c => [$v, $s]) {
                $ref = self::col($c).$r;
                if ($v === null || $v === '') {
                    $xml .= '<c r="'.$ref.'" s="'.$s.'"/>';
                } elseif (is_array($v) && isset($v['f'])) {
                    $cached = isset($v['v']) && is_numeric($v['v']) ? '<v>'.$v['v'].'</v>' : '';
                    $xml .= '<c r="'.$ref.'" s="'.$s.'"><f>'.$this->esc(ltrim($v['f'], '=')).'</f>'.$cached.'</c>';
                } elseif (is_int($v) || is_float($v)) {
                    $xml .= '<c r="'.$ref.'" s="'.$s.'"><v>'.$v.'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'" s="'.$s.'" t="inlineStr"><is><t xml:space="preserve">'.$this->esc((string) $v).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData>'
            .'<pageMargins left="0.5" right="0.5" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            .'<pageSetup paperSize="9" orientation="landscape" fitToHeight="0"/>'
            .'</worksheet>';
    }

    private function stylesXml(): string
    {
        // Indeks cellXfs harus sama dengan konstanta S_* di atas.
        $b = '<border><left style="thin"><color rgb="FFBFA894"/></left><right style="thin"><color rgb="FFBFA894"/></right><top style="thin"><color rgb="FFBFA894"/></top><bottom style="thin"><color rgb="FFBFA894"/></bottom><diagonal/></border>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="3">'
            .'<numFmt numFmtId="164" formatCode="#,##0"/>'
            .'<numFmt numFmtId="165" formatCode="#,##0.00"/>'
            .'<numFmt numFmtId="166" formatCode="0.0%"/>'
            .'</numFmts>'
            .'<fonts count="5">'
            .'<font><sz val="11"/><name val="Arial"/></font>'                                          // 0 normal
            .'<font><b/><sz val="14"/><color rgb="FF5A2410"/><name val="Arial"/></font>'              // 1 judul
            .'<font><i/><sz val="10"/><color rgb="FF6B5B4F"/><name val="Arial"/></font>'              // 2 catatan
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>'              // 3 header
            .'<font><b/><sz val="11"/><name val="Arial"/></font>'                                      // 4 tebal
            .'</fonts>'
            .'<fills count="4">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF5A2410"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEFE3D5"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'.$b.'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="13">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                                                                                                          // 0
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                                                                                            // 1 judul
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                                                                                            // 2 catatan
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' // 3 header
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'                                                                                                          // 4 teks
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'                                                                                  // 5 bulat
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'                                                                                  // 6 desimal
            .'<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'                                                                              // 7 total teks
            .'<xf numFmtId="164" fontId="4" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>'                                                      // 8 total angka
            .'<xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'                                                                                  // 9 persen
            .'<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                                                                                            // 10 label tebal
            .'<xf numFmtId="166" fontId="4" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>'                                                      // 11 total persen
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>'                                                    // 12 tengah
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function esc(string $s): string
    {
        // Buang karakter kontrol yang tidak valid di XML 1.0, lalu escape.
        $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
