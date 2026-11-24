<?php

namespace App\Services;

class Mb51CsvParser
{
    public const DELIMITERS = [',', ';', "\t", '|'];

    public function parse(string $path): array
    {
        $contenido = file_get_contents($path);

        $encoding = $this->detectarEncoding($contenido);
        if ($encoding !== 'UTF-8') {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
        }

        $contenido = preg_replace('/^\x{FEFF}/u', '', $contenido);

        $delimiter = $this->detectarDelimitador($contenido);

        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        $lineas = array_values(array_filter($lineas, fn ($l) => trim($l) !== ''));

        if (empty($lineas)) {
            return ['encabezado' => [], 'filas' => [], 'delimiter' => $delimiter];
        }

        $encabezado = $this->parsearLinea(array_shift($lineas), $delimiter);
        $encabezado = array_map(fn ($h) => $this->limpiar($h), $encabezado);

        $filas = [];
        foreach ($lineas as $linea) {
            $cols = $this->parsearLinea($linea, $delimiter);
            if (count($cols) !== count($encabezado)) {
                continue;
            }
            $fila = array_combine($encabezado, $cols);
            $fila = array_map(fn ($v) => $this->limpiar($v), $fila);
            $filas[] = $fila;
        }

        return ['encabezado' => $encabezado, 'filas' => $filas, 'delimiter' => $delimiter];
    }

    private function detectarEncoding(string $contenido): string
    {
        if (mb_check_encoding($contenido, 'UTF-8')) {
            return 'UTF-8';
        }
        return 'ISO-8859-1';
    }

    private function detectarDelimitador(string $contenido): string
    {
        $primeraLinea = strtok($contenido, "\r\n");
        $mejor = ',';
        $mejorConteo = -1;

        foreach (self::DELIMITERS as $cand) {
            $conteo = substr_count($primeraLinea, $cand);
            if ($conteo > $mejorConteo) {
                $mejorConteo = $conteo;
                $mejor = $cand;
            }
        }
        return $mejor;
    }

    private function parsearLinea(string $linea, string $delimiter): array
    {
        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $linea);
        rewind($fp);
        $cols = fgetcsv($fp, 0, $delimiter, '"', '\\') ?: [];
        fclose($fp);
        return $cols;
    }

    private function limpiar($valor): string
    {
        if ($valor === null) return '';
        $valor = trim((string) $valor);
        if ($valor === "'" || $valor === 'NULL') return '';
        return $valor;
    }
}