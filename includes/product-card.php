<?php
/**
 * includes/product-card.php
 *
 * Defines render_product_card(), the server-rendered equivalent of the
 * old JS renderProductCard() in assets/js/common.js. Produces the same
 * markup/classes so no CSS changes are needed.
 *
 * The Quick View / Wishlist / Add to Cart buttons still call the
 * existing client-side JS functions (openQuickView, toggleWishlist,
 * addToCart) - those keep working as long as the page also emits a
 * PRODUCTS JS array (see index.php) for them to look up by id.
 *
 * Wishlist state lives in localStorage, so it can't be known
 * server-side - every card renders as "not wishlisted" initially, and
 * assets/js/common.js's syncWishlistUI() corrects the heart icons
 * right after the page loads.
 *
 * Expects each $product array to have an extra 'category_name' key
 * (the caller looks this up from the categories list, since the raw
 * product row only has category_id).
 */

declare(strict_types=1);

if (!function_exists('format_pkr')) {
    function format_pkr(float $amount): string
    {
        return 'Rs. ' . number_format(round($amount));
    }
}

if (!function_exists('render_stars')) {
    function render_stars(float $rating): string
    {
        $html = '<span class="rating-stars">';
        for ($i = 1; $i <= 5; $i++) {
            if ($rating >= $i) {
                $html .= '<i class="bi bi-star-fill"></i>';
            } elseif ($rating >= $i - 0.5) {
                $html .= '<i class="bi bi-star-half"></i>';
            } else {
                $html .= '<i class="bi bi-star"></i>';
            }
        }
        return $html . '</span>';
    }
}

if (!function_exists('render_product_card')) {
    function render_product_card(array $p, bool $listView = false): string
    {
        $id = (int) $p['id'];
        $price = (float) $p['price'];
        $oldPrice = isset($p['old_price']) && $p['old_price'] !== null ? (float) $p['old_price'] : null;
        $discount = $oldPrice ? (int) round((1 - $price / $oldPrice) * 100) : 0;
        $name = htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars($p['category_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $image = htmlspecialchars($p['images'][0] ?? '', ENT_QUOTES, 'UTF-8');
        $badge = $p['badge'] ? htmlspecialchars($p['badge'], ENT_QUOTES, 'UTF-8') : null;
        $description = htmlspecialchars(mb_substr((string) ($p['description'] ?? ''), 0, 100), ENT_QUOTES, 'UTF-8');

        ob_start();
        ?>
        <div class="product-card <?= $listView ? 'd-flex' : '' ?>" data-id="<?= $id ?>">
          <div class="product-img-wrap">
            <?php if ($badge): ?><span class="product-badge"><?= $badge ?></span><?php endif; ?>
            <?php if ($discount > 0): ?><span class="product-discount">-<?= $discount ?>%</span><?php endif; ?>
            <a href="product.html?id=<?= $id ?>"><img src="<?= $image ?>" alt="<?= $name ?>" loading="lazy"></a>
            <div class="product-actions">
              <button class="product-action-btn" onclick="openQuickView(<?= $id ?>)" title="Quick View"><i class="bi bi-eye"></i></button>
              <button class="product-action-btn wish-btn" onclick="toggleWishlist(<?= $id ?>); refreshWishBtn(this, <?= $id ?>)" title="Wishlist"><i class="bi bi-heart"></i></button>
              <button class="product-action-btn" onclick="addToCart(<?= $id ?>)" title="Add to Cart"><i class="bi bi-cart-plus"></i></button>
            </div>
          </div>
          <div class="product-body">
            <span class="product-cat-tag"><?= $category ?></span>
            <h6 class="product-name"><a href="product.html?id=<?= $id ?>"><?= $name ?></a></h6>
            <div class="mb-2"><?= render_stars((float) $p['rating']) ?><span class="rating-text">(<?= (int) $p['reviews_count'] ?>)</span></div>
            <div>
              <span class="product-price"><?= format_pkr($price) ?></span>
              <?php if ($oldPrice): ?><span class="product-old-price"><?= format_pkr($oldPrice) ?></span><?php endif; ?>
            </div>
            <?php if ($listView): ?><p class="text-muted-2 fs-7 mt-2 mb-0"><?= $description ?>...</p><?php endif; ?>
          </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
