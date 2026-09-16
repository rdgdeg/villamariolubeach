<?php

class EmailTemplates
{
    /** @return array<string,string> */
    public static function kinds(): array
    {
        return [
            'request' => 'Demande reçue (en attente)',
            'confirmed' => 'Confirmation + acompte',
            'deposit_reminder_1' => 'Relance acompte 1',
            'deposit_reminder_2' => 'Relance acompte 2',
            'balance' => 'Demande de solde',
            'balance_reminder' => 'Relance solde',
            'cancelled' => 'Annulation',
            'refused' => 'Refus',
            'deposit_paid' => 'Acompte reçu',
            'balance_paid' => 'Solde reçu',
            'prearrival' => 'Informations d’arrivée',
            'thanks' => 'Merci / avis',
            'host' => 'Notification propriétaire',
        ];
    }

    /** @return array<string,string> */
    public static function tokens(): array
    {
        return [
            '{{prenom}}' => 'Prénom',
            '{{nom}}' => 'Nom complet',
            '{{dates}}' => 'Dates du séjour',
            '{{arrivee}}' => 'Arrivée',
            '{{depart}}' => 'Départ',
            '{{nuits}}' => 'Nuits',
            '{{total}}' => 'Total',
            '{{acompte_pct}}' => '% acompte',
            '{{acompte}}' => 'Montant acompte',
            '{{solde}}' => 'Solde',
            '{{caution}}' => 'Caution',
            '{{iban}}' => 'IBAN',
            '{{bic}}' => 'BIC',
            '{{banque}}' => 'Titulaire',
            '{{communication}}' => 'Communication virement',
            '{{email}}' => 'E-mail client',
            '{{telephone}}' => 'Téléphone',
        ];
    }

    public static function ensureSeeded(): void
    {
        foreach (array_keys(self::kinds()) as $kind) {
            foreach (I18n::LANGS as $lang) {
                $stmt = db()->prepare('SELECT id FROM email_templates WHERE kind = ? AND lang = ?');
                $stmt->execute([$kind, $lang]);
                if ($stmt->fetch()) {
                    continue;
                }
                $msg = StayCopy::tokenized($kind, $lang);
                db()->prepare(
                    'INSERT INTO email_templates (kind, lang, subject, body, updated_at) VALUES (?, ?, ?, ?, ?)'
                )->execute([$kind, $lang, $msg['subject'], $msg['body'], date('Y-m-d H:i:s')]);
            }
        }
    }

    /** @return array<string,array{subject:string,body:string}> */
    public static function forKind(string $kind): array
    {
        self::ensureSeeded();
        $rows = db()->prepare('SELECT lang, subject, body FROM email_templates WHERE kind = ?');
        $rows->execute([$kind]);
        $out = [];
        foreach (I18n::LANGS as $lang) {
            $out[$lang] = ['subject' => '', 'body' => ''];
        }
        foreach ($rows as $row) {
            $out[$row['lang']] = [
                'subject' => (string) $row['subject'],
                'body' => (string) $row['body'],
            ];
        }
        return $out;
    }

    public static function saveKind(string $kind, array $subjects, array $bodies): void
    {
        if (!isset(self::kinds()[$kind])) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        foreach (I18n::LANGS as $lang) {
            $subject = trim((string) ($subjects[$lang] ?? ''));
            $body = (string) ($bodies[$lang] ?? '');
            $exists = db()->prepare('SELECT id FROM email_templates WHERE kind = ? AND lang = ?');
            $exists->execute([$kind, $lang]);
            if ($exists->fetch()) {
                db()->prepare(
                    'UPDATE email_templates SET subject = ?, body = ?, updated_at = ? WHERE kind = ? AND lang = ?'
                )->execute([$subject, $body, $now, $kind, $lang]);
            } else {
                db()->prepare(
                    'INSERT INTO email_templates (kind, lang, subject, body, updated_at) VALUES (?, ?, ?, ?, ?)'
                )->execute([$kind, $lang, $subject, $body, $now]);
            }
        }
    }

    /** @return array{subject:string,body:string}|null */
    public static function render(array $booking, string $kind): ?array
    {
        self::ensureSeeded();
        $lang = (string) ($booking['guest_lang'] ?? 'en');
        if (!in_array($lang, I18n::LANGS, true)) {
            $lang = 'en';
        }
        $stmt = db()->prepare('SELECT subject, body FROM email_templates WHERE kind = ? AND lang = ?');
        $stmt->execute([$kind, $lang]);
        $row = $stmt->fetch();
        if (!$row || trim((string) $row['subject']) === '' || trim((string) $row['body']) === '') {
            return null;
        }
        $vars = self::vars($booking);
        return [
            'subject' => self::replace((string) $row['subject'], $vars),
            'body' => self::replace((string) $row['body'], $vars),
        ];
    }

    public static function sampleBooking(string $lang): array
    {
        if (!in_array($lang, I18n::LANGS, true)) {
            $lang = 'fr';
        }
        return [
            'id' => 0,
            'guest_first_name' => 'Marie',
            'guest_last_name' => 'Dupont',
            'guest_name' => 'Marie Dupont',
            'guest_email' => (string) setting('email', 'test@example.com'),
            'guest_phone' => '+32 470 00 00 00',
            'guest_lang' => $lang,
            'check_in' => '2027-07-10',
            'check_out' => '2027-07-17',
            'nights' => 7,
            'total' => 2100,
            'deposit_amount' => 315,
            'deposit_percent' => 15,
            'caution' => (float) setting('caution', 300),
        ];
    }

    /** @return array<string,string> */
    public static function vars(array $booking): array
    {
        $percent = (float) ($booking['deposit_percent'] ?? 0);
        if ($percent <= 0) {
            $percent = (float) setting('deposit_percent', 15);
        }
        $deposit = (float) ($booking['deposit_amount'] ?? 0);
        $total = (float) ($booking['total'] ?? 0);
        $name = trim((string) ($booking['guest_name'] ?? ''));
        $first = trim((string) ($booking['guest_first_name'] ?? ''));
        if ($first === '') {
            $first = $name !== '' ? explode(' ', $name, 2)[0] : '';
        }
        $pct = abs($percent - (int) $percent) < 0.009 ? (string) (int) $percent : (string) $percent;
        return [
            '{{prenom}}' => $first,
            '{{nom}}' => $name !== '' ? $name : trim($first . ' ' . (string) ($booking['guest_last_name'] ?? '')),
            '{{dates}}' => format_date((string) ($booking['check_in'] ?? '')) . ' → ' . format_date((string) ($booking['check_out'] ?? '')),
            '{{arrivee}}' => format_date((string) ($booking['check_in'] ?? '')),
            '{{depart}}' => format_date((string) ($booking['check_out'] ?? '')),
            '{{nuits}}' => (string) (int) ($booking['nights'] ?? 0),
            '{{total}}' => money($total),
            '{{acompte_pct}}' => $pct,
            '{{acompte}}' => money($deposit),
            '{{solde}}' => money($total - $deposit),
            '{{caution}}' => money((float) ($booking['caution'] ?? setting('caution', 300))),
            '{{iban}}' => (string) setting('iban', ''),
            '{{bic}}' => (string) setting('bic', ''),
            '{{banque}}' => (string) setting('bank_name', ''),
            '{{communication}}' => (string) setting('payment_ref', ''),
            '{{email}}' => (string) ($booking['guest_email'] ?? ''),
            '{{telephone}}' => (string) ($booking['guest_phone'] ?? ''),
        ];
    }

    /** @param array<string,string> $vars */
    private static function replace(string $text, array $vars): string
    {
        return strtr($text, $vars);
    }
}
