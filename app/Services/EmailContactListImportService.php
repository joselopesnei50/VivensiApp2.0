<?php

namespace App\Services;

use App\Models\EmailContact;
use App\Models\EmailContactList;
use Illuminate\Http\UploadedFile;

/**
 * Import CSV pra lista de contatos de campanha.
 *
 * Detecta:
 *  - Header row (email, name) — se primeira linha nao parece email, trata como header
 *  - Colunas em qualquer ordem (email/e-mail/email_address + name/nome)
 *  - Encoding: UTF-8 (auto), Latin1/Windows-1252 (heuristica)
 *  - Delimiter: virgula ou ponto-virgula (Excel BR)
 *
 * Retorna estatisticas: {imported, duplicates_in_list, invalid_emails, total_lines}
 */
class EmailContactListImportService
{
    public const MAX_CONTACTS_PER_UPLOAD = 20000;

    /**
     * @return array{imported:int, duplicates_in_list:int, invalid_emails:int, total_lines:int, errors:array}
     */
    public function importFromCsv(EmailContactList $list, UploadedFile $csv): array
    {
        $handle = fopen($csv->getRealPath(), 'r');
        if (!$handle) {
            return ['imported' => 0, 'duplicates_in_list' => 0, 'invalid_emails' => 0, 'total_lines' => 0, 'errors' => ['Falha ao abrir arquivo.']];
        }

        // Detecta delimiter na primeira linha bruta
        $firstLineRaw = fgets($handle);
        rewind($handle);
        $delim = $this->detectDelimiter($firstLineRaw ?: '');

        $imported   = 0;
        $duplicates = 0;
        $invalids   = 0;
        $lineNumber = 0;
        $errors     = [];

        // Guarda emails ja existentes na lista pra evitar UNIQUE violation
        $existingEmails = EmailContact::where('email_contact_list_id', $list->id)
            ->pluck('email')
            ->map(fn ($e) => mb_strtolower(trim((string) $e)))
            ->flip()
            ->all();

        $headerParsed = false;
        $emailCol = 0;
        $nameCol  = null;

        while (($row = fgetcsv($handle, 8192, $delim)) !== false) {
            $lineNumber++;

            if ($lineNumber > self::MAX_CONTACTS_PER_UPLOAD) {
                $errors[] = "Limite de " . self::MAX_CONTACTS_PER_UPLOAD . " contatos por upload atingido — linhas apos essa foram ignoradas.";
                break;
            }

            // Normaliza encoding (Excel BR salva em Windows-1252)
            $row = array_map(fn ($c) => $this->normalizeEncoding((string) $c), $row);

            // Detecta header na primeira linha
            if (!$headerParsed) {
                $headerParsed = true;
                if ($this->looksLikeHeader($row)) {
                    [$emailCol, $nameCol] = $this->detectColumns($row);
                    continue; // pula header
                }
            }

            if (!isset($row[$emailCol])) {
                continue;
            }
            $email = mb_strtolower(trim((string) $row[$emailCol]));
            $name  = $nameCol !== null ? trim((string) ($row[$nameCol] ?? '')) : '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalids++;
                continue;
            }

            if (isset($existingEmails[$email])) {
                $duplicates++;
                continue;
            }

            EmailContact::create([
                'email_contact_list_id' => $list->id,
                'tenant_id'             => $list->tenant_id,
                'email'                 => $email,
                'name'                  => $name ?: null,
                'status'                => EmailContact::STATUS_ACTIVE,
                'source'                => 'csv_upload',
                'added_at'              => now(),
            ]);
            $existingEmails[$email] = true;
            $imported++;
        }
        fclose($handle);

        return [
            'imported'           => $imported,
            'duplicates_in_list' => $duplicates,
            'invalid_emails'     => $invalids,
            'total_lines'        => $lineNumber,
            'errors'             => $errors,
        ];
    }

    private function detectDelimiter(string $line): string
    {
        $semis = substr_count($line, ';');
        $comms = substr_count($line, ',');
        return $semis > $comms ? ';' : ',';
    }

    private function normalizeEncoding(string $s): string
    {
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'Windows-1252, ISO-8859-1');
        }
        return $s;
    }

    /**
     * Se qualquer celula da primeira linha NAO parecer email, tratamos como header.
     */
    private function looksLikeHeader(array $row): bool
    {
        foreach ($row as $c) {
            if (filter_var(trim((string) $c), FILTER_VALIDATE_EMAIL)) {
                return false; // achou email = provavelmente NAO eh header
            }
        }
        return true;
    }

    /**
     * @return array{0:int, 1:?int}  [emailCol, nameCol]
     */
    private function detectColumns(array $headerRow): array
    {
        $emailCol = 0;
        $nameCol  = null;

        foreach ($headerRow as $i => $col) {
            $c = mb_strtolower(trim((string) $col));
            if (in_array($c, ['email', 'e-mail', 'email_address', 'endereco'], true)) {
                $emailCol = $i;
            }
            if (in_array($c, ['name', 'nome', 'firstname', 'first_name', 'primeiro_nome'], true)) {
                $nameCol = $i;
            }
        }

        return [$emailCol, $nameCol];
    }
}
