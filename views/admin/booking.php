<?php
$statuses = [
    'pending' => 'En attente',
    'confirmed' => 'Confirmée',
    'refused' => 'Refusée',
    'cancelled' => 'Annulée',
];
$extraLabels = [
    'baby_cot' => 'Lit bébé',
    'high_chair' => 'Chaise haute',
    'beach_towels' => 'Serviettes de plage',
    'late_arrival' => 'Arrivée après 20 h',
];
$extras = json_decode((string) ($booking['extras'] ?? '[]'), true);
if (!is_array($extras)) {
    $extras = [];
}
$balance = (float) $booking['total'] - (float) $booking['deposit_amount'];
$letter = StayCopy::paymentLetter($booking);
$depositPercent = (float) ($booking['deposit_percent'] ?? 0);
if ($depositPercent <= 0) {
    $depositPercent = (float) setting('deposit_percent', 20);
}
$nights = 0;
try {
    $nights = (new DateTimeImmutable((string) $booking['check_in']))
        ->diff(new DateTimeImmutable((string) $booking['check_out']))
        ->days;
} catch (Exception $e) {
    $nights = 0;
}
$rentalNet = round((float) $booking['rental_subtotal'] - (float) $booking['discount_amount'], 2);
if ($rentalNet > 0 && (float) $booking['deposit_amount'] > 0) {
    $depositPercent = round(((float) $booking['deposit_amount'] / $rentalNet) * 100, 2);
}
$firstName = $booking['guest_first_name'] ?: explode(' ', (string) $booking['guest_name'], 2)[0];
$lastName = $booking['guest_last_name'] ?: (explode(' ', (string) $booking['guest_name'], 2)[1] ?? '');
$bookingEvents = $bookingEvents ?? [];
$bookingStages = $bookingStages ?? [];
$nightsDetail = Pricing::nightsBreakdown((string) $booking['check_in'], (string) $booking['check_out']);
$nightsSum = 0.0;
foreach ($nightsDetail as $night) {
    $nightsSum += (float) $night['rate'];
}
$nightsMismatch = $nightsDetail && abs($nightsSum - (float) $booking['rental_subtotal']) > 0.05;
$guestEmail = (string) $booking['guest_email'];
$mailConfirm = static function (string $label) use ($guestEmail): string {
    return 'return confirm(' . json_encode('Envoyer « ' . $label . ' » à ' . $guestEmail . ' ?', JSON_UNESCAPED_UNICODE) . ');';
};
?>
<p class="booking-back"><a class="link" href="<?= e(base_url('admin')) ?>">← Retour à la liste</a></p>

<article class="booking-sheet">
    <header class="booking-head">
        <div>
            <h2><?= e($booking['guest_name']) ?></h2>
            <p class="booking-meta">
                <?= e($booking['guest_email']) ?>
                <?php if ($booking['guest_phone']): ?> · <?= e($booking['guest_phone']) ?><?php endif; ?>
                <?php if (!empty($booking['guest_country'])): ?> · <?= e($booking['guest_country']) ?><?php endif; ?>
                · <?= e(strtoupper((string) $booking['guest_lang'])) ?>
                <?php if ($nights): ?> · <?= (int) $nights ?> nuit<?= $nights > 1 ? 's' : '' ?><?php endif; ?>
            </p>
        </div>
        <span class="status <?= e($booking['status']) ?>"><?= e($statuses[$booking['status']] ?? $booking['status']) ?></span>
    </header>

    <?php if ($bookingStages): ?>
        <ol class="stage-track">
            <?php foreach ($bookingStages as $step): ?>
                <li class="is-<?= e($step['state']) ?>"><?= e($step['label']) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <form method="post" class="booking-layout">
        <?= Csrf::field() ?>
        <input type="hidden" name="form" value="update">

        <div class="booking-main">
            <section class="block">
                <h3>Client</h3>
                <div class="fields fields-2">
                    <label>Prénom
                        <input name="guest_first_name" value="<?= e($firstName) ?>">
                    </label>
                    <label>Nom
                        <input name="guest_last_name" value="<?= e($lastName) ?>">
                    </label>
                    <label>E-mail
                        <input type="email" name="guest_email" value="<?= e($booking['guest_email']) ?>">
                    </label>
                    <label>Téléphone
                        <input name="guest_phone" value="<?= e($booking['guest_phone']) ?>">
                    </label>
                </div>
                <label class="field-narrow">Pays
                    <input name="guest_country" value="<?= e($booking['guest_country']) ?>">
                </label>
            </section>

            <section class="block">
                <h3>Séjour</h3>
                <div class="fields fields-2">
                    <label>Arrivée
                        <input type="date" name="check_in" value="<?= e($booking['check_in']) ?>" required>
                    </label>
                    <label>Départ
                        <input type="date" name="check_out" value="<?= e($booking['check_out']) ?>" required>
                    </label>
                </div>
                <div class="fields fields-guests">
                    <label>Adultes
                        <input type="number" name="adults" min="1" max="6" value="<?= (int) $booking['adults'] ?>">
                    </label>
                    <label>Enfants
                        <input type="number" name="children" min="0" max="4" value="<?= (int) $booking['children'] ?>">
                    </label>
                </div>
                <div class="fields fields-2">
                    <label>Occupants
                        <textarea name="occupants" rows="2"><?= e($booking['occupants']) ?></textarea>
                    </label>
                    <label>Message client
                        <textarea name="guest_message" rows="2"><?= e($booking['guest_message']) ?></textarea>
                    </label>
                </div>
                <?php if ($extras): ?>
                    <p class="booking-extras">Options : <?= e(implode(', ', array_map(static fn ($k) => $extraLabels[$k] ?? $k, $extras))) ?></p>
                <?php endif; ?>
            </section>

            <section class="block">
                <h3>Notes internes</h3>
                <label class="sr-only" for="admin_notes">Notes internes</label>
                <textarea id="admin_notes" name="admin_notes" rows="3"><?= e($booking['admin_notes']) ?></textarea>
            </section>
        </div>

        <aside class="booking-side">
            <section class="block">
                <h3>Montants</h3>
                <dl class="amounts">
                    <div><dt>Location</dt><dd><?= e(money((float) $booking['rental_subtotal'])) ?></dd></div>
                    <?php if ($nightsDetail): ?>
                        <details class="nights-acc">
                            <summary>Détail par nuit (<?= count($nightsDetail) ?>)</summary>
                            <ul>
                                <?php foreach ($nightsDetail as $night): ?>
                                    <li>
                                        <span><?= e(format_date($night['date'])) ?> · <?= e($night['label']) ?></span>
                                        <strong><?= e(money((float) $night['rate'])) ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="nights-acc-total">
                                Somme <?= e(money($nightsSum)) ?>
                                <?php if ($nightsMismatch): ?>
                                    <span class="nights-acc-warn">≠ location enregistrée <?= e(money((float) $booking['rental_subtotal'])) ?></span>
                                <?php endif; ?>
                            </p>
                        </details>
                    <?php endif; ?>
                    <div><dt>Réduction <?= e((string) $booking['discount_percent']) ?> %</dt><dd>− <?= e(money((float) $booking['discount_amount'])) ?></dd></div>
                    <div><dt>Nettoyage</dt><dd><?= e(money((float) $booking['cleaning_fee'])) ?></dd></div>
                    <div class="is-total"><dt>Total</dt><dd><?= e(money((float) $booking['total'])) ?></dd></div>
                    <div><dt>Acompte <span data-deposit-percent-label><?= e((string) $depositPercent) ?></span> %</dt><dd data-deposit-line><?= e(money((float) $booking['deposit_amount'])) ?></dd></div>
                    <div><dt>Solde</dt><dd data-balance-line><?= e(money($balance)) ?></dd></div>
                    <div><dt>Caution</dt><dd><?= e(money((float) $booking['caution'])) ?></dd></div>
                </dl>
                <div class="deposit-edit" data-deposit-sync data-rental="<?= e((string) $rentalNet) ?>" data-total="<?= e((string) $booking['total']) ?>">
                    <div class="fields fields-2">
                        <label>Acompte %
                            <input name="deposit_percent" type="number" min="0" max="100" step="0.01" value="<?= e((string) $depositPercent) ?>">
                        </label>
                        <label>Acompte €
                            <input name="deposit_amount" type="number" min="0" step="0.01" value="<?= e((string) $booking['deposit_amount']) ?>">
                        </label>
                    </div>
                    <input type="hidden" name="deposit_source" value="percent" data-deposit-source>
                </div>
                <div class="pay-flags" data-autosave-flags>
                    <label class="switch">
                        <input type="checkbox" name="deposit_paid" value="1" <?= !empty($booking['deposit_paid']) ? 'checked' : '' ?>>
                        <span class="switch-ui" aria-hidden="true"></span>
                        Acompte perçu
                    </label>
                    <label class="switch">
                        <input type="checkbox" name="balance_paid" value="1" <?= !empty($booking['balance_paid']) ? 'checked' : '' ?>>
                        <span class="switch-ui" aria-hidden="true"></span>
                        Solde perçu
                    </label>
                </div>
                <p class="hint">Le % s’applique à la location (<?= e(money($rentalNet)) ?>, hors nettoyage). Un montant saisi prime et recalcule le %.</p>
            </section>

            <section class="block">
                <h3>Statut</h3>
                <label>Statut
                    <select name="status">
                        <?php foreach ($statuses as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= $booking['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="booking-actions">
                    <?php if ($booking['status'] === 'pending'): ?>
                        <button type="submit" name="quick" value="confirmed" data-confirm-stay data-email="<?= e($guestEmail) ?>" data-amount="<?= e((string) $booking['deposit_amount']) ?>">Valider la réservation</button>
                        <button class="danger" type="submit" name="quick" value="refused" onclick="<?= e($mailConfirm('Refus de la demande')) ?>">Refuser</button>
                    <?php endif; ?>
                    <button type="submit">Enregistrer</button>
                </div>
                <p class="hint">« Confirmée » bloque les dates (bordeaux). « Refusée » les libère. En attente = doré.</p>
            </section>
        </aside>
    </form>

    <section class="block">
        <h3>E-mails</h3>
        <div class="mail-groups">
            <div class="mail-group">
                <h4>Paiement</h4>
                <div class="mail-btns">
                    <form method="post" onsubmit="<?= e($mailConfirm('Relance acompte 1')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="form" value="remind">
                        <input type="hidden" name="kind" value="deposit_reminder_1">
                        <button type="submit" data-deposit-mail-btn>Relance acompte 1 (<?= e(money((float) $booking['deposit_amount'])) ?>)</button>
                    </form>
                    <form method="post" onsubmit="<?= e($mailConfirm('Relance acompte 2')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="form" value="remind">
                        <input type="hidden" name="kind" value="deposit_reminder_2">
                        <button type="submit">Relance acompte 2</button>
                    </form>
                    <form method="post" onsubmit="<?= e($mailConfirm('Demande de solde')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="form" value="remind">
                        <input type="hidden" name="kind" value="balance">
                        <button type="submit" data-balance-mail-btn>Demande de solde (<?= e(money($balance)) ?>)</button>
                    </form>
                    <form method="post" onsubmit="<?= e($mailConfirm('Relance solde')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="form" value="remind">
                        <input type="hidden" name="kind" value="balance_reminder">
                        <button type="submit">Relance solde</button>
                    </form>
                </div>
            </div>
            <div class="mail-group">
                <h4>Séjour</h4>
                <div class="mail-btns">
                    <form method="post" onsubmit="<?= e($mailConfirm('Mail d’arrivée')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="form" value="journey">
                        <input type="hidden" name="kind" value="prearrival">
                        <button type="submit">Mail d’arrivée (J−7)</button>
                    </form>
                    <form method="post" onsubmit="<?= e($mailConfirm('Mail merci / avis')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="form" value="journey">
                        <input type="hidden" name="kind" value="thanks">
                        <button type="submit">Mail merci / avis</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="block">
        <h3>Historique</h3>
        <?php if ($bookingEvents): ?>
            <ol class="event-log">
                <?php foreach ($bookingEvents as $ev): ?>
                    <li>
                        <time datetime="<?= e($ev['at']) ?>"><?= e(format_date($ev['at'], true)) ?></time>
                        <span><?= e($ev['label']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="hint">Aucun événement enregistré pour le moment.</p>
        <?php endif; ?>
    </section>

    <details class="letter-preview">
        <summary>Lettre type (langue du client)</summary>
        <pre><?= e($letter) ?></pre>
    </details>

    <form method="post" class="danger-box" onsubmit="return confirm('Supprimer définitivement cette réservation ? Cette action est irréversible.');">
        <?= Csrf::field() ?>
        <input type="hidden" name="form" value="delete">
        <p>Supprimer cette réservation libère les dates dans le calendrier.</p>
        <button class="danger" type="submit">Supprimer la réservation</button>
    </form>
</article>
