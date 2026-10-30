<?php
/**
 * Unifica nombres de ministerio escritos a mano en inscripciones de talleres
 * (p. ej. «Sara y Michael» vs «Michael y Sara», «Elizabeth» vs «Fabian y Elizabeth»).
 */
class TallerMinisterioNormalizer {

    /**
     * @param array<int, array{id?: int, nombre: string}> $catalogo
     */
    public function __construct(private array $catalogo = []) {
    }

    /**
     * @param array<int, array{id?: int, nombre: string}> $catalogo
     */
    public function conCatalogo(array $catalogo): self {
        $this->catalogo = $catalogo;
        return $this;
    }

    public function normalizar(string $nombre): string {
        $nombre = trim($nombre);
        if ($nombre === '' || $this->esSinMinisterio($nombre)) {
            return 'Sin ministerio asignado';
        }

        $canonCatalogo = $this->buscarEnCatalogo($nombre);
        if ($canonCatalogo !== null) {
            return $canonCatalogo;
        }

        return $nombre;
    }

    /**
     * @return array{changed: bool, nombre: string, id: int}
     */
    public function resolverConId(string $nombre): array {
        $normalizado = $this->normalizar($nombre);
        $id = 0;
        foreach ($this->catalogo as $fila) {
            if (strcasecmp(trim((string)($fila['nombre'] ?? '')), $normalizado) === 0) {
                $id = (int)($fila['id'] ?? 0);
                break;
            }
        }
        return [
            'changed' => $this->claveComparable($nombre) !== $this->claveComparable($normalizado)
                || strcasecmp(trim($nombre), $normalizado) !== 0,
            'nombre' => $normalizado,
            'id' => $id,
        ];
    }

    private function buscarEnCatalogo(string $nombre): ?string {
        if ($this->catalogo === []) {
            return null;
        }

        $clave = $this->claveComparable($nombre);
        $tokens = $this->tokensNombre($nombre);

        // 1) Exacto / mismo conjunto de nombres en otro orden.
        foreach ($this->catalogo as $fila) {
            $canon = trim((string)($fila['nombre'] ?? ''));
            if ($canon === '') {
                continue;
            }
            if ($this->claveComparable($canon) === $clave) {
                return $canon;
            }
        }

        // 2) El texto escrito es un subconjunto de un ministerio del catálogo
        //    (ej. «Elizabeth» → «Fabian y Elizabeth»).
        if ($tokens !== []) {
            $candidatos = [];
            foreach ($this->catalogo as $fila) {
                $canon = trim((string)($fila['nombre'] ?? ''));
                if ($canon === '') {
                    continue;
                }
                $tokensCanon = $this->tokensNombre($canon);
                if ($tokensCanon === []) {
                    continue;
                }
                if ($this->tokensCubiertos($tokens, $tokensCanon) && count($tokens) < count($tokensCanon)) {
                    $candidatos[] = $canon;
                }
            }
            if (count($candidatos) === 1) {
                return $candidatos[0];
            }
        }

        // 3) Parecido tipográfico fuerte contra el catálogo.
        $mejor = null;
        $mejorScore = 0.0;
        foreach ($this->catalogo as $fila) {
            $canon = trim((string)($fila['nombre'] ?? ''));
            if ($canon === '') {
                continue;
            }
            similar_text($clave, $this->claveComparable($canon), $pct);
            if ($pct > $mejorScore) {
                $mejorScore = $pct;
                $mejor = $canon;
            }
        }
        if ($mejor !== null && $mejorScore >= 92.0) {
            return $mejor;
        }

        return null;
    }

    public function claveComparable(string $nombre): string {
        $tokens = $this->tokensNombre($nombre);
        sort($tokens, SORT_STRING);
        return implode('|', $tokens);
    }

    /**
     * @return array<int, string>
     */
    public function tokensNombre(string $nombre): array {
        $texto = $this->sinAcentos(mb_strtolower(trim($nombre)));
        $texto = preg_replace('/[^a-z0-9\s]+/u', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;
        $texto = trim($texto);
        if ($texto === '') {
            return [];
        }

        $partes = preg_split('/\s+y\s+|\s+e\s+|\s*&\s*|\s*,\s*/u', $texto) ?: [];
        $tokens = [];
        foreach ($partes as $parte) {
            $parte = trim((string)$parte);
            if ($parte === '' || in_array($parte, ['y', 'e', 'de', 'del', 'la', 'el', 'los', 'las'], true)) {
                continue;
            }
            // Un token por persona/bloque (puede ser nombre compuesto).
            $tokens[] = preg_replace('/\s+/u', ' ', $parte) ?? $parte;
        }

        // Si no hubo separadores, usar palabras sueltas.
        if ($tokens === []) {
            foreach (preg_split('/\s+/u', $texto) ?: [] as $palabra) {
                $palabra = trim((string)$palabra);
                if ($palabra !== '' && !in_array($palabra, ['y', 'e', 'de', 'del', 'la', 'el'], true)) {
                    $tokens[] = $palabra;
                }
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @param array<int, string> $buscados
     * @param array<int, string> $en
     */
    private function tokensCubiertos(array $buscados, array $en): bool {
        foreach ($buscados as $t) {
            $ok = false;
            foreach ($en as $c) {
                if ($t === $c || str_contains($c, $t) || str_contains($t, $c)) {
                    $ok = true;
                    break;
                }
                similar_text($t, $c, $pct);
                if ($pct >= 88.0) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                return false;
            }
        }
        return true;
    }

    public function esSinMinisterio(string $nombre): bool {
        $n = $this->sinAcentos(mb_strtolower(trim($nombre)));
        $n = preg_replace('/\s+/u', ' ', $n) ?? $n;
        return in_array($n, [
            '',
            'sin ministerio',
            'sin ministerio asignado',
            'ninguno',
            'ninguna',
            'n/a',
            'na',
            '-',
            'no',
            'no aplica',
        ], true);
    }

    private function sinAcentos(string $texto): string {
        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'Ü' => 'u', 'Ñ' => 'n',
        ]);
    }
}
