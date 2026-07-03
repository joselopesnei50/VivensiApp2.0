<?php

namespace App\Enums;

/**
 * Modo de operação da Sala de Estratégia por tipo de tenant.
 *
 * Institucional: ONGs e gestores de projeto (type ngo/business) — editais,
 * beneficiários, doadores. Negocio: MEI/autônomo/PJ (type common/personal) —
 * clientes, recibos, prospecção, teto MEI.
 */
enum StrategyRoomMode: string
{
    case Institucional = 'institucional';
    case Negocio       = 'negocio';
}
