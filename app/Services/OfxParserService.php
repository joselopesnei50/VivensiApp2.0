<?php

namespace App\Services;

use SimpleXMLElement;
use Carbon\Carbon;

class OfxParserService
{
    public function parse($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Arquivo OFX não encontrado.");
        }

        $content = file_get_contents($filePath);
        
        // Limpar cabeçalhos OFX antigos que não são XML válido
        $xmlContent = $this->cleanOfxHeaders($content);

        try {
            $xml = new SimpleXMLElement($xmlContent);
        } catch (\Exception $e) {
            throw new \Exception("Erro ao ler o XML do OFX: " . $e->getMessage());
        }

        $transactions = [];
        $bankId = (string) $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKACCTFROM->BANKID;
        $accountId = (string) $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKACCTFROM->ACCTID;

        foreach ($xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN as $trn) {
            $dateString = (string) $trn->DTPOSTED;
            // OFX date format: YYYYMMDDHHMMSS[...]
            $date = Carbon::createFromFormat('YmdHis', substr($dateString, 0, 14));
            
            $rawAmount = (float) $trn->TRNAMT;
            
            $transactions[] = [
                'type' => $rawAmount >= 0 ? 'income' : 'expense',
                'date' => $date->format('Y-m-d'),
                'amount' => abs($rawAmount), // Store as positive, type defines sign
                'fitid' => (string) $trn->FITID, // Unique ID from bank
                'description' => (string) $trn->MEMO,
                'bank_id' => $bankId,
                'account_id' => $accountId
            ];
        }

        return $transactions;
    }

    private function cleanOfxHeaders($content)
    {
        // OFX files often have a header block before the XML tag
        // We need to strip everything before <OFX>
        $start = strpos($content, '<OFX>');
        if ($start !== false) {
            return substr($content, $start);
        }
        return $content; // Tenta retornar como está se não achar tag
    }
}
