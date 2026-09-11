<form method="post" class="stack card">
    <?= Csrf::field() ?>
    <div class="row">
        <label>Nettoyage (€) <input name="cleaning_fee" type="number" step="1" value="<?= e(setting('cleaning_fee')) ?>"></label>
        <label>Caution (€) <input name="caution" type="number" step="1" value="<?= e(setting('caution')) ?>"></label>
        <label>Acompte (%) <input name="deposit_percent" type="number" step="1" value="<?= e(setting('deposit_percent')) ?>"></label>
        <label>Min. nuits <input name="min_nights" type="number" value="<?= e(setting('min_nights')) ?>"></label>
        <label>Max. nuits <input name="max_nights" type="number" value="<?= e(setting('max_nights')) ?>"></label>
    </div>
    <label>E-mail <input name="email" type="email" value="<?= e(setting('email')) ?>"></label>
    <label>Adresse <input name="address" value="<?= e(setting('address')) ?>"></label>
    <label>CIN <input name="cin" value="<?= e(setting('cin')) ?>"></label>
    <label>IUN <input name="iun" value="<?= e(setting('iun')) ?>"></label>
    <h2>Virement</h2>
    <label>Titulaire <input name="bank_name" value="<?= e(setting('bank_name')) ?>"></label>
    <label>IBAN <input name="iban" value="<?= e(setting('iban')) ?>"></label>
    <div class="row">
        <label>BIC <input name="bic" value="<?= e(setting('bic')) ?>"></label>
        <label>Communication <input name="payment_ref" value="<?= e(setting('payment_ref')) ?>"></label>
    </div>
    <h2>E-mails</h2>
    <p class="hint">Les e-mails partent via le serveur (O2switch). L’adresse « Expéditeur » doit idéalement être une boîte du domaine (ex. contact@villamariolubeach.com). Si l’envoi échoue, les relances ouvrent encore votre messagerie.</p>
    <label class="chk"><input type="checkbox" name="mail_enabled" value="1" <?= setting('mail_enabled', '1') !== '0' ? 'checked' : '' ?>> Envoyer les e-mails automatiquement</label>
    <label>Expéditeur (From) <input type="email" name="mail_from" value="<?= e(setting('mail_from')) ?>" placeholder="<?= e(setting('email')) ?>"></label>
    <h2>Changer le mot de passe admin</h2>
    <label>Nouveau mot de passe <input type="password" name="new_password" autocomplete="new-password"></label>
    <label>Confirmation <input type="password" name="new_password_confirm" autocomplete="new-password"></label>
    <button type="submit">Enregistrer</button>
</form>
