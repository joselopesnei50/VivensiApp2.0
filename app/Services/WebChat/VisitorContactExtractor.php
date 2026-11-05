<?php

namespace App\Services\WebChat;

/**
 * Tira nome, e-mail e telefone do que o visitante escreve no chat, para a
 * equipe saber com quem o Bruno falou mesmo que a pessoa não agende.
 * Conservador: na dúvida, não captura.
 */
class VisitorContactExtractor
{
    /** Pergunta de nome feita pelo Bruno na mensagem anterior. */
    private const NAME_QUESTION = '~(seu nome|como (eu )?(posso|devo) te chamar|com quem (eu )?(falo|estou falando)|qual (é |e )?o seu nome|como voc[eê] se chama)~iu';

    private const NOT_A_NAME = [
        'sim', 'não', 'nao', 'ok', 'oi', 'olá', 'ola', 'opa', 'bom dia', 'boa tarde', 'boa noite',
        'obrigado', 'obrigada', 'valeu', 'tudo bem', 'beleza', 'claro', 'pode ser', 'prefiro não',
        'prefiro nao', 'quero', 'ong', 'instituto', 'associação', 'associacao', 'fundação', 'fundacao',
    ];

    public static function email(string $text): ?string
    {
        if (preg_match('~[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}~i', $text, $m)) {
            $email = mb_strtolower(rtrim($m[0], '.'));
            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }
        return null;
    }

    /** Telefone brasileiro com DDD, formatado como (16) 99999-8888. */
    public static function phone(string $text): ?string
    {
        if (! preg_match('~(?:\+?55[\s.-]?)?\(?\b(\d{2})\)?[\s.-]?(9?\d{4})[\s.-]?(\d{4})\b~', $text, $m)) {
            return null;
        }
        $ddd = (int) $m[1];
        if ($ddd < 11 || $ddd > 99) {
            return null;
        }
        return sprintf('(%s) %s-%s', $m[1], $m[2], $m[3]);
    }

    /**
     * Nome dito explicitamente ("me chamo Ana", "meu nome é Ana Paula") ou
     * resposta curta logo depois de o Bruno perguntar o nome.
     */
    public static function name(string $text, ?string $previousBotMessage = null): ?string
    {
        $text = trim($text);

        $explicit = '~(?:^|[\s,.!])(?:meu nome (?:é|e)|me chamo|pode me chamar de|aqui (?:é|e) (?:o|a) |quem fala (?:é|e) (?:o |a )?)\s*'
            . '([\p{L}\'-]+(?:\s+[\p{L}\'-]+){0,5})~iu';
        if (preg_match($explicit, $text, $m) && ($name = self::trimName($m[1]))) {
            return self::clean($name);
        }

        if ($previousBotMessage && preg_match(self::NAME_QUESTION, $previousBotMessage)) {
            $candidate = preg_replace('~^(?:(?:oi|olá|ola|opa)[,!.\s]+)?(?:(?:é|e|sou)\s+(?:o |a )?)?~iu', '', $text);
            $candidate = trim(preg_replace('~[!.,;]+$~', '', $candidate));
            if ($candidate !== ''
                && mb_strlen($candidate) <= 40
                && preg_match('~^[\p{L}\'-]+(?:\s+[\p{L}\'-]+){0,3}$~u', $candidate)
                && ! in_array(mb_strtolower($candidate), self::NOT_A_NAME, true)) {
                return self::clean($candidate);
            }
        }

        return null;
    }

    public static function botAskedName(string $botMessage): bool
    {
        return (bool) preg_match(self::NAME_QUESTION, $botMessage);
    }

    public static function botAskedContact(string $botMessage): bool
    {
        return (bool) preg_match('~(seu|um|o) (whats ?app|e-?mail|telefone|contato)|n[uú]mero (de|do) (whats|telefone|celular)~iu', $botMessage);
    }

    /** Corta o nome onde a frase continua ("ana e sou da ONG Luz" → "ana"). */
    private static function trimName(string $words): ?string
    {
        $stop = ['e', 'sou', 'trabalho', 'aqui', 'represento', 'tenho', 'estou', 'queria', 'quero', 'gostaria', 'da', 'de', 'do', 'dos', 'das'];
        $orgs = ['ong', 'instituto', 'associação', 'associacao', 'fundação', 'fundacao', 'projeto', 'prefeitura', 'empresa', 'igreja', 'uma', 'um'];
        $tokens = preg_split('~\s+~', trim($words));
        $out = [];
        foreach ($tokens as $i => $t) {
            $lower = mb_strtolower($t);
            $isParticle = in_array($lower, ['da', 'de', 'do', 'dos', 'das'], true);
            if ($isParticle && $out && isset($tokens[$i + 1]) && ! in_array(mb_strtolower($tokens[$i + 1]), array_merge($stop, $orgs), true)) {
                $out[] = $t; // "Maria da Silva"
                continue;
            }
            if (in_array($lower, $stop, true) || in_array($lower, $orgs, true)) {
                break;
            }
            $out[] = $t;
            if (count($out) >= 4) {
                break;
            }
        }
        // não termina em partícula ("Maria da")
        while ($out && in_array(mb_strtolower(end($out)), ['da', 'de', 'do', 'dos', 'das'], true)) {
            array_pop($out);
        }
        $name = implode(' ', $out);
        return $name !== '' && ! in_array(mb_strtolower($name), self::NOT_A_NAME, true) ? $name : null;
    }

    private static function clean(string $name): string
    {
        $parts = preg_split('~\s+~', trim($name));
        $parts = array_map(function ($p) {
            $lower = mb_strtolower($p);
            return in_array($lower, ['de', 'da', 'do', 'dos', 'das', 'e'], true) ? $lower : mb_convert_case($lower, MB_CASE_TITLE);
        }, $parts);
        return mb_substr(implode(' ', $parts), 0, 120);
    }
}
