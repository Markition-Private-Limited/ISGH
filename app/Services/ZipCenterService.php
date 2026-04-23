<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ZipCenterService
{
    private string $xlsxPath;

    public function __construct()
    {
        $this->xlsxPath = public_path('updated_zip_to_center_mapping.xlsx');
    }

    /**
     * Returns the full ZIP→centers map, cached for 24 hours.
     * Structure: ['77001' => ['Center A', 'Center B'], ...]
     */
    public function getMap(): array
    {
        return Cache::remember('zip_center_map_v2', 86400, function () {
            $spreadsheet = IOFactory::load($this->xlsxPath);
            $sheet       = $spreadsheet->getActiveSheet();
            $map         = [];

            foreach ($sheet->getRowIterator(2) as $row) { // skip header row
                $cells  = $row->getCellIterator();
                $cells->setIterateOnlyExistingCells(false);

                $cols = [];
                foreach ($cells as $cell) {
                    $cols[] = trim((string) $cell->getValue());
                    if (count($cols) >= 3) break;
                }

                $zip    = $cols[0] ?? '';
                $center = $cols[1] ?? '';
                $donation_types = $cols[2] ?? '';

                if ($zip === '' || $center === '') continue;

                // Normalise ZIP to 5 digits
                $zip = str_pad(preg_replace('/\D/', '', $zip), 5, '0', STR_PAD_LEFT);

                $map[$zip]['centers'][] = $center;
                $map[$zip]['donation_types'][] = $donation_types;
            }

            // Deduplicate centers and donation types per ZIP
            foreach ($map as $zip => $data) {
                $map[$zip]['centers'] = array_values(array_unique($data['centers']));
                $map[$zip]['donation_types'] = array_values(array_unique($data['donation_types']));
            }

            return $map;
        });
    }

    /**
     * Look up centers for a given ZIP.
     * Returns [] if the ZIP is not in the mapping.
     */
    public function lookup(string $zip): array
    {
        $zip = str_pad(preg_replace('/\D/', '', $zip), 5, '0', STR_PAD_LEFT);
        return $this->getMap()[$zip] ?? [];
    }

    /** Clear the cache (call after uploading a new XLSX). */
    public function clearCache(): void
    {
        Cache::forget('zip_center_map_v2');
    }
}
