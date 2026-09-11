<?php
$statuses = [
    'pending' => 'En attente',
    'confirmed' => 'Confirmée',
    'refused' => 'Refusée',
    'cancelled' => 'Annulée',
];
$paid = (float) $stats['deposits_paid'] + (float) $stats['balances_paid'];
$outstanding = (float) $stats['total'] - $paid;
?>
<div class="kpi-grid">
    <article class="kpi">
        <span>Semaines réservées</span>
        <strong><?= e((string) $stats['weeks']) ?></strong>
        <small><?= (int) $stats['nights'] ?> nuits · <?= (int) $stats['confirmed_stays'] ?> séjour(s) confirmé(s)</small>
    </article>
    <article class="kpi">
        <span>Montant total</span>
        <strong><?= e(money((float) $stats['total'])) ?></strong>
        <small>séjours confirmés</small>
    </article>
    <article class="kpi">
        <span>Acomptes</span>
        <strong><?= e(money((float) $stats['deposits_paid'])) ?></strong>
        <small>dus <?= e(money((float) $stats['deposits_due'])) ?></small>
    </article>
    <article class="kpi">
        <span>Encaissé</span>
        <strong><?= e(money($paid)) ?></strong>
        <small>reste <?= e(money(max(0, $outstanding))) ?> · soldes <?= e(money((float) $stats['balances_paid'])) ?></small>
    </article>
    <article class="kpi warn">
        <span>Demandes en attente</span>
        <strong><?= (int) $stats['pending_stays'] ?></strong>
        <small><?= e(money((float) $stats['pending_total'])) ?></small>
    </article>
</div>

<p class="pending-banner"><?= (int) $pendingCount ?> demande(s) en attente</p>
<nav class="filters">
    <a href="<?= e(base_url('admin')) ?>">Toutes</a>
    <?php foreach ($statuses as $key => $label): ?>
        <a href="<?= e(base_url('admin?status=' . $key)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<div class="table-card">
    <table class="bookings-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Dates</th>
                <th>Client</th>
                <th>Personnes</th>
                <th>Total</th>
                <th>Paiement</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr class="is-clickable" data-href="<?= e(base_url('admin/booking/' . $b['id'])) ?>">
                    <td><?= (int) $b['id'] ?></td>
                    <td><?= e(format_date($b['check_in'])) ?> → <?= e(format_date($b['check_out'])) ?><br><small><?= (int) $b['nights'] ?> nuits</small></td>
                    <td><?= e($b['guest_name']) ?><br><small><?= e($b['guest_email']) ?></small></td>
                    <td><?= (int) $b['adults'] ?> ad. / <?= (int) $b['children'] ?> enf.</td>
                    <td><?= e(money((float) $b['total'])) ?></td>
                    <td>
                        <small>
                            Acompte <?= !empty($b['deposit_paid']) ? 'perçu' : 'dû' ?> <?= e(money((float) $b['deposit_amount'])) ?><br>
                            Solde <?= !empty($b['balance_paid']) ? 'perçu' : 'dû' ?>
                        </small>
                    </td>
                    <td><span class="status <?= e($b['status']) ?>"><?= e($statuses[$b['status']] ?? $b['status']) ?></span></td>
                    <td>
                        <a class="link" href="<?= e(base_url('admin/booking/' . $b['id'])) ?>">Ouvrir</a>
                        <?php if ($b['status'] === 'pending'): ?>
                            <form method="post" action="<?= e(base_url('admin/booking/' . $b['id'])) ?>" class="inline-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="deposit_amount" value="<?= e((string) $b['deposit_amount']) ?>">
                                <button type="submit" name="quick" value="confirmed" data-confirm-stay data-email="<?= e((string) $b['guest_email']) ?>" data-amount="<?= e((string) $b['deposit_amount']) ?>">Valider</button>
                                <button class="danger" type="submit" name="quick" value="refused" onclick="return confirm('Refuser cette demande et envoyer l’e-mail de refus ?');">Refuser</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
                <tr><td colspan="8">Aucune réservation.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
