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
?>
<p><a class="link" href="<?= e(base_url('admin')) ?>">← Retour à la liste</a></p>

<article class="detail">
    <header class="detail-head">
        <h2><?= e($booking['guest_name']) ?></h2>
        <span class="status <?= e($booking['status']) ?>"><?= e($statuses[$booking['status']] ?? $booking['status']) ?></span>
    </header>
    <p><?= e($booking['guest_email']) ?> · <?= e($booking['guest_phone']) ?>
        <?php if (!empty($booking['guest_country'])): ?> · <?= e($booking['guest_country']) ?><?php endif; ?>
        · langue <?= e($booking['guest_lang']) ?>
    </p>

    <div class="remind-row">
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="remind">
            <input type="hidden" name="kind" value="deposit_reminder_1">
            <button type="submit">Relance acompte 1 (<?= e(money((float) $booking['deposit_amount'])) ?>)</button>
        </form>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="remind">
            <input type="hidden" name="kind" value="deposit_reminder_2">
            <button type="submit">Relance acompte 2</button>
        </form>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="remind">
            <input type="hidden" name="kind" value="balance">
            <button type="submit">Demande de solde (<?= e(money($balance)) ?>)</button>
        </form>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="remind">
            <input type="hidden" name="kind" value="balance_reminder">
            <button type="submit">Relance solde</button>
        </form>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="journey">
            <input type="hidden" name="kind" value="prearrival">
            <button type="submit">Envoyer le mail d’arrivée (J−7)</button>
        </form>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="journey">
            <input type="hidden" name="kind" value="thanks">
            <button type="submit">Envoyer le mail « merci / avis »</button>
        </form>
        <?php if (!empty($booking['deposit_reminded_at'])): ?>
            <small>Acompte relancé le <?= e(format_date($booking['deposit_reminded_at'], true)) ?></small>
        <?php endif; ?>
        <?php if (!empty($booking['thanks_sent_at'])): ?>
            <small>Mail merci envoyé le <?= e(format_date($booking['thanks_sent_at'], true)) ?></small>
        <?php endif; ?>
        <?php if (!empty($booking['prearrival_sent_at'])): ?>
            <small>Mail d’arrivée envoyé le <?= e(format_date($booking['prearrival_sent_at'], true)) ?></small>
        <?php endif; ?>
    </div>

    <form method="post" class="stack">
        <?= Csrf::field() ?>
        <input type="hidden" name="form" value="update">
        <div class="row">
            <label>Prénom
                <input name="guest_first_name" value="<?= e($booking['guest_first_name'] ?: explode(' ', (string) $booking['guest_name'], 2)[0]) ?>">
            </label>
            <label>Nom
                <input name="guest_last_name" value="<?= e($booking['guest_last_name'] ?: (explode(' ', (string) $booking['guest_name'], 2)[1] ?? '')) ?>">
            </label>
        </div>
        <div class="row">
            <label>E-mail <input type="email" name="guest_email" value="<?= e($booking['guest_email']) ?>"></label>
            <label>Téléphone <input name="guest_phone" value="<?= e($booking['guest_phone']) ?>"></label>
            <label>Pays <input name="guest_country" value="<?= e($booking['guest_country']) ?>"></label>
        </div>
        <div class="row">
            <label>Arrivée <input type="date" name="check_in" value="<?= e($booking['check_in']) ?>" required></label>
            <label>Départ <input type="date" name="check_out" value="<?= e($booking['check_out']) ?>" required></label>
            <label>Adultes
                <input type="number" name="adults" min="1" max="6" value="<?= (int) $booking['adults'] ?>">
            </label>
            <label>Enfants
                <input type="number" name="children" min="0" max="4" value="<?= (int) $booking['children'] ?>">
            </label>
        </div>
        <label>Occupants
            <textarea name="occupants" rows="3"><?= e($booking['occupants']) ?></textarea>
        </label>
        <label>Message client
            <textarea name="guest_message" rows="3"><?= e($booking['guest_message']) ?></textarea>
        </label>
        <?php if ($extras): ?>
            <p>Options : <?= e(implode(', ', array_map(static fn ($k) => $extraLabels[$k] ?? $k, $extras))) ?></p>
        <?php endif; ?>

        <ul class="amount-list">
            <li>Location : <?= e(money((float) $booking['rental_subtotal'])) ?></li>
            <li>Réduction : <?= e((string) $booking['discount_percent']) ?>% (− <?= e(money((float) $booking['discount_amount'])) ?>)</li>
            <li>Nettoyage : <?= e(money((float) $booking['cleaning_fee'])) ?></li>
            <li>Total : <?= e(money((float) $booking['total'])) ?></li>
            <li>Acompte <?= e((string) $depositPercent) ?>% : <?= e(money((float) $booking['deposit_amount'])) ?></li>
            <li>Solde : <?= e(money($balance)) ?></li>
            <li>Caution : <?= e(money((float) $booking['caution'])) ?></li>
        </ul>
        <div class="row">
            <label>Acompte %
                <input name="deposit_percent" type="number" min="0" max="100" step="0.01" value="<?= e((string) $depositPercent) ?>">
            </label>
            <label>Acompte €
                <input name="deposit_amount" type="number" min="0" step="0.01" value="<?= e((string) $booking['deposit_amount']) ?>">
            </label>
        </div>
        <p class="hint">Si vous saisissez un montant, il prime sur le %. Sinon le % calcule le montant. Vide = % des paramètres (<?= e(setting('deposit_percent', '20')) ?> %). Les totaux se recalculent si vous changez les dates.</p>

        <div class="row">
            <label class="chk"><input type="checkbox" name="deposit_paid" value="1" <?= !empty($booking['deposit_paid']) ? 'checked' : '' ?>> Acompte perçu</label>
            <label class="chk"><input type="checkbox" name="balance_paid" value="1" <?= !empty($booking['balance_paid']) ? 'checked' : '' ?>> Solde perçu</label>
        </div>
        <label>Statut
            <select name="status">
                <?php foreach ($statuses as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $booking['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Notes internes
            <textarea name="admin_notes" rows="4"><?= e($booking['admin_notes']) ?></textarea>
        </label>
        <?php if ($booking['status'] === 'pending'): ?>
            <div class="row">
                <button type="submit" name="quick" value="confirmed">Valider la réservation</button>
                <button class="danger" type="submit" name="quick" value="refused">Refuser</button>
            </div>
        <?php endif; ?>
        <button type="submit">Enregistrer les modifications</button>
        <p class="hint">« Confirmée » bloque les dates sur le calendrier public (bordeaux). « Refusée » les libère. Une demande en attente apparaît en doré.</p>
    </form>

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
