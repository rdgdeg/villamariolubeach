# E-mails admin — modèles éditables et acomptes par réservation

Date : 2026-09-11  
Statut : en revue  
Langue du produit : FR, EN, IT, DE, NL, PL

## Objectif

L’admin peut modifier le contenu de chaque e-mail client (objet + corps) dans les 6 langues, avec des champs calculés d’après la réservation. À la validation, l’admin peut imposer un pourcentage d’acompte et/ou un montant pour **cette** réservation. Les relances automatiques selon des délais (acompte après confirmation, solde avant arrivée) sont prévues, mais **l’envoi automatique exact se configure en phase 2**.

## Hors scope (phase 2)

- Cron O2switch et envoi quotidien automatique des relances.
- Réglage fin des jours exacts en production (les champs délais existent déjà en phase 1, non branchés sur un cron).
- Parcours d’annulation côté client (l’admin annule à la demande du client).
- E-mails HTML riches (on reste en texte brut, comme aujourd’hui).

## Phase 1 — à livrer maintenant

### Modèles éditables

Page admin `/admin/emails` (entrée « E-mails » dans le menu).

Liste des types :

| Clé | Quand il part (phase 1) |
|---|---|
| `request` | Demande envoyée depuis le site (statut en attente) |
| `confirmed` | Admin passe la réservation en confirmée |
| `cancelled` | Admin passe en annulée (à la demande du client) |
| `refused` | Admin refuse |
| `deposit_reminder_1` | Relance manuelle « acompte » (1re) |
| `deposit_reminder_2` | Relance manuelle « acompte » (2e) — bouton distinct |
| `balance` | Relance manuelle « solde » (1re / demande de solde) |
| `balance_reminder` | Relance manuelle « solde » (2e) — bouton distinct |
| `deposit_paid` | Admin coche acompte perçu |
| `balance_paid` | Admin coche solde perçu |

Les mails déjà présents `prearrival`, `thanks` et `host` restent envoyés comme aujourd’hui. Ils deviennent aussi éditables sur la même page (même mécanisme), pour ne pas laisser deux systèmes de textes.

Chaque type a, par langue : `subject` + `body` (texte brut).

Les textes actuels de `StayCopy` / `lang/*/paymail` servent de **contenu initial** (seed). Si un modèle est vide ou absent, repli sur ce contenu actuel.

### Interface

- Menu gauche : liste des types (libellés FR).
- Droite : onglets FR / EN / IT / DE / NL / PL.
- Champs objet + texte.
- Puce cliquable pour insérer un champ auto à la position du curseur.
- Bouton Enregistrer (la langue affichée, ou toutes les langues du formulaire — un enregistrement enregistre les 6 langues du type courant).
- Bouton « Envoyer un test » : envoie le modèle de la langue affichée à l’adresse admin (`settings.email`), avec un jeu de données d’exemple (pas une vraie réservation).
- Bloc **Délais** (enregistré, non exécuté en phase 1) :
  - Relance acompte 1 : jours **après confirmation** (défaut 3)
  - Relance acompte 2 : jours **après confirmation**, indépendant (défaut 10)
  - Demande de solde : jours **avant arrivée** (défaut 60)
  - Relance solde : jours **avant arrivée** (défaut 30)

### Champs automatiques

Remplacés à l’envoi. Noms stables (français, identiques dans toutes les langues de modèle) :

| Jeton | Valeur |
|---|---|
| `{{prenom}}` | Prénom du client |
| `{{nom}}` | Nom complet |
| `{{dates}}` | Arrivée → départ, format jj/mm/aaaa |
| `{{arrivee}}` | Date d’arrivée |
| `{{depart}}` | Date de départ |
| `{{nuits}}` | Nombre de nuits |
| `{{total}}` | Total séjour (format monétaire) |
| `{{acompte_pct}}` | Pourcentage d’acompte de **cette** réservation |
| `{{acompte}}` | Montant d’acompte de **cette** réservation |
| `{{solde}}` | Total − acompte |
| `{{caution}}` | Caution |
| `{{iban}}` | IBAN des paramètres |
| `{{bic}}` | BIC |
| `{{banque}}` | Titulaire |
| `{{communication}}` | Communication virement |

Un jeton inconnu est laissé tel quel (l’admin voit l’erreur en test).

### Acompte à la validation

Sur la fiche réservation, champs optionnels **Acompte %** et **Acompte €** (en plus du % global des paramètres).

Règle, au passage en `confirmed` (et si l’admin enregistre ces champs sur une confirmée) :

1. Si un **montant** > 0 est saisi → `deposit_amount` = ce montant ; `deposit_percent` stocké = arrondi `(montant / total) * 100`.
2. Sinon si un **%** > 0 est saisi → `deposit_percent` = ce % ; `deposit_amount` = `total * % / 100` (arrondi au centime).
3. Sinon → % des paramètres globaux, montant calculé comme aujourd’hui via `Pricing::quote`.

Le mail `confirmed` utilise ces valeurs. Elles restent sur la réservation pour les relances et reçus.

Conflit % + montant : **le montant gagne** (règle 1).

### Relances manuelles (phase 1)

Sur la fiche, quatre boutons au lieu de deux :

- Relancer acompte 1 / Relancer acompte 2
- Relancer solde / Relancer solde (rappel)

Chaque clic envoie le modèle correspondant, dans la langue du client, et écrit `email_log`. Pas de double-envoi automatique en phase 1 (l’admin peut renvoyer à la main).

### Données

Table `email_templates` :

- `id`
- `kind` (texte)
- `lang` (fr, en, it, de, nl, pl)
- `subject`
- `body`
- `updated_at`
- unicité `(kind, lang)`

Colonne `bookings.deposit_percent` (décimal, défaut 0 = « suivre le global au moment du calcul »). Une fois confirmé, le % et le montant réellement utilisés sont persistés.

Settings (clés, non exécutées en phase 1) :

- `mail_deposit_remind_days_1` = `3`
- `mail_deposit_remind_days_2` = `10`
- `mail_balance_days_1` = `60`
- `mail_balance_days_2` = `30`

`email_log` existant : on continue d’y écrire `kind` + `ok`.

### Architecture code

- `EmailTemplates` : CRUD, seed depuis `StayCopy` actuel, rendu d’un modèle + remplacement des jetons.
- `StayCopy::email()` : demande d’abord le modèle persisté pour `(kind, guest_lang)` ; sinon le texte actuel.
- `Mailer` : inchangé dans son rôle d’envoi `mail()` + log ; `sendBooking` passe par `StayCopy`.
- `BookingService::setStatus('confirmed')` : applique la règle d’acompte avant l’envoi du mail.
- Route admin `emails` dans `index.php` + vue `views/admin/emails.php`.
- Menu dans `views/admin/layout.php`.

### Erreurs

- `mail()` échoue : log `ok = 0`, flash admin, le fallback `mailto:` existant reste sur la fiche.
- Modèle manquant : repli `StayCopy` actuel, pas d’envoi vide.
- Test send : même chemin `Mailer::send`, `kind` = `test-<type>`.
- CSRF sur tous les POST admin.

### Vérification phase 1

1. Modifier le mail « demande reçue » en FR, envoyer une demande test depuis `/fr/reserver` : le nouveau texte part, champs dates/nom remplis.
2. Même modèle en EN : une demande avec langue EN utilise l’EN.
3. Valider une réservation avec 25 % : mail de confirmation et fiche montrent 25 % et le montant calculé.
4. Valider une autre avec 400 € : montant 400 €, % dérivé.
5. Relances manuelles acompte 1 / 2 et solde / rappel : bons modèles, journal.
6. Annulation admin : mail `cancelled` édité.
7. Bouton test : un mail arrive sur l’adresse admin.

## Phase 2 — plus tard (rappel)

Un cron quotidien (URL secrète) :

- Confirmée, acompte non reçu, J ≥ X après confirmation, jamais envoyé `deposit_reminder_1` → envoi.
- Idem Y jours → `deposit_reminder_2`.
- Confirmée, solde non reçu, arrivée dans A jours, jamais `balance` → envoi.
- Idem B jours → `balance_reminder`.
- Une fois par type et par réservation (source : `email_log` ok=1). Une relance manuelle du même `kind` compte comme déjà envoyée.

Les valeurs X/Y/A/B sont celles du bloc Délais.

## Décisions figées

- 6 langues éditables, onglet par langue.
- Annulation = action admin uniquement.
- Relances acompte : deux, calées en jours **après confirmation**.
- Relances solde : deux, calées en jours **avant arrivée**.
- À la validation : montant saisi prioritaire sur le %.
- Texte brut. Pas d’annulation en ligne côté client.
- Phase 1 sans cron.
