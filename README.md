# Villa Mariolu Beach

Site de location de vacances (PHP + MySQL), pensé pour un hébergement **O2switch**.

Langues : français, anglais, italien, allemand, néerlandais, polonais.

## Déploiement O2switch

1. Dans cPanel, créez une base **MySQL** et un utilisateur avec tous les droits sur cette base.
2. Uploadez tout le dossier du projet à la racine du domaine (`public_html` ou le dossier du sous-domaine).
3. Copiez `config.example.php` vers `config.php`.
4. Renseignez :
   - `'driver' => 'mysql'`
   - host, nom de base, utilisateur, mot de passe
   - éventuellement `'base_url' => 'https://www.villamariolubeach.com'`
5. Ouvrez le site : les tables se créent toutes seules au premier chargement.
6. Admin : `https://votredomaine/admin`  
   Identifiant : `admin`  
   Mot de passe initial : celui défini dans `config.php` (`admin_password`, par défaut `VillaMariolu2027`).  
   Changez-le ensuite dans **Paramètres**.

Les e-mails de confirmation / paiement partent via PHP `mail()` (boîte du domaine chez O2switch). Si l’envoi échoue, les relances ouvrent encore la messagerie. Backups : Admin → Sauvegardes.

## Admin

- Voir / valider / refuser les demandes de réservation
- Modifier la grille tarifaire et les réductions long séjour
- Bloquer des dates sur le calendrier
- Ajuster caution, acompte, nuits min/max, e-mail, CIN / IUN

## Test en local

```bash
php -S localhost:8080 router.php
```

Le fichier `config.php` du projet est en SQLite pour le développement local.

## Parcours client

Le visiteur choisit des dates sur le calendrier, voit le prix (nuitées + réduction + nettoyage + acompte 10 % + caution), puis envoie une **demande**. L’hôte confirme dans l’admin, ce qui bloque les dates.
