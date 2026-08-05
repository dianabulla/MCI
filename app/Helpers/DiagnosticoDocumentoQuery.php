<?php
/**
 * Consultas de diagnóstico para Numero_Documento en persona.
 */
class DiagnosticoDocumentoQuery
{
    /** @var bool|null */
    private static $usaRegexpReplace = null;

    public static function soportaRegexpReplace(PDO $pdo): bool
    {
        if (self::$usaRegexpReplace !== null) {
            return self::$usaRegexpReplace;
        }
        try {
            $row = $pdo->query("SELECT REGEXP_REPLACE('a1b2', '[^0-9]', '') AS d")->fetch(PDO::FETCH_ASSOC);
            self::$usaRegexpReplace = isset($row['d']) && (string)$row['d'] === '12';
        } catch (Throwable $e) {
            self::$usaRegexpReplace = false;
        }
        return self::$usaRegexpReplace;
    }

    public static function sqlExprCampo(string $campo, ?string $alias = 'p'): string
    {
        $ref = ($alias !== null && $alias !== '') ? "{$alias}.{$campo}" : $campo;
        $col = "TRIM(COALESCE({$ref}, ''))";
        if (self::$usaRegexpReplace) {
            return "REGEXP_REPLACE({$col}, '[^0-9]', '')";
        }
        foreach ([' ', '.', ',', '-', '+', '(', ')', '/'] as $ch) {
            $col = "REPLACE({$col}, '{$ch}', '')";
        }
        return $col;
    }

    public static function sqlDocDigitos(string $alias = 'p'): string
    {
        return self::sqlExprCampo('Numero_Documento', $alias);
    }

    public static function sqlTelefonoDigitos(string $alias = 'p'): string
    {
        return self::sqlExprCampo('Telefono', $alias);
    }

    public static function sqlWhereTelefonoEnDocumento(): string
    {
        $doc = self::sqlDocDigitos('p');
        $tel = self::sqlTelefonoDigitos('p');
        return "(
            {$doc} REGEXP '^3[0-9]{9}$'
            OR ({$tel} <> '' AND {$doc} = {$tel})
            OR {$doc} REGEXP '^573[0-9]{9}$'
        )";
    }

    public static function sqlWhereVacio(): string
    {
        return "(p.Numero_Documento IS NULL OR TRIM(p.Numero_Documento) = '')";
    }

    public static function sqlWhereCincoDigitos(): string
    {
        return "TRIM(COALESCE(p.Numero_Documento, '')) <> '' AND " . self::sqlDocDigitos('p') . " REGEXP '^[0-9]{5}$'";
    }

    public static function sqlJoinsLiderMinisterio(): string
    {
        return "
            LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
            LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
        ";
    }

    public static function sqlCamposLiderMinisterio(bool $agregarMax = false): string
    {
        $lider = "TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, '')))";
        $ministerio = "COALESCE(m.Nombre_Ministerio, '')";
        if ($agregarMax) {
            return "
                MAX({$lider}) AS nombre_lider,
                MAX({$ministerio}) AS nombre_ministerio
            ";
        }
        return "
            {$lider} AS nombre_lider,
            {$ministerio} AS nombre_ministerio
        ";
    }

    public static function sqlSubqueryAnomaliasUnion(): string
    {
        $doc = self::sqlExprCampo('Numero_Documento', null);
        $tel = self::sqlExprCampo('Telefono', null);
        $whereTel = "(
            {$doc} REGEXP '^3[0-9]{9}$'
            OR ({$tel} <> '' AND {$doc} = {$tel})
            OR {$doc} REGEXP '^573[0-9]{9}$'
        )";

        return "
            SELECT Id_Persona, 'documento_vacio' AS tipo FROM persona
            WHERE Numero_Documento IS NULL OR TRIM(Numero_Documento) = ''
            UNION
            SELECT Id_Persona, 'documento_solo_5_digitos' FROM persona
            WHERE TRIM(COALESCE(Numero_Documento, '')) <> ''
              AND {$doc} REGEXP '^[0-9]{5}$'
            UNION
            SELECT Id_Persona, 'documento_parece_telefono' FROM persona
            WHERE TRIM(COALESCE(Numero_Documento, '')) <> ''
              AND {$whereTel}
        ";
    }

    public static function obtenerResumen(PDO $pdo): array
    {
        self::soportaRegexpReplace($pdo);
        $whereTel = self::sqlWhereTelefonoEnDocumento();
        $sql = "SELECT 'documento_vacio' AS tipo, COUNT(*) AS total FROM persona p WHERE " . self::sqlWhereVacio() . "
                UNION ALL
                SELECT 'documento_solo_5_digitos', COUNT(*) FROM persona p WHERE " . self::sqlWhereCincoDigitos() . "
                UNION ALL
                SELECT 'documento_parece_telefono', COUNT(*) FROM persona p
                WHERE TRIM(COALESCE(p.Numero_Documento, '')) <> '' AND {$whereTel}";
        $out = [];
        foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(string)$row['tipo']] = (int)$row['total'];
        }
        return $out;
    }

    public static function obtenerFilas(PDO $pdo, string $tipo): array
    {
        self::soportaRegexpReplace($pdo);
        $doc = self::sqlDocDigitos('p');
        $tel = self::sqlTelefonoDigitos('p');
        $whereTel = self::sqlWhereTelefonoEnDocumento();

        $joins = self::sqlJoinsLiderMinisterio();
        $liderMin = self::sqlCamposLiderMinisterio(false);

        $selectBase = "
            p.Id_Persona,
            TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS nombre_completo,
            p.Tipo_Documento,
            p.Numero_Documento,
            {$doc} AS doc_solo_digitos,
            p.Telefono,
            {$tel} AS telefono_solo_digitos,
            {$liderMin},
            p.Estado_Cuenta,
            p.Es_Antiguo,
            p.Id_Celula,
            p.Id_Ministerio,
            p.Proceso,
            p.Fecha_Registro
        ";

        switch ($tipo) {
            case 'vacio':
                $sql = "SELECT 'documento_vacio' AS tipo_anomalia, '' AS motivo, {$selectBase}
                        FROM persona p {$joins}
                        WHERE " . self::sqlWhereVacio() . " ORDER BY p.Id_Persona";
                break;
            case '5_digitos':
                $sql = "SELECT 'documento_solo_5_digitos' AS tipo_anomalia, '' AS motivo, {$selectBase}
                        FROM persona p {$joins}
                        WHERE " . self::sqlWhereCincoDigitos() . "
                        ORDER BY doc_solo_digitos, p.Id_Persona";
                break;
            case 'telefono':
                $sql = "SELECT 'documento_parece_telefono' AS tipo_anomalia,
                        CASE
                            WHEN {$doc} REGEXP '^3[0-9]{9}$' THEN 'movil_10_digitos_3xx'
                            WHEN {$tel} <> '' AND {$doc} = {$tel} THEN 'igual_a_campo_telefono'
                            WHEN {$doc} REGEXP '^573[0-9]{9}$' THEN 'movil_con_prefijo_57'
                            ELSE 'otro'
                        END AS motivo,
                        {$selectBase}
                        FROM persona p {$joins}
                        WHERE TRIM(COALESCE(p.Numero_Documento, '')) <> '' AND {$whereTel}
                        ORDER BY motivo, p.Id_Persona";
                break;
            default:
                $union = self::sqlSubqueryAnomaliasUnion();
                $liderMinMax = self::sqlCamposLiderMinisterio(true);
                $sql = "SELECT
                            p.Id_Persona,
                            MAX(TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, '')))) AS nombre_completo,
                            MAX(p.Tipo_Documento) AS Tipo_Documento,
                            MAX(p.Numero_Documento) AS Numero_Documento,
                            MAX({$doc}) AS doc_solo_digitos,
                            MAX(p.Telefono) AS Telefono,
                            MAX({$tel}) AS telefono_solo_digitos,
                            {$liderMinMax},
                            MAX(p.Estado_Cuenta) AS Estado_Cuenta,
                            MAX(p.Es_Antiguo) AS Es_Antiguo,
                            MAX(p.Id_Celula) AS Id_Celula,
                            MAX(p.Id_Ministerio) AS Id_Ministerio,
                            MAX(p.Proceso) AS Proceso,
                            MAX(p.Fecha_Registro) AS Fecha_Registro,
                            GROUP_CONCAT(DISTINCT t.tipo ORDER BY t.tipo SEPARATOR ', ') AS tipos_anomalia
                        FROM persona p {$joins}
                        INNER JOIN ({$union}) t ON t.Id_Persona = p.Id_Persona
                        GROUP BY p.Id_Persona
                        ORDER BY p.Id_Persona";
                break;
        }

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function tiposValidos(): array
    {
        return ['todos', 'vacio', '5_digitos', 'telefono'];
    }

    public static function normalizarTipo(string $tipo): string
    {
        return in_array($tipo, self::tiposValidos(), true) ? $tipo : 'todos';
    }
}
