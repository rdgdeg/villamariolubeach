<p>Cliquez une date d’arrivée puis une date de départ (jour de libération) pour bloquer une période. Survolez une case occupée pour voir le client ; cliquez pour ouvrir le détail. Les réservations confirmées apparaissent en bordeaux, les demandes en attente en doré, les blocages en gris.</p>

<div class="legend">
    <span><i class="dot avail"></i> Disponible</span>
    <span><i class="dot booked"></i> Réservé (confirmé)</span>
    <span><i class="dot pending"></i> En attente de validation</span>
    <span><i class="dot no"></i> Indisponible / bloqué</span>
    <span><i class="dot turnover"></i> Jour de départ (bascule)</span>
</div>

<div class="calendar" data-calendar data-mode="block" data-months="6"></div>

<form method="post" class="stack card" id="block-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="form" value="block">
    <p class="hint">La fin est le jour du départ : la villa redevient libre ce matin-là.</p>
    <div class="row">
        <label>Début <input type="date" name="check_in" required></label>
        <label>Fin (jour de libération) <input type="date" name="check_out" required></label>
        <label>Note <input name="note" placeholder="Ex. propriétaires, travaux"></label>
    </div>
    <button type="submit">Bloquer ces dates</button>
</form>

<div class="table-card">
    <h2>Dates bloquées manuellement</h2>
    <table>
        <thead><tr><th>Du</th><th>Au</th><th>Note</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($blocks as $b): ?>
                <tr>
                    <td><?= e(format_date($b['check_in'])) ?></td>
                    <td><?= e(format_date($b['check_out'])) ?></td>
                    <td><?= e($b['admin_notes']) ?></td>
                    <td>
                        <form method="post">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="form" value="unblock">
                            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                            <button class="danger" type="submit">Retirer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$blocks): ?><tr><td colspan="4">Aucun blocage manuel.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="table-card">
    <h2>Demandes et réservations à venir</h2>
    <table>
        <thead><tr><th>Statut</th><th>Du</th><th>Au</th><th>Client</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($stays as $s): ?>
                <tr>
                    <td><span class="status <?= e($s['status']) ?>"><?= $s['status'] === 'pending' ? 'En attente' : 'Confirmée' ?></span></td>
                    <td><?= e(format_date($s['check_in'])) ?></td>
                    <td><?= e(format_date($s['check_out'])) ?></td>
                    <td><?= e($s['guest_name']) ?><br><small><?= e($s['guest_email']) ?></small></td>
                    <td><a class="link" href="<?= e(base_url('admin/booking/' . $s['id'])) ?>">Ouvrir</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$stays): ?><tr><td colspan="5">Aucune réservation à venir.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
