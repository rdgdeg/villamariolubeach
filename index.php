<?php

require __DIR__ . '/src/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$frontController = (bool) preg_match('#/(index|router)\.php$#', $scriptName);
$basePath = rtrim(dirname($scriptName), '/');
if (
    $frontController
    && $basePath
    && $basePath !== '/'
    && str_starts_with($uri, $basePath)
) {
    $uri = substr($uri, strlen($basePath)) ?: '/';
}
$path = trim($uri, '/');
$segments = $path === '' ? [] : explode('/', $path);

if (($segments[0] ?? '') === 'robots.txt') {
    Seo::outputRobots();
}
if (($segments[0] ?? '') === 'sitemap.xml') {
    Seo::outputSitemap();
}

if (($segments[0] ?? '') === 'api') {
    handle_api(array_slice($segments, 1));
}

if (($segments[0] ?? '') === 'admin') {
    handle_admin(array_slice($segments, 1));
}

$lang = config('default_lang', 'en');
$page = 'home';
if (!$segments) {
    redirect(url_for('home', [], $lang));
}
if (in_array($segments[0], I18n::LANGS, true)) {
    $lang = $segments[0];
    $page = $segments[1] ?? 'home';
} else {
    $lang = I18n::load($lang);
    redirect(url_for($segments[0], [], $lang));
}
I18n::load($lang);

$publicPages = ['home', 'reserver', 'contact', 'mentions'];
if (!in_array($page, $publicPages, true)) {
    http_response_code(404);
    $page = 'home';
}

render_public($page);
exit;

function render_public(string $page): void
{
    $title = match ($page) {
        'reserver' => t('meta.booking_title'),
        'contact' => t('meta.contact_title'),
        'mentions' => t('meta.legal_title'),
        default => t('meta.home_title'),
    };
    $description = Seo::description($page);
    $canonical = url_for($page);
    $ogImage = Seo::image();
    ob_start();
    $view = ROOT . '/views/' . ($page === 'home' ? 'home' : $page) . '.php';
    require $view;
    $content = ob_get_clean();
    require ROOT . '/views/layout.php';
}

function handle_api(array $segments): void
{
    $lang = $_GET['lang'] ?? config('default_lang', 'en');
    I18n::load(is_string($lang) ? $lang : config('default_lang', 'en'));
    $endpoint = $segments[0] ?? '';

    if ($endpoint === 'calendar' && request_method() === 'GET') {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) query('month', '')) ? query('month') : date('Y-m');
        $months = min(12, max(1, (int) query('months', 2)));
        json_response([
            'ok' => true,
            'months' => Availability::calendar($month, $months, query('details') && Auth::check()),
            'min_nights' => (int) setting('min_nights', 6),
            'max_nights' => (int) setting('max_nights', 21),
        ]);
    }

    if ($endpoint === 'quote' && request_method() === 'POST') {
        $payload = json_input();
        json_response(Pricing::quote($payload['check_in'] ?? '', $payload['check_out'] ?? ''));
    }

    if ($endpoint === 'booking' && request_method() === 'POST') {
        $payload = $_POST ?: json_input();
        if (!Csrf::check($payload['csrf'] ?? null)) {
            json_response(['ok' => false, 'error' => 'csrf'], 419);
        }
        if (trim((string) ($payload['company'] ?? '')) !== '') {
            json_response(['ok' => true, 'id' => 0]);
        }
        if (!empty($_SESSION['form_at']) && time() - (int) $_SESSION['form_at'] < 4) {
            json_response(['ok' => false, 'error' => 'rate'], 429);
        }
        $_SESSION['form_at'] = time();
        $result = BookingService::createRequest($payload);
        json_response($result, !empty($result['ok']) ? 200 : 422);
    }

    if ($endpoint === 'mail-preview' && request_method() === 'GET') {
        if (!Auth::check()) {
            json_response(['ok' => false, 'error' => 'auth'], 401);
        }
        $booking = BookingService::find((int) query('id', 0));
        $kind = StayCopy::alias((string) query('kind', ''));
        $allowed = array_keys(EmailTemplates::kinds());
        if (!$booking || !in_array($kind, $allowed, true) || $kind === 'host') {
            json_response(['ok' => false, 'error' => 'not_found'], 404);
        }
        $rawAmount = trim((string) query('deposit_amount', ''));
        if ($rawAmount !== '') {
            $amount = (float) str_replace(',', '.', $rawAmount);
            $rental = round((float) $booking['rental_subtotal'] - (float) $booking['discount_amount'], 2);
            $booking['deposit_amount'] = round(max(0, $amount), 2);
            $booking['deposit_percent'] = $rental > 0 ? round(($booking['deposit_amount'] / $rental) * 100, 2) : 0.0;
        }
        $msg = StayCopy::email($booking, $kind);
        json_response([
            'ok' => true,
            'kind' => $kind,
            'label' => EmailTemplates::kinds()[$kind] ?? $kind,
            'to' => (string) $booking['guest_email'],
            'subject' => $msg['subject'],
            'body' => $msg['body'],
        ]);
    }

    if ($endpoint === 'contact' && request_method() === 'POST') {
        Csrf::requireValid();
        if (trim((string) post('company')) !== '') {
            flash('success', t('contact.success'));
            redirect(url_for('contact'));
        }
        if (!empty($_SESSION['form_at']) && time() - (int) $_SESSION['form_at'] < 4) {
            flash('error', t('contact.error'));
            redirect(url_for('contact'));
        }
        $_SESSION['form_at'] = time();
        $name = trim((string) post('name'));
        $email = trim((string) post('email'));
        $phone = trim((string) post('phone'));
        $subject = trim((string) post('subject'));
        $body = trim((string) post('message'));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $body === '') {
            flash('error', t('contact.error'));
            redirect(url_for('contact'));
        }
        db()->prepare('INSERT INTO messages (name, email, phone, subject, body, lang, created_at) VALUES (?,?,?,?,?,?,?)')
            ->execute([$name, $email, $phone, $subject, $body, current_lang(), date('Y-m-d H:i:s')]);
        Mailer::notifyHost(
            t('paymail.subject_contact', ['name' => $name]),
            implode("\n", [
                $name, $email, $phone, $subject, '', $body,
            ]),
            $email
        );
        flash('success', t('contact.success'));
        redirect(url_for('contact'));
    }

    json_response(['ok' => false, 'error' => 'not_found'], 404);
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

/** @return array{to?:string,subject?:string,body?:string} */
function admin_mail_override(): array
{
    $override = [];
    $to = trim((string) post('mail_to'));
    $subject = trim((string) post('mail_subject'));
    $body = (string) post('mail_body');
    if ($to !== '') {
        $override['to'] = $to;
    }
    if ($subject !== '') {
        $override['subject'] = $subject;
    }
    if (trim($body) !== '') {
        $override['body'] = $body;
    }
    return $override;
}

function handle_admin(array $segments): void
{
    I18n::load('fr');
    $action = $segments[0] ?? 'dashboard';

    if ($action === 'login') {
        if (Auth::check()) {
            redirect(base_url('admin'));
        }
        if (request_method() === 'POST') {
            Csrf::requireValid();
            if (Auth::isLocked()) {
                flash('error', 'Trop de tentatives. Réessayez dans ' . Auth::lockRemainingMinutes() . ' min.');
            } elseif (Auth::attempt((string) post('username'), (string) post('password'))) {
                redirect(base_url('admin'));
            } else {
                flash('error', Auth::isLocked()
                    ? 'Trop de tentatives. Réessayez dans ' . Auth::lockRemainingMinutes() . ' min.'
                    : 'Identifiants incorrects.');
            }
        }
        $title = 'Connexion admin';
        ob_start();
        require ROOT . '/views/admin/login.php';
        $content = ob_get_clean();
        require ROOT . '/views/admin/layout.php';
        exit;
    }

    if ($action === 'logout') {
        Auth::logout();
        redirect(base_url('admin/login'));
    }

    Auth::requireLogin();
    $adminUser = Auth::user();

    if ($action === 'booking' && isset($segments[1])) {
        $booking = BookingService::find((int) $segments[1]);
        if (!$booking) {
            flash('error', 'Réservation introuvable.');
            redirect(base_url('admin'));
        }
        if (request_method() === 'GET' && query('preview')) {
            $kind = StayCopy::alias((string) query('preview'));
            $allowed = array_keys(EmailTemplates::kinds());
            if (!in_array($kind, $allowed, true) || $kind === 'host') {
                json_response(['ok' => false, 'error' => 'not_found'], 404);
            }
            $rawAmount = trim((string) query('deposit_amount', ''));
            if ($rawAmount !== '') {
                $amount = (float) str_replace(',', '.', $rawAmount);
                $rental = round((float) $booking['rental_subtotal'] - (float) $booking['discount_amount'], 2);
                $booking['deposit_amount'] = round(max(0, $amount), 2);
                $booking['deposit_percent'] = $rental > 0 ? round(($booking['deposit_amount'] / $rental) * 100, 2) : 0.0;
            }
            $msg = StayCopy::email($booking, $kind);
            json_response([
                'ok' => true,
                'kind' => $kind,
                'label' => EmailTemplates::kinds()[$kind] ?? $kind,
                'to' => (string) $booking['guest_email'],
                'subject' => $msg['subject'],
                'body' => $msg['body'],
            ]);
        }
        if (request_method() === 'POST') {
            Csrf::requireValid();
            $form = (string) post('form');
            $id = (int) $booking['id'];
            if ($form === 'delete') {
                BookingService::delete($id);
                flash('success', 'Réservation supprimée.');
                redirect(base_url('admin'));
            }
            if ($form === 'remind') {
                $allowed = ['deposit_reminder_1', 'deposit_reminder_2', 'balance', 'balance_reminder'];
                $kind = (string) post('kind');
                if (!in_array($kind, $allowed, true)) {
                    $kind = 'deposit_reminder_1';
                }
                $sent = Mailer::sendBooking($booking, $kind, admin_mail_override());
                BookingService::markReminded($id, $kind);
                BookingEvents::log($id, $kind);
                $labels = [
                    'deposit_reminder_1' => 'Relance acompte 1 envoyée par e-mail.',
                    'deposit_reminder_2' => 'Relance acompte 2 envoyée par e-mail.',
                    'balance' => 'Demande de solde envoyée par e-mail.',
                    'balance_reminder' => 'Relance du solde envoyée par e-mail.',
                ];
                if ($sent) {
                    flash('success', $labels[$kind]);
                } else {
                    flash('mailto', StayCopy::mailto($booking, $kind));
                    flash('success', 'L’e-mail n’a pas pu partir automatiquement. Ouverture de votre messagerie.');
                }
                redirect(base_url('admin/booking/' . $id));
            }
            if ($form === 'journey') {
                $kind = (string) post('kind');
                $allowed = ['prearrival', 'thanks', 'confirmed', 'request'];
                if (!in_array($kind, $allowed, true)) {
                    $kind = 'prearrival';
                }
                $sent = Mailer::sendBooking($booking, $kind, admin_mail_override());
                BookingEvents::log($id, $kind);
                if ($kind === 'prearrival') {
                    db()->prepare('UPDATE bookings SET prearrival_sent_at = ?, updated_at = ? WHERE id = ?')
                        ->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
                }
                if ($kind === 'thanks') {
                    db()->prepare('UPDATE bookings SET thanks_sent_at = ?, updated_at = ? WHERE id = ?')
                        ->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
                }
                flash($sent ? 'success' : 'error', $sent
                    ? 'E-mail envoyé.'
                    : 'Envoi impossible. Vérifiez l’adresse e-mail du serveur (Paramètres).');
                redirect(base_url('admin/booking/' . $id));
            }
            if ($form === 'update') {
                if (post('quick')) {
                    $_POST['status'] = post('quick');
                }
                $res = BookingService::update($id, $_POST);
                flash($res['ok'] ? 'success' : 'error', $res['ok']
                    ? 'Réservation mise à jour.'
                    : 'Impossible d’enregistrer : ' . ($res['error'] ?? 'erreur'));
                redirect(base_url('admin/booking/' . $id));
            }
            $status = (string) (post('quick') ?: post('status'));
            $notes = (string) post('admin_notes');
            if ($status === 'confirmed') {
                $rawAmount = trim((string) post('deposit_amount', ''));
                if ($rawAmount !== '') {
                    BookingService::applyDepositAmount($id, (float) str_replace(',', '.', $rawAmount));
                }
            }
            if (!BookingService::setStatus($id, $status, $notes)) {
                flash('error', 'Impossible de confirmer : dates déjà occupées.');
            } else {
                flash('success', 'Réservation mise à jour.');
            }
            redirect(base_url('admin/booking/' . $id));
        }
        $title = 'Demande #' . $booking['id'];
        $rentalNet = round((float) $booking['rental_subtotal'] - (float) $booking['discount_amount'], 2);
        $depositAmount = (float) $booking['deposit_amount'];
        if ($rentalNet > 0 && $depositAmount > 0) {
            $pct = round(($depositAmount / $rentalNet) * 100, 2);
            if (abs((float) $booking['deposit_percent'] - $pct) > 0.05) {
                db()->prepare('UPDATE bookings SET deposit_percent = ? WHERE id = ?')
                    ->execute([$pct, (int) $booking['id']]);
                $booking['deposit_percent'] = $pct;
            }
        }
        BookingEvents::backfill($booking);
        $bookingEvents = BookingEvents::forBooking((int) $booking['id']);
        $bookingStages = BookingEvents::stages($booking);
        ob_start();
        require ROOT . '/views/admin/booking.php';
        $content = ob_get_clean();
        require ROOT . '/views/admin/layout.php';
        exit;
    }

    if ($action === 'pricing') {
        if (request_method() === 'POST') {
            Csrf::requireValid();
            $form = (string) post('form');
            if ($form === 'season_move') {
                Pricing::moveSeason((int) post('id'), (string) post('direction'));
                flash('success', 'Ordre des périodes mis à jour.');
                redirect(base_url('admin/pricing'));
            }
            if ($form === 'season_update' || $form === 'season_create') {
                $start = parse_eu_date((string) post('start_date'));
                $end = parse_eu_date((string) post('end_date'));
                if (!$start || !$end) {
                    flash('error', 'Indiquez les dates au format européen, par exemple 01/12/2026.');
                    redirect(base_url('admin/pricing'));
                }
                if ($form === 'season_update') {
                    $stmt = db()->prepare(
                        'UPDATE rate_seasons SET label=?, start_month=?, start_day=?, end_month=?, end_day=?, nightly_rate=?, is_closed=?, sort_order=? WHERE id=?'
                    );
                    $stmt->execute([
                        post('label'), $start['month'], $start['day'],
                        $end['month'], $end['day'], (float) post('nightly_rate'),
                        post('is_closed') ? 1 : 0, (int) post('sort_order'), (int) post('id'),
                    ]);
                    flash('success', 'Période mise à jour.');
                } else {
                    db()->prepare(
                        'INSERT INTO rate_seasons (label, year, start_month, start_day, end_month, end_day, nightly_rate, is_closed, sort_order) VALUES (?,NULL,?,?,?,?,?,?,?)'
                    )->execute([
                        post('label'), $start['month'], $start['day'],
                        $end['month'], $end['day'], (float) post('nightly_rate'),
                        post('is_closed') ? 1 : 0, Pricing::nextSortOrder(),
                    ]);
                    flash('success', 'Période ajoutée.');
                }
            } elseif ($form === 'season_delete') {
                db()->prepare('DELETE FROM rate_seasons WHERE id = ?')->execute([(int) post('id')]);
                flash('success', 'Période supprimée.');
            } elseif ($form === 'discount_update') {
                db()->prepare('UPDATE discounts SET min_nights=?, max_nights=?, percent=? WHERE id=?')
                    ->execute([(int) post('min_nights'), (int) post('max_nights'), (float) post('percent'), (int) post('id')]);
                flash('success', 'Réduction mise à jour.');
            } elseif ($form === 'discount_create') {
                db()->prepare('INSERT INTO discounts (min_nights, max_nights, percent) VALUES (?,?,?)')
                    ->execute([(int) post('min_nights'), (int) post('max_nights'), (float) post('percent')]);
                flash('success', 'Réduction ajoutée.');
            } elseif ($form === 'discount_delete') {
                db()->prepare('DELETE FROM discounts WHERE id = ?')->execute([(int) post('id')]);
                flash('success', 'Réduction supprimée.');
            }
            redirect(base_url('admin/pricing'));
        }
        $seasons = Pricing::seasons();
        $discounts = Pricing::discounts();
        $title = 'Grille tarifaire';
        ob_start();
        require ROOT . '/views/admin/pricing.php';
        $content = ob_get_clean();
        require ROOT . '/views/admin/layout.php';
        exit;
    }

    if ($action === 'calendar') {
        if (request_method() === 'POST') {
            Csrf::requireValid();
            $form = (string) post('form');
            if ($form === 'block') {
                $res = BookingService::blockDates((string) post('check_in'), (string) post('check_out'), (string) post('note'));
                flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Dates bloquées.' : 'Dates invalides.');
            } elseif ($form === 'unblock') {
                db()->prepare("DELETE FROM bookings WHERE id = ? AND status = 'blocked'")->execute([(int) post('id')]);
                flash('success', 'Blocage retiré.');
            }
            redirect(base_url('admin/calendar'));
        }
        $blocks = db()->query("SELECT * FROM bookings WHERE status = 'blocked' ORDER BY check_in")->fetchAll();
        $stayStmt = db()->prepare(
            "SELECT * FROM bookings
             WHERE status IN ('pending', 'confirmed') AND check_out >= ?
             ORDER BY check_in"
        );
        $stayStmt->execute([date('Y-m-d')]);
        $stays = $stayStmt->fetchAll();
        $title = 'Calendrier';
        ob_start();
        require ROOT . '/views/admin/calendar.php';
        $content = ob_get_clean();
        require ROOT . '/views/admin/layout.php';
        exit;
    }

    if ($action === 'messages') {
        $messages = db()->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();
        $title = 'Messages';
        ob_start();
        require ROOT . '/views/admin/messages.php';
        $content = ob_get_clean();
        require ROOT . '/views/admin/layout.php';
        exit;
    }

    if ($action === 'emails') {
        EmailTemplates::ensureSeeded();
        $kinds = EmailTemplates::kinds();
        $kind = (string) (post('kind') ?: query('kind', 'request'));
        if (!isset($kinds[$kind])) {
            $kind = 'request';
        }
        if (request_method() === 'POST') {
            Csrf::requireValid();
            $form = (string) post('form');
            if ($form === 'delays') {
                foreach (['mail_deposit_remind_days_1', 'mail_deposit_remind_days_2', 'mail_balance_days_1', 'mail_balance_days_2'] as $key) {
                    $value = max(1, min(365, (int) post($key, '1')));
                    set_setting($key, (string) $value);
                }
                flash('success', 'Délais enregistrés. Ils serviront aux envois automatiques plus tard.');
                redirect(base_url('admin/emails?kind=' . rawurlencode($kind)));
            }
            if ($form === 'save') {
                EmailTemplates::saveKind($kind, (array) ($_POST['subjects'] ?? []), (array) ($_POST['bodies'] ?? []));
                flash('success', 'Modèle enregistré dans les 6 langues.');
                redirect(base_url('admin/emails?kind=' . rawurlencode($kind)));
            }
            if ($form === 'test') {
                $lang = (string) post('lang', 'fr');
                if (!in_array($lang, I18n::LANGS, true)) {
                    $lang = 'fr';
                }
                $sample = EmailTemplates::sampleBooking($lang);
                $msg = StayCopy::email($sample, $kind);
                $to = trim((string) setting('email', ''));
                $sent = Mailer::send($to, $msg['subject'], $msg['body'], null, 'test-' . $kind, null);
                flash($sent ? 'success' : 'error', $sent
                    ? 'E-mail de test envoyé à ' . $to . ' (' . strtoupper($lang) . ').'
                    : 'Envoi impossible. Vérifiez l’adresse et l’option « Envoyer les e-mails » dans Paramètres.');
                redirect(base_url('admin/emails?kind=' . rawurlencode($kind)));
            }
        }
        $templates = EmailTemplates::forKind($kind);
        $title = 'E-mails';
        ob_start();
        require ROOT . '/views/admin/emails.php';
        $content = ob_get_clean();
        require ROOT . '/views/admin/layout.php';
        exit;
    }

    if ($action === 'settings') {
        if (request_method() === 'POST') {
            Csrf::requireValid();
            foreach (['cleaning_fee', 'caution', 'deposit_percent', 'min_nights', 'max_nights', 'email', 'address', 'cin', 'iun', 'bank_name', 'iban', 'bic', 'payment_ref', 'mail_from'] as $key) {
                if (isset($_POST[$key])) {
                    set_setting($key, post($key));
                }
            }
            set_setting('mail_enabled', post('mail_enabled') ? '1' : '0');
            if (post('new_password') !== '') {
                if (post('new_password') !== post('new_password_confirm')) {
                    flash('error', 'Les mots de passe ne correspondent pas.');
                    redirect(base_url('admin/settings'));
                }
                Auth::updatePassword((int) $adminUser['id'], (string) post('new_password'));
            }
            flash('success', 'Paramètres enregistrés.');
            redirect(base_url('admin/settings'));
        }
        $title = 'Paramètres';
        ob_start();
        require ROOT . '/views/admin/settings.php';
        $content = ob_get_clean();
        require ROOT . '/views/admin/layout.php';
        exit;
    }

    if ($action === 'backup') {
        if (request_method() === 'POST') {
            Csrf::requireValid();
            $kind = (string) post('kind');
            if ($kind === 'sql') {
                Backup::downloadSql();
            }
            if ($kind === 'files') {
                Backup::downloadFiles();
            }
        }
        $title = 'Sauvegardes';
        ob_start();
        require ROOT . '/views/admin/backup.php';
        $content = ob_get_clean();
        require ROOT . '/views/admin/layout.php';
        exit;
    }

    $filter = query('status');
    $bookings = BookingService::all(is_string($filter) && $filter !== '' ? $filter : null);
    $pendingCount = (int) db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    $stats = BookingService::stats();
    $title = 'Réservations';
    ob_start();
    require ROOT . '/views/admin/dashboard.php';
    $content = ob_get_clean();
    require ROOT . '/views/admin/layout.php';
    exit;
}
