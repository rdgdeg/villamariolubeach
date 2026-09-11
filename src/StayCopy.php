<?php

class StayCopy
{
    public static function paymentLetter(array $booking): string
    {
        return self::email($booking, 'confirmed')['body'];
    }

    public static function reminderBody(array $booking, string $kind = 'deposit'): string
    {
        $map = [
            'deposit' => 'deposit_reminder_1',
            'balance' => 'balance',
        ];
        return self::email($booking, $map[$kind] ?? $kind)['body'];
    }

    /** @return array{subject:string,body:string} */
    public static function email(array $booking, string $kind): array
    {
        $kind = self::alias($kind);
        $rendered = EmailTemplates::render($booking, $kind);
        if ($rendered) {
            return $rendered;
        }
        return self::builtin($booking, $kind);
    }

    /** @return array{subject:string,body:string} */
    public static function tokenized(string $kind, string $lang): array
    {
        $kind = self::alias($kind);
        $prev = current_lang();
        I18n::load($lang);
        try {
            return self::compose($kind, [
                'prenom' => '{{prenom}}',
                'nom' => '{{nom}}',
                'dates' => '{{dates}}',
                'percent' => '{{acompte_pct}}',
                'deposit' => '{{acompte}}',
                'balance' => '{{solde}}',
                'total' => '{{total}}',
                'email' => '{{email}}',
                'phone' => '{{telephone}}',
                'maps' => (string) setting('shardana_maps', ''),
                'reviews' => (string) setting('google_reviews', ''),
                'bank' => self::bankBlockTokens(),
            ]);
        } finally {
            I18n::load($prev ?: 'en');
        }
    }

    public static function mailto(array $booking, string $kind = 'deposit'): string
    {
        $msg = self::email($booking, self::alias($kind));
        return 'mailto:' . rawurlencode((string) $booking['guest_email'])
            . '?subject=' . rawurlencode($msg['subject'])
            . '&body=' . rawurlencode($msg['body']);
    }

    public static function alias(string $kind): string
    {
        return match ($kind) {
            'deposit' => 'confirmed',
            default => $kind,
        };
    }

    /** @return array{subject:string,body:string} */
    public static function builtin(array $booking, string $kind): array
    {
        return self::inGuestLang($booking, static function () use ($booking, $kind) {
            $vars = EmailTemplates::vars($booking);
            return self::compose($kind, [
                'prenom' => $vars['{{prenom}}'],
                'nom' => $vars['{{nom}}'],
                'dates' => $vars['{{dates}}'],
                'percent' => $vars['{{acompte_pct}}'],
                'deposit' => $vars['{{acompte}}'],
                'balance' => $vars['{{solde}}'],
                'total' => $vars['{{total}}'],
                'email' => $vars['{{email}}'],
                'phone' => $vars['{{telephone}}'],
                'maps' => (string) setting('shardana_maps', ''),
                'reviews' => (string) setting('google_reviews', ''),
                'bank' => self::bankBlock(),
            ]);
        });
    }

    /** @param array<string,string> $v */
    private static function compose(string $kind, array $v): array
    {
        $sign = t('paymail.sign');
        $hello = t('paymail.hello_name', ['name' => $v['prenom']]);
        if ($hello === 'paymail.hello_name') {
            $hello = t('paymail.hello');
        }
        $kind = match ($kind) {
            'deposit_reminder_1', 'deposit_reminder_2', 'deposit' => 'confirmed',
            'balance_reminder' => 'balance',
            default => $kind,
        };

        return match ($kind) {
            'host' => [
                'subject' => t('paymail.subject_host', ['name' => $v['nom'], 'dates' => $v['dates']]),
                'body' => implode("\n", [
                    t('paymail.host_intro', ['name' => $v['nom'], 'dates' => $v['dates']]),
                    '',
                    $v['email'] . ' · ' . $v['phone'],
                    t('paymail.host_total', ['amount' => $v['total']]),
                    t('paymail.host_admin'),
                ]),
            ],
            'request' => [
                'subject' => t('paymail.subject_request'),
                'body' => implode("\n", [
                    $hello, '',
                    t('paymail.request_thanks', ['dates' => $v['dates']]),
                    t('paymail.request_wait'),
                    '', $sign,
                ]),
            ],
            'confirmed' => [
                'subject' => t('paymail.subject_deposit'),
                'body' => implode("\n", [
                    $hello, '',
                    t('paymail.thanks'),
                    t('paymail.dates_line', ['dates' => $v['dates']]),
                    '',
                    t('paymail.deposit', ['p' => $v['percent'], 'amount' => $v['deposit']]),
                    t('paymail.two_months'),
                    '', $v['bank'], '',
                    t('legal.golden_title'),
                    ...self::goldenRules(),
                    '', $sign,
                ]),
            ],
            'balance' => [
                'subject' => t('paymail.subject_balance'),
                'body' => implode("\n", [
                    $hello, '',
                    t('paymail.thanks'),
                    t('paymail.dates_line', ['dates' => $v['dates']]),
                    '',
                    t('paymail.balance_intro', ['amount' => $v['balance']]),
                    '', $v['bank'], '', $sign,
                ]),
            ],
            'refused' => [
                'subject' => t('paymail.subject_refused'),
                'body' => implode("\n", [
                    $hello, '',
                    t('paymail.refused_body', ['dates' => $v['dates']]),
                    '', $sign,
                ]),
            ],
            'cancelled' => [
                'subject' => t('paymail.subject_cancelled'),
                'body' => implode("\n", [
                    $hello, '',
                    t('paymail.cancelled_body', ['dates' => $v['dates']]),
                    '', $sign,
                ]),
            ],
            'deposit_paid' => [
                'subject' => t('paymail.subject_deposit_paid'),
                'body' => implode("\n", [
                    $hello, '',
                    t('paymail.deposit_paid_body', ['amount' => $v['deposit'], 'dates' => $v['dates']]),
                    t('paymail.two_months'),
                    '', $sign,
                ]),
            ],
            'balance_paid' => [
                'subject' => t('paymail.subject_balance_paid'),
                'body' => implode("\n", [
                    $hello, '',
                    t('paymail.balance_paid_body', ['amount' => $v['total'], 'dates' => $v['dates']]),
                    '', $sign,
                ]),
            ],
            'prearrival' => [
                'subject' => t('paymail.subject_prearrival'),
                'body' => implode("\n", [
                    $hello, '',
                    t('paymail.prearrival_body', ['dates' => $v['dates']]),
                    t('legal.checkin'),
                    t('legal.meeting'),
                    $v['maps'],
                    '',
                    t('legal.golden_title'),
                    ...self::goldenRules(),
                    '', $sign,
                ]),
            ],
            'thanks' => [
                'subject' => t('paymail.subject_thanks'),
                'body' => implode("\n", [
                    $hello, '',
                    t('paymail.thanks_body'),
                    $v['reviews'],
                    '', $sign,
                ]),
            ],
            default => [
                'subject' => t('paymail.subject_request'),
                'body' => t('paymail.hello') . "\n\n" . $sign,
            ],
        };
    }

    private static function bankBlock(): string
    {
        return implode("\n", [
            t('paymail.bank_title'),
            t('paymail.holder') . ' ' . setting('bank_name', 'Villa Mariolu Beach – Owner'),
            'IBAN: ' . setting('iban', ''),
            'BIC: ' . setting('bic', ''),
            t('paymail.reference') . ' ' . setting('payment_ref', 'Holiday reservation in Sardinia'),
        ]);
    }

    private static function bankBlockTokens(): string
    {
        return implode("\n", [
            t('paymail.bank_title'),
            t('paymail.holder') . ' {{banque}}',
            'IBAN: {{iban}}',
            'BIC: {{bic}}',
            t('paymail.reference') . ' {{communication}}',
        ]);
    }

    private static function goldenRules(): array
    {
        $rules = [];
        foreach (['a', 'b', 'c', 'd', 'e', 'f'] as $key) {
            $rules[] = t('legal.golden.' . $key);
        }
        return $rules;
    }

    private static function inGuestLang(array $booking, callable $fn): mixed
    {
        $lang = $booking['guest_lang'] ?? 'en';
        if (!in_array($lang, I18n::LANGS, true)) {
            $lang = 'en';
        }
        $prev = current_lang();
        I18n::load($lang);
        try {
            return $fn();
        } finally {
            I18n::load($prev ?: 'en');
        }
    }
}
