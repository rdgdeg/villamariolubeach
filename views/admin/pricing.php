<h2>Périodes</h2>
<div class="table-card">
    <table>
        <thead>
            <tr>
                <th>Libellé</th><th>Début</th><th>Fin</th><th>€ / nuit</th><th>Fermé</th><th>Ordre</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($seasons as $s): ?>
                <tr>
                    <td colspan="7">
                        <form method="post" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="form" value="season_update">
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <input name="label" value="<?= e($s['label']) ?>">
                            <input class="date-eu" name="start_date" value="<?= e(format_eu_date_parts((int) $s['start_day'], (int) $s['start_month'])) ?>" placeholder="jj/mm/aaaa" inputmode="numeric" autocomplete="off" required>
                            <input class="date-eu" name="end_date" value="<?= e(format_eu_date_parts((int) $s['end_day'], (int) $s['end_month'])) ?>" placeholder="jj/mm/aaaa" inputmode="numeric" autocomplete="off" required>
                            <input type="number" step="1" name="nightly_rate" value="<?= e($s['nightly_rate']) ?>" style="width:6rem">
                            <label class="chk"><input type="checkbox" name="is_closed" value="1" <?= (int) $s['is_closed'] ? 'checked' : '' ?>> fermé</label>
                            <input type="number" name="sort_order" value="<?= (int) $s['sort_order'] ?>" style="width:5rem">
                            <button type="submit">OK</button>
                        </form>
                        <form method="post" class="inline-del" onsubmit="return confirm('Supprimer cette période ?')">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="form" value="season_delete">
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <button type="submit" class="danger">×</button>
                        </form>
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
        <label>Début <input class="date-eu" name="start_date" placeholder="01/12/2026" inputmode="numeric" autocomplete="off" required></label>
        <label>Fin <input class="date-eu" name="end_date" placeholder="31/12/2026" inputmode="numeric" autocomplete="off" required></label>
        <label>Tarif / nuit <input type="number" step="1" name="nightly_rate" required></label>
        <label>Ordre <input type="number" name="sort_order" value="200"></label>
        <label class="chk"><input type="checkbox" name="is_closed" value="1"> Période fermée</label>
    </div>
    <p class="hint">Dates au format européen : jour/mois/année, par exemple 01/12/2026.</p>
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
