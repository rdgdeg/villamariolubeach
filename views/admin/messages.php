<div class="table-card">
    <table>
        <thead><tr><th>Date</th><th>Nom</th><th>Email</th><th>Sujet</th><th>Message</th></tr></thead>
        <tbody>
            <?php foreach ($messages as $m): ?>
                <tr>
                    <td><?= e(format_date($m['created_at'], true)) ?></td>
                    <td><?= e($m['name']) ?></td>
                    <td><?= e($m['email']) ?></td>
                    <td><?= e($m['subject']) ?></td>
                    <td><?= nl2br(e($m['body'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$messages): ?><tr><td colspan="5">Aucun message.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
