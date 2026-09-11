<?php
$seasons = Pricing::seasons();
$points = t_arr('villa.points');
$amenities = t_arr('amenities.grid');
$amenityOrder = [
    'air-conditioning', 'free-wifi', 'covered-terrace', 'dish-washer', 'washing-machine', 'private-parking',
    'outside-shower', 'barbecue', 'television', 'hair-dryer', 'vacuum-cleaner', 'coffee-machine',
];
$factIcons = [
    ['2-6-persons', 'facts.guests'],
    ['2-bedrooms', 'facts.bedrooms'],
    ['1-bathroom', 'facts.bathroom'],
    ['5-min-from-walking', 'facts.beach'],
    ['air-conditioning', 'facts.ac'],
    ['private-parking', 'facts.parking'],
];
$steps = t_arr('howto.steps');
$faq = t_arr('faq.items');
$trust = t_arr('trust.items');
$places = t_arr('location.places');
$heroSlides = [
    ['src' => 'hero/sea-view.jpg', 'alt' => 'Villa Mariolu Beach, vue mer à Budoni'],
    ['src' => 'hero/terrace.jpg', 'alt' => 'Terrasse de la villa face à la mer'],
    ['src' => 'hero/house.jpg', 'alt' => 'Villa Mariolu Beach et son jardin'],
    ['src' => 'gallery/ext-olive.jpg', 'alt' => 'Olivier et terrasse de la villa'],
    ['src' => 'location/beach.jpg', 'alt' => 'Plage Punta Sant’Anna à Budoni'],
];
$placeCards = [
    ['photo' => 'places/cala-brandinchi.jpg', 'maps' => 'https://www.google.com/maps/search/?api=1&query=Cala+Brandinchi+San+Teodoro+Sardinia'],
    ['photo' => 'places/tavolara.jpg', 'maps' => 'https://www.google.com/maps/search/?api=1&query=Isola+di+Tavolara+Sardinia'],
    ['photo' => 'places/monte-nieddu.jpg', 'maps' => 'https://www.google.com/maps/search/?api=1&query=Monte+Nieddu+San+Teodoro+Sardinia'],
    ['photo' => 'places/la-cinta.jpg', 'maps' => 'https://www.google.com/maps/search/?api=1&query=Spiaggia+La+Cinta+San+Teodoro'],
];
$galleryPreview = 6;
$gallery = [
    'gallery/bedroom.jpg', 'gallery/bathroom.jpg', 'gallery/hallway.jpg',
    'hero/sea-view.jpg', 'gallery/ext-olive.jpg', 'gallery/g2.jpg',
    'gallery/bedroom-lamp.jpg', 'gallery/g7.jpg', 'gallery/g10.jpg', 'gallery/g3.jpg',
    'gallery/g8.jpg', 'gallery/g11.jpg', 'gallery/g28.jpg', 'gallery/g31.jpg',
    'gallery/g35.jpg', 'gallery/ext-garden.jpg', 'gallery/g1.jpg', 'gallery/g39.jpg',
    'location/beach.jpg', 'gallery/g15.jpg', 'gallery/g5.jpg', 'gallery/ext-6.jpg',
];
$restaurants = [
    ['La Shardana', '150 m'],
    ['La Volpe', '950 m'],
    ['Pizzeria da Paolo', '1 km'],
    ['Ristorante Lu Nibaru', '—'],
];
$beaches = [
    ['Spiaggia Punta Sant’Anna', '250 m'],
    ['Spiaggia Capannizza', '500 m'],
    ['Spiaggia di Porto Ainu', '1 km'],
    ['Baia Sant’Anna', '2,5 km'],
    ['Plage de Budoni', '2,5 km'],
    ['Spiaggia e pineta Salmaghe', '3,3 km'],
];
$minRate = 9999;
foreach ($seasons as $s) {
    if (!(int) $s['is_closed'] && (float) $s['nightly_rate'] > 0) {
        $minRate = min($minRate, (float) $s['nightly_rate']);
    }
}
?>
<section class="hero" data-hero-slider>
    <div class="hero-slides" aria-hidden="true">
        <?php foreach ($heroSlides as $i => $slide): ?>
            <figure class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>">
                <img src="<?= e(asset('img/' . $slide['src'])) ?>" alt="<?= e($slide['alt']) ?>">
            </figure>
        <?php endforeach; ?>
    </div>
    <div class="hero-veil"></div>
    <div class="hero-content wrap">
        <p class="kicker gold"><?= e(t('hero.kicker')) ?></p>
        <h1><?= e(t('hero.title')) ?></h1>
        <p class="hero-sub"><?= e(t('hero.subtitle')) ?></p>
        <p class="hero-lead"><?= e(t('hero.lead')) ?></p>
        <p class="hero-chips"><?= e(t('hero.chips')) ?></p>
        <a class="btn btn-gold" href="<?= e(url_for('reserver')) ?>"><?= e(t('hero.cta')) ?></a>
        <div class="hero-nav">
            <button type="button" class="hero-arrow" data-hero-prev aria-label="Previous">‹</button>
            <div class="hero-dots">
                <?php foreach ($heroSlides as $i => $slide): ?>
                    <button type="button" data-hero-dot="<?= $i ?>" class="<?= $i === 0 ? 'is-active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
            <button type="button" class="hero-arrow" data-hero-next aria-label="Next">›</button>
        </div>
    </div>
    <a class="hero-scroll" href="#decouvrir">
        <span><?= e(t('hero.scroll')) ?></span>
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
            <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>
</section>

<section class="facts reveal" id="decouvrir">
    <div class="wrap facts-grid">
        <?php foreach ($factIcons as [$icon, $key]): ?>
            <div class="fact">
                <img src="<?= e(asset('img/icons/' . $icon . '.svg')) ?>" alt="">
                <strong><?= e(t($key)) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section reveal" id="villa">
    <div class="wrap villa-block">
        <div class="villa-copy">
            <p class="eyebrow"><?= e(t('villa.eyebrow')) ?></p>
            <h2><?= e(t('villa.title')) ?></h2>
            <p class="lead"><?= e(t('villa.text')) ?></p>
        </div>
        <div class="villa-photos">
            <img src="<?= e(asset('img/gallery/bedroom.jpg')) ?>" alt="Chambre double de la villa">
            <img src="<?= e(asset('img/gallery/bathroom.jpg')) ?>" alt="Salle de bain de la villa">
        </div>
    </div>
    <div class="wrap points">
        <?php foreach ($points as $p): ?>
            <article>
                <h3><?= e($p['title']) ?></h3>
                <p><?= e($p['text']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section sand reveal" id="services">
    <div class="wrap">
        <p class="eyebrow"><?= e(t('amenities.title')) ?></p>
        <ul class="amenity-grid">
            <?php foreach ($amenityOrder as $icon): ?>
                <li>
                    <img src="<?= e(asset('img/icons/' . $icon . '.svg')) ?>" alt="">
                    <span><?= e($amenities[$icon] ?? $icon) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section reveal" id="galerie">
    <div class="wrap" data-gallery>
        <p class="eyebrow"><?= e(t('gallery.eyebrow')) ?></p>
        <h2><?= e(t('gallery.title')) ?></h2>
        <p class="lead"><?= e(t('gallery.text')) ?></p>
        <div class="gallery-grid">
            <?php foreach (array_slice($gallery, 0, $galleryPreview) as $img): ?>
                <button type="button" class="masonry-item" data-lightbox="<?= e(asset('img/' . $img)) ?>">
                    <img src="<?= e(asset('img/' . $img)) ?>" alt="Villa Mariolu Beach" loading="lazy">
                </button>
            <?php endforeach; ?>
        </div>
        <div class="gallery-more">
            <div class="gallery-more-inner">
                <div class="gallery-grid">
                    <?php foreach (array_slice($gallery, $galleryPreview) as $img): ?>
                        <button type="button" class="masonry-item" data-lightbox="<?= e(asset('img/' . $img)) ?>">
                            <img src="<?= e(asset('img/' . $img)) ?>" alt="Villa Mariolu Beach" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <p class="center gallery-toggle-wrap">
            <button type="button" class="btn btn-outline" data-gallery-toggle data-more="<?= e(t('gallery.more')) ?>" data-less="<?= e(t('gallery.less')) ?>" aria-expanded="false">
                <?= e(t('gallery.more')) ?>
            </button>
        </p>
    </div>
</section>

<section class="hosts reveal" id="hosts">
    <div class="wrap hosts-inner">
        <p class="eyebrow gold"><?= e(t('hosts.eyebrow')) ?></p>
        <h2><?= e(t('hosts.title')) ?></h2>
        <p><?= e(t('hosts.p1')) ?></p>
        <p><?= e(t('hosts.p2')) ?></p>
        <p><?= e(t('hosts.p3')) ?></p>
        <p><?= e(t('hosts.p4')) ?></p>
        <p class="sign"><?= e(t('hosts.sign')) ?></p>
    </div>
</section>

<section class="section reveal" id="budoni">
    <div class="wrap">
        <p class="eyebrow"><?= e(t('location.eyebrow')) ?></p>
        <h2><?= e(t('location.title')) ?></h2>
        <div class="split">
            <div>
                <p><?= e(t('location.p1')) ?></p>
                <p><?= e(t('location.p2')) ?></p>
                <p><?= e(t('location.p3')) ?></p>
                <a class="btn btn-outline" href="<?= e(setting('maps')) ?>" target="_blank" rel="noopener"><?= e(t('location.map_cta')) ?></a>
            </div>
            <a class="location-map" href="<?= e(setting('maps')) ?>" target="_blank" rel="noopener">
                <img class="rounded" src="<?= e(asset('img/location/map.jpg')) ?>" alt="Plan de situation : Villa Mariolu Beach à Budoni, près de la plage Punta Sant’Anna">
            </a>
        </div>
        <div class="nearby">
            <div>
                <h3><?= e(t('location.restaurants')) ?></h3>
                <ul>
                    <?php foreach ($restaurants as [$n, $d]): ?>
                        <li><span><?= e($n) ?></span><span><?= e($d) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h3><?= e(t('location.beaches')) ?></h3>
                <ul>
                    <?php foreach ($beaches as [$n, $d]): ?>
                        <li><span><?= e($n) ?></span><span><?= e($d) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="places">
            <h3><?= e(t('location.discover')) ?></h3>
            <div class="places-grid">
                <?php foreach ($places as $i => $pl): ?>
                    <?php $card = $placeCards[$i] ?? null; ?>
                    <article class="place-card">
                        <?php if ($card): ?>
                            <img src="<?= e(asset('img/' . $card['photo'])) ?>" alt="<?= e($pl['title']) ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="place-card-body">
                            <h4><?= e($pl['title']) ?></h4>
                            <p><?= e($pl['text']) ?></p>
                            <?php if ($card): ?>
                                <a class="place-map" href="<?= e($card['maps']) ?>" target="_blank" rel="noopener">
                                    <?= e(t('location.map_cta')) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="reviews reveal" id="avis">
    <div class="wrap reviews-inner">
        <p class="eyebrow"><?= e(t('reviews.eyebrow')) ?></p>
        <h2><?= e(t('reviews.title')) ?></h2>
        <p class="lead"><?= e(t('reviews.text')) ?></p>
        <p class="rating">★★★★★ <?= e(t('reviews.rating')) ?></p>
        <p class="badge-line"><?= e(t('reviews.badge')) ?></p>
        <a class="btn btn-gold" href="<?= e(setting('google_reviews')) ?>" target="_blank" rel="noopener"><?= e(t('reviews.cta')) ?></a>
    </div>
</section>

<section class="section sand reveal" id="confiance">
    <div class="wrap section-center">
        <p class="eyebrow"><?= e(t('trust.eyebrow')) ?></p>
        <h2><?= e(t('trust.title')) ?></h2>
        <p class="lead"><?= e(t('trust.text')) ?></p>
        <ul class="check-list trust-list">
            <?php foreach ($trust as $item): ?>
                <li><?= e($item) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="cin">CIN <?= e(setting('cin')) ?> · IUN <?= e(setting('iun')) ?></p>
    </div>
</section>

<section class="section reveal" id="tarifs">
    <div class="wrap">
        <p class="eyebrow"><?= e(t('prices.eyebrow')) ?></p>
        <h2><?= e(t('prices.title')) ?></h2>
        <p class="lead"><?= e(t('prices.text')) ?></p>
        <div class="price-extras">
            <div class="price-frame price-side">
                <h3><?= e(t('prices.included_title')) ?></h3>
                <ul class="included-list">
                    <?php foreach (t_arr('prices.included') as $item): ?>
                        <li><?= e($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="price-frame price-side">
                <h3><?= e(t('prices.excluded_title')) ?></h3>
                <ul class="price-terms">
                    <li><?= e(t('prices.caution')) ?></li>
                    <li><?= e(t('prices.cleaning')) ?></li>
                    <li><?= e(t('prices.deposit')) ?></li>
                    <li><?= e(t('prices.balance')) ?></li>
                    <li><?= e(t('prices.min')) ?></li>
                </ul>
            </div>
        </div>
        <div class="prices-layout">
            <div class="price-frame">
                <table class="rate-table">
                    <thead>
                        <tr>
                            <th><?= e(t('prices.period')) ?></th>
                            <th class="num"><?= e(t('prices.night')) ?></th>
                            <th class="num"><?= e(t('prices.col_6')) ?></th>
                            <th class="num"><?= e(t('prices.col_10')) ?></th>
                            <th class="num"><?= e(t('prices.col_14')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $off6 = Pricing::discountPercentForNights(6);
                        $off10 = Pricing::discountPercentForNights(10);
                        $off14 = Pricing::discountPercentForNights(14);
                        ?>
                        <?php foreach ($seasons as $s): ?>
                            <?php $closed = (int) $s['is_closed']; $rate = (float) $s['nightly_rate']; ?>
                            <tr class="<?= $closed ? 'is-closed' : '' ?>">
                                <td><?= e($s['label']) ?></td>
                                <?php if ($closed): ?>
                                    <td class="num" colspan="4"><?= e(t('prices.closed')) ?></td>
                                <?php else: ?>
                                    <td class="num"><?= e(money($rate)) ?></td>
                                    <td class="num"><?= e(money(round($rate * (1 - $off6 / 100)))) ?></td>
                                    <td class="num"><?= e(money(round($rate * (1 - $off10 / 100)))) ?></td>
                                    <td class="num"><?= e(money(round($rate * (1 - $off14 / 100)))) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <aside class="price-frame price-side">
                <h3><?= e(t('prices.discounts_title')) ?></h3>
                <ul class="discount-list">
                    <?php foreach (['d1', 'd2', 'd3'] as $dk): ?>
                        <?php
                        $label = t('prices.' . $dk);
                        if ($label === '' || $label === 'prices.' . $dk) {
                            continue;
                        }
                        $parts = preg_split('/\s*→\s*/u', $label, 2);
                        ?>
                        <li>
                            <span><?= e($parts[0]) ?></span>
                            <strong><?= e($parts[1] ?? '') ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php require ROOT . '/views/partials/stay-rules.php'; ?>
            </aside>
        </div>
    </div>
</section>

<section class="section sand reveal" id="disponibilites">
    <div class="wrap">
        <p class="eyebrow"><?= e(t('calendar.eyebrow')) ?></p>
        <h2><?= e(t('calendar.title')) ?></h2>
        <p class="lead"><?= e(t('calendar.select')) ?></p>
        <div class="calendar" data-calendar data-mode="range"></div>
        <div class="legend">
            <span><i class="dot avail"></i> <?= e(t('calendar.available')) ?></span>
            <span><i class="dot booked"></i> <?= e(t('calendar.booked')) ?></span>
            <span><i class="dot pending"></i> <?= e(t('calendar.pending')) ?></span>
            <span><i class="dot no"></i> <?= e(t('calendar.unavailable')) ?></span>
            <span><i class="dot turnover"></i> <?= e(t('calendar.turnover')) ?></span>
        </div>
        <p class="center"><a class="btn btn-terracotta" href="<?= e(url_for('reserver')) ?>"><?= e(t('hero.cta')) ?></a></p>
    </div>
</section>

<section class="section reveal" id="comment">
    <div class="wrap">
        <p class="eyebrow"><?= e(t('howto.eyebrow')) ?></p>
        <h2><?= e(t('howto.title')) ?></h2>
        <ol class="steps">
            <?php foreach ($steps as $step): ?>
                <li>
                    <span class="step-n"><?= e($step['n']) ?></span>
                    <h3><?= e($step['title']) ?></h3>
                    <p><?= e($step['text']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section class="section sand reveal" id="paiement">
    <div class="wrap split">
        <div>
            <p class="eyebrow"><?= e(t('payment.eyebrow')) ?></p>
            <h2><?= e(t('payment.title')) ?></h2>
            <p><?= e(t('payment.card')) ?></p>
            <p><?= e(t('payment.transfer')) ?></p>
            <p class="muted"><?= e(t('payment.note')) ?></p>
        </div>
        <div class="pay-cards">
            <div class="pay-card">Visa / Mastercard</div>
            <div class="pay-card">Virement</div>
        </div>
    </div>
</section>

<section class="section reveal" id="faq">
    <div class="wrap narrow">
        <p class="eyebrow"><?= e(t('faq.eyebrow')) ?></p>
        <h2><?= e(t('faq.title')) ?></h2>
        <div class="faq">
            <?php foreach ($faq as $item): ?>
                <details>
                    <summary><?= e($item['q']) ?></summary>
                    <p><?= e($item['a']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="wrap">
        <h2><?= e(t('book.title')) ?></h2>
        <p><?= e(t('prices.from')) ?> <?= e(money($minRate)) ?> / <?= e(t('prices.night')) ?></p>
        <a class="btn btn-gold" href="<?= e(url_for('reserver')) ?>"><?= e(t('nav.book')) ?></a>
    </div>
</section>
