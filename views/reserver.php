<?php
$countries = [
    'BE' => 'Belgique', 'FR' => 'France', 'NL' => 'Nederland', 'DE' => 'Deutschland',
    'IT' => 'Italia', 'GB' => 'United Kingdom', 'CH' => 'Schweiz', 'LU' => 'Luxembourg',
    'ES' => 'España', 'PT' => 'Portugal', 'AT' => 'Österreich', 'PL' => 'Polska',
    'IE' => 'Ireland', 'DK' => 'Danmark', 'SE' => 'Sverige', 'NO' => 'Norge',
    'US' => 'United States', 'CA' => 'Canada', 'AU' => 'Australia', 'OTHER' => t('book.country_other'),
];
$extras = t_arr('book.extra_items');
?>
<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow"><?= e(t('nav.book')) ?></p>
        <h1><?= e(t('book.title')) ?></h1>
        <p class="lead"><?= e(t('book.lead')) ?></p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <ol class="booking-progress" aria-label="<?= e(t('book.steps_label')) ?>">
            <li class="is-current" data-step-label="1"><span>1</span> <?= e(t('book.step1')) ?></li>
            <li data-step-label="2"><span>2</span> <?= e(t('book.step2')) ?></li>
        </ol>

        <form id="booking-form" class="booking-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="check_in" id="check_in">
            <input type="hidden" name="check_out" id="check_out">
            <label class="hp" aria-hidden="true">
                <input type="text" name="company" tabindex="-1" autocomplete="off">
            </label>

            <div id="booking-step-1">
                <div class="booking-layout">
                    <div>
                        <div class="calendar" data-calendar data-mode="booking"></div>
                        <div class="legend">
                            <span><i class="dot avail"></i> <?= e(t('calendar.available')) ?></span>
                            <span><i class="dot booked"></i> <?= e(t('calendar.booked')) ?></span>
                            <span><i class="dot pending"></i> <?= e(t('calendar.pending')) ?></span>
                            <span><i class="dot no"></i> <?= e(t('calendar.unavailable')) ?></span>
                            <span><i class="dot turnover"></i> <?= e(t('calendar.turnover')) ?></span>
                        </div>
                        <p class="hint"><?= e(t('calendar.select')) ?> <?= e(t('calendar.checkout_hint')) ?></p>
                    </div>
                    <aside class="booking-panel">
                        <h2 class="panel-title"><?= e(t('book.step1')) ?></h2>
                        <label><?= e(t('booking_widget.adults')) ?>
                            <select name="adults" id="adults">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <label><?= e(t('booking_widget.children')) ?>
                            <select name="children" id="children">
                                <?php for ($i = 0; $i <= 4; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <div id="quote" class="quote" hidden></div>
                        <p id="quote-error" class="form-error" hidden></p>
                        <ul class="included-mini">
                            <?php foreach (t_arr('book.included') as $item): ?>
                                <li><?= e($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button class="btn btn-terracotta" type="button" id="step-continue" disabled><?= e(t('book.continue')) ?></button>
                    </aside>
                </div>
            </div>

            <div id="booking-step-2" hidden>
                <div class="booking-layout">
                    <div>
                        <h2 class="panel-title"><?= e(t('book.your_info')) ?></h2>
                        <p class="hint"><?= e(t('book.required_hint')) ?></p>
                        <div class="form-grid-2">
                            <label><?= e(t('book.first_name')) ?> *
                                <input type="text" name="guest_first_name" autocomplete="given-name">
                            </label>
                            <label><?= e(t('book.last_name')) ?> *
                                <input type="text" name="guest_last_name" autocomplete="family-name">
                            </label>
                        </div>
                        <label><?= e(t('book.email')) ?> *
                            <input type="email" name="guest_email" autocomplete="email">
                        </label>
                        <label><?= e(t('book.phone')) ?> *
                            <input type="tel" name="guest_phone" autocomplete="tel">
                        </label>
                        <label><?= e(t('book.country')) ?> *
                            <select name="guest_country">
                                <option value=""><?= e(t('book.country_placeholder')) ?></option>
                                <?php foreach ($countries as $code => $label): ?>
                                    <option value="<?= e($code) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><?= e(t('book.occupants')) ?>
                            <textarea name="occupants" rows="3" placeholder="<?= e(t('book.occupants_ph')) ?>"></textarea>
                        </label>

                        <fieldset class="extras-box">
                            <legend><?= e(t('book.extras_title')) ?></legend>
                            <p class="hint"><?= e(t('book.extras_hint')) ?></p>
                            <?php foreach ($extras as $key => $label): ?>
                                <label class="chk">
                                    <input type="checkbox" name="extras[]" value="<?= e($key) ?>">
                                    <span><?= e($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>

                        <label><?= e(t('book.message')) ?>
                            <textarea name="guest_message" rows="4"></textarea>
                        </label>
                        <?php require ROOT . '/views/partials/stay-info.php'; ?>
                        <label class="chk terms">
                            <input type="checkbox" name="accept_terms" value="1">
                            <span><?= e(t('book.terms')) ?> <a href="<?= e(url_for('mentions')) ?>" target="_blank" rel="noopener"><?= e(t('book.terms_link')) ?></a> *</span>
                        </label>
                        <p id="step2-error" class="form-error" hidden></p>
                        <div class="step-actions">
                            <button class="btn btn-outline" type="button" id="step-back"><?= e(t('book.back')) ?></button>
                            <button class="btn btn-terracotta" type="submit" id="book-submit"><?= e(t('book.submit')) ?></button>
                        </div>
                        <p id="book-success" class="form-success" hidden><?= e(t('book.success')) ?></p>
                        <aside id="book-payinfo" class="pay-letter-box" hidden>
                            <h3><?= e(t('book.payinfo_title')) ?></h3>
                            <pre id="book-letter" class="pay-letter"></pre>
                        </aside>
                    </div>
                    <aside class="booking-panel" id="quote-summary"></aside>
                </div>
            </div>
        </form>
    </div>
</section>
