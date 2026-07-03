<?php

namespace App\Enums;

/**
 * Modo de operação da Sala de Estratégia por tipo de tenant.
 *
 * Institucional: ONGs e gestores de projeto (type ngo/business) — editais,
 * beneficiários, doadores. Negocio: MEI/autônomo/PJ (type common/personal) —
 * clientes, recibos, prospecção, teto MEI.
 *
 * Classe de constantes (não enum nativo): o ambiente local roda PHP 8.0,
 * que não suporta enum — os call sites usam `string $mode` com estes valores.
 */
final class StrategyRoomMode
{
    public const Institucional = 'institucional';
    public const Negocio       = 'negocio';
}
