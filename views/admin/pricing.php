<h2>Périodes</h2>
<p class="hint">Utilisez les flèches pour réordonner la liste. Un nouveau créneau s’ajoute en bas, puis vous le déplacez où vous voulez.</p>
<div class="table-card">
    <table>
        <thead>
            <tr>
                <th>Libellé</th><th>Début</th><th>Fin</th><th>€ / nuit</th><th>Fermé</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $seasonCount = count($seasons);
            foreach ($seasons as $i => $s):
            ?>
                <tr>
                    <td colspan="6">
                        <div class="season-row">
                        <form method="post" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="form" value="season_update">
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <input type="hidden" name="sort_order" value="<?= (int) $s['sort_order'] ?>">
                            <input name="label" value="<?= e($s['label']) ?>">
                            <input class="date-eu" name="start_date" value="<?= e(format_eu_date_parts((int) $s['start_day'], (int) $s['start_month'], isset($s['year']) && $s['year'] !== null && $s['year'] !== '' ? (int) $s['year'] : null)) ?>" placeholder="jj/mm/aaaa" inputmode="numeric" autocomplete="off" required>
                            <input class="date-eu" name="end_date" value="<?= e(format_eu_date_parts((int) $s['end_day'], (int) $s['end_month'], season_end_year($s))) ?>" placeholder="jj/mm/aaaa" inputmode="numeric" autocomplete="off" required>
                            <input type="number" step="1" name="nightly_rate" value="<?= e($s['nightly_rate']) ?>" style="width:6rem">
                            <label class="chk"><input type="checkbox" name="is_closed" value="1" <?= (int) $s['is_closed'] ? 'checked' : '' ?>> fermé</label>
                            <button type="submit">OK</button>
                        </form>
                        <div class="season-move">
                            <form method="post">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="form" value="season_move">
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="btn-icon" <?= $i === 0 ? 'disabled' : '' ?> title="Monter" aria-label="Monter">↑</button>
                            </form>
                            <form method="post">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="form" value="season_move">
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="btn-icon" <?= $i === $seasonCount - 1 ? 'disabled' : '' ?> title="Descendre" aria-label="Descendre">↓</button>
                            </form>
                            <form method="post" class="inline-del" onsubmit="return confirm('Supprimer cette période ?')">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="form" value="season_delete">
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <button type="submit" class="danger">×</button>
                            </form>
                        </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2>Ajouter une période</h2>
<form method="post" class="stack card">
    <?= Csrf::field() ?>
    <input type="hidden" name="form" value="season_create">
    <label>Libellé <input name="label" required></label>
    <div class="row">
        <label>Début <input class="date-eu" name="start_date" placeholder="01/04/2027" inputmode="numeric" autocomplete="off" required></label>
        <label>Fin <input class="date-eu" name="end_date" placeholder="15/04/2027" inputmode="numeric" autocomplete="off" required></label>
        <label>Tarif / nuit <input type="number" step="1" name="nightly_rate" required></label>
        <label class="chk"><input type="checkbox" name="is_closed" value="1"> Période fermée</label>
    </div>
    <p class="hint">Dates au format européen : jour/mois/année, par exemple 01/04/2027. L’année saisie est enregistrée (plus de retour forcé à l’année en cours). Le créneau est ajouté en bas de liste : déplacez-le ensuite avec les flèches.</p>
    <button type="submit">Ajouter</button>
</form>

<h2>Réductions long séjour</h2>
<?php foreach ($discounts as $d): ?>
    <form method="post" class="inline-form card">
        <?= Csrf::field() ?>
        <input type="hidden" name="form" value="discount_update">
        <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
        <label>Min nuits <input type="number" name="min_nights" value="<?= (int) $d['min_nights'] ?>"></label>
        <label>Max nuits <input type="number" name="max_nights" value="<?= (int) $d['max_nights'] ?>"></label>
        <label>% <input type="number" step="0.5" name="percent" value="<?= e($d['percent']) ?>"></label>
        <button type="submit">OK</button>
    </form>
    <form method="post" class="inline-del" onsubmit="return confirm('Supprimer ?')">
        <?= Csrf::field() ?>
        <input type="hidden" name="form" value="discount_delete">
        <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
        <button class="danger" type="submit">Supprimer</button>
    </form>
<?php endforeach; ?>
<form method="post" class="stack card">
    <?= Csrf::field() ?>
    <input type="hidden" name="form" value="discount_create">
    <div class="row">
        <label>Min nuits <input type="number" name="min_nights" required></label>
        <label>Max nuits <input type="number" name="max_nights" required></label>
        <label>% <input type="number" step="0.5" name="percent" required></label>
    </div>
    <button type="submit">Ajouter une réduction</button>
</form>
