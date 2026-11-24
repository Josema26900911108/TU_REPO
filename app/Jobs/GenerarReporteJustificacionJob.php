<?php

namespace App\Jobs;

use App\Services\Mb51CsvParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GenerarReporteJustificacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $timeout = 900;
    public int    $tries   = 1;

    public function __construct(
        public string $jobId,
        public string $csvPath,
        public int    $fkTienda,
        public string $fechaInicio,
        public string $fechaFin
    ) {}

    public function handle(): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $progressFile = storage_path("app/reportes_justificacion/progress_{$this->jobId}.json");
        $resultFile   = "reportes_justificacion/reporte_{$this->jobId}.xlsx";
        @mkdir(storage_path('app/reportes_justificacion'), 0777, true);

        $this->updateProgress($progressFile, ['estado'=>'procesando', 'mensaje'=>'Ejecutando consultas maestras...']);

        $parser = new Mb51CsvParser();
        $parsed = $parser->parse(storage_path('app/' . $this->csvPath));
        $filas  = $parsed['filas'];

        if (count($filas) === 0) {
            $this->updateProgress($progressFile, ['estado'=>'error','mensaje'=>'El archivo está vacío.']);
            return;
        }

        $skusCsv = collect($filas)->pluck('Material')->map(fn($s)=>trim((string)$s))
            ->filter()->unique()->values()->toArray();
        $skusSql = "'" . implode("','", array_map(fn($s)=>addslashes($s), $skusCsv)) . "'";

        // ============================================================
        // HOJA 1: MB51 vs ETA (Filtrado por clase_movimiento 251 y 221)
        // ============================================================
        $this->updateProgress($progressFile, ['estado'=>'procesando', 'mensaje'=>'Cruzando MB51 vs ETA...']);
        
        $hoja1Raw = DB::select("
            SELECT 
                mb.Orden_Ref AS Orden,
                mb.SKU,
                mb.Cant_MB51 AS Cant_MB51,
                IFNULL(e.Cantidad, 0) AS Cant_ETA,
                (IFNULL(e.Cantidad, 0) - mb.Cant_MB51) AS Diferencia,
                CASE 
                    WHEN IFNULL(e.Cantidad, 0) = 0 THEN 'NO REPORTADO'
                    WHEN IFNULL(e.Cantidad, 0) < mb.Cant_MB51 THEN 'FALTANTE'
                    WHEN IFNULL(e.Cantidad, 0) > mb.Cant_MB51 THEN 'EXCESO'
                    ELSE 'CORRECTO'
                END AS Estatus
            FROM (
                SELECT 
                    TRIM(material) AS SKU,
                    LEFT(REGEXP_REPLACE(TRIM(referencia), '[^0-9]', ''), 8) AS Orden_Ref,
                    ABS(SUM(cantidad)) AS Cant_MB51
                FROM mb51_importaciones
                WHERE fkTienda = {$this->fkTienda}
                  AND fe_contabilizacion BETWEEN '{$this->fechaInicio}' AND '{$this->fechaFin}'
                  AND TRIM(material) IN ({$skusSql})
                  AND clase_movimiento IN ('251', '221') -- FILTRO AGREGADO AQUÍ
                GROUP BY TRIM(material), Orden_Ref
            ) mb
            LEFT JOIN (
                SELECT 
                    TRIM(SKU) AS SKU,
                    LEFT(REGEXP_REPLACE(TRIM(Orden), '[^0-9]', ''), 8) AS Orden_Ref,
                    SUM(Cantidad) AS Cantidad
                FROM ETA
                WHERE fkTienda = {$this->fkTienda}
                  AND created_at BETWEEN '{$this->fechaInicio}' AND '{$this->fechaFin}'
                  AND TRIM(SKU) IN ({$skusSql})
                GROUP BY TRIM(SKU), Orden_Ref
            ) e ON mb.SKU = e.SKU AND mb.Orden_Ref = e.Orden_Ref
            WHERE mb.Cant_MB51 > 0
            ORDER BY mb.Orden_Ref, mb.SKU
        ");

        // ============================================================
        // HOJA 2: MB51 vs MOVS (Filtrado por clase_movimiento 251 y 221)
        // ============================================================
        $this->updateProgress($progressFile, ['estado'=>'procesando', 'mensaje'=>'Cruzando MB51 vs Movimientos...']);
        
        $hoja2Raw = DB::select("
            SELECT 
                mb.Orden_Ref AS Orden,
                mb.SKU,
                mb.Cant_MB51 AS Cant_MB51,
                IFNULL(m.Cantidad, 0) AS Cant_Movs,
                (IFNULL(m.Cantidad, 0) - mb.Cant_MB51) AS Diferencia,
                CASE 
                    WHEN IFNULL(m.Cantidad, 0) = 0 THEN 'NO REPORTADO'
                    WHEN IFNULL(m.Cantidad, 0) < mb.Cant_MB51 THEN 'FALTANTE'
                    WHEN IFNULL(m.Cantidad, 0) > mb.Cant_MB51 THEN 'EXCESO'
                    ELSE 'CORRECTO'
                END AS Estatus
            FROM (
                SELECT 
                    TRIM(material) AS SKU,
                    LEFT(REGEXP_REPLACE(TRIM(referencia), '[^0-9]', ''), 8) AS Orden_Ref,
                    ABS(SUM(cantidad)) AS Cant_MB51
                FROM mb51_importaciones
                WHERE fkTienda = {$this->fkTienda}
                  AND fe_contabilizacion BETWEEN '{$this->fechaInicio}' AND '{$this->fechaFin}'
                  AND TRIM(material) IN ({$skusSql})
                  AND clase_movimiento IN ('251', '221') -- FILTRO AGREGADO AQUÍ
                GROUP BY TRIM(material), Orden_Ref
            ) mb
            LEFT JOIN (
                SELECT 
                    TRIM(mm.SKU) AS SKU,
                    LEFT(REGEXP_REPLACE(TRIM(et.Orden), '[^0-9]', ''), 8) AS Orden_Ref,
                    SUM(mm.cantidad) AS Cantidad
                FROM movimientomateriales mm
                INNER JOIN expedientetecnico et ON et.id = mm.fkExpediente
                WHERE mm.fkTienda = {$this->fkTienda}
                  AND et.ESTATUS = 'C'
                  AND et.FECHAINSTALACION BETWEEN '{$this->fechaInicio}' AND '{$this->fechaFin}'
                  AND TRIM(mm.SKU) IN ({$skusSql})
                GROUP BY TRIM(mm.SKU), Orden_Ref
            ) m ON mb.SKU = m.SKU AND mb.Orden_Ref = m.Orden_Ref
            WHERE mb.Cant_MB51 > 0
            ORDER BY mb.Orden_Ref, mb.SKU
        ");

        $this->updateProgress($progressFile, ['estado'=>'procesando', 'mensaje'=>'Formateando datos...']);

        $infoCsv = collect($filas)->keyBy(fn($f) => trim((string)($f['Material'] ?? '')));

        // Mapeamos los resultados de Hoja 1
        $hoja1 = [];
        foreach ($hoja1Raw as $row) {
            $itemCsv = $infoCsv->get($row->SKU);
            $hoja1[] = [
                'Orden'           => $row->Orden,
                'SKU'             => $row->SKU,
                'Denominación'    => $itemCsv['Denominación'] ?? '',
                'Serie/MAC'       => 'N/A',
                'Centro'          => $itemCsv['Centro'] ?? 'G817',
                'Cant. MB51 (SAP)'=> $row->Cant_MB51,
                'Cant. ETA'       => $row->Cant_ETA,
                'Diferencia'      => $row->Diferencia,
                'Estatus'         => $row->Estatus,
            ];
        }

        // Mapeamos los resultados de Hoja 2
        $hoja2 = [];
        foreach ($hoja2Raw as $row) {
            $itemCsv = $infoCsv->get($row->SKU);
            $hoja2[] = [
                'Orden'           => $row->Orden,
                'SKU'             => $row->SKU,
                'Denominación'    => $itemCsv['Denominación'] ?? '',
                'Serie/MAC'       => 'N/A', 
                'Centro'          => $itemCsv['Centro'] ?? 'G817',
                'Cant. MB51 (SAP)'=> $row->Cant_MB51,
                'Cant. Movs'      => $row->Cant_Movs,
                'Diferencia'      => $row->Diferencia,
                'Estatus'         => $row->Estatus,
            ];
        }

        // ============================================================
        // GENERAR EXCEL
        // ============================================================
        $this->updateProgress($progressFile, ['estado'=>'exportando', 'mensaje'=>'Escribiendo Excel...']);

        Excel::store(new class($hoja1, $hoja2) implements WithMultipleSheets {
            public function __construct(private array $h1, private array $h2) {}
            public function sheets(): array {
                return [
                    new class($this->h1) implements FromArray, WithTitle, WithHeadings {
                        public function __construct(private array $d) {}
                        public function array(): array { return $this->d; }
                        public function headings(): array {
                            return ['Orden','SKU','Denominación','Serie/MAC','Centro','Cant. MB51 (SAP)','Cant. ETA','Diferencia','Estatus'];
                        }
                        public function title(): string { return '1. MB51 vs ETA'; }
                    },
                    new class($this->h2) implements FromArray, WithTitle, WithHeadings {
                        public function __construct(private array $d) {}
                        public function array(): array { return $this->d; }
                        public function headings(): array {
                            return ['Orden','SKU','Denominación','Serie/MAC','Centro','Cant. MB51 (SAP)','Cant. Movs','Diferencia','Estatus'];
                        }
                        public function title(): string { return '2. MB51 vs Movimientos'; }
                    }
                ];
            }
        }, $resultFile, 'local');

        $this->updateProgress($progressFile, [
            'estado'=>'completado',
            'mensaje'=>'Reporte generado exitosamente.',
            'archivo'=>$resultFile,
        ]);
        
        // Liberar conexión de base de datos para evitar el error 2002
        DB::disconnect('mysql');
        
        @unlink(storage_path('app/' . $this->csvPath));
    }
 
    private function updateProgress(string $file, array $data): void
    {
        file_put_contents($file, json_encode($data));
    }
}