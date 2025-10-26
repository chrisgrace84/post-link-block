<?php
$attributes = $attributes ?? null;
$selectedPost = $attributes['selectedPost'] ?? null;
$title = $selectedPost['title']['rendered'] ?? null;
$permalink = $selectedPost['link'] ?? null;
?>

<?php if (!!$title && !!$permalink): ?>
    <p class="dmg-read-more">
        <a href="<?= $permalink ?>">
            <?= __('Read More: ') ?> <?= $title ?>
        </a>
    </p>
<?php endif ?>
