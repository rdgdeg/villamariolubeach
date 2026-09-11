<p>Téléchargez une copie de la base (réservations, tarifs, messages) et des photos. À faire avant chaque saison, et après une grosse mise à jour. Ces fichiers ne sont pas publics : ils ne s’ouvrent que si vous êtes connecté.</p>

<div class="row">
    <form method="post" class="card stack">
        <?= Csrf::field() ?>
        <input type="hidden" name="kind" value="sql">
        <h2>Base de données</h2>
        <p class="hint">Fichier SQL : réservations, grille, utilisateurs, messages, paramètres.</p>
        <button type="submit">Télécharger la base (.sql)</button>
    </form>
    <form method="post" class="card stack">
        <?= Csrf::field() ?>
        <input type="hidden" name="kind" value="files">
        <h2>Photos et fichiers</h2>
        <p class="hint">Archive ZIP du dossier d’images (et de la base SQLite en local).</p>
        <button type="submit">Télécharger les fichiers (.zip)</button>
    </form>
</div>
