<?php

defined('tpwatchshop') or exit;
if (isset($_GET['query']) && $_GET['query'] != '') {
    $search_query = htmlspecialchars($_GET['query'], ENT_QUOTES, 'UTF-8');
    $statement = $pdo->prepare('SELECT * FROM products WHERE name LIKE ? ORDER BY date_added DESC');
    $statement->execute(['%' . $search_query . '%']);
    $products = $statement->fetchAll(PDO::FETCH_ASSOC);
    $total_products = count($products);
} else {
    $error = 'No search query was specified!';
}
?>
<?=header_template('Search')?>

<?php if ($error): ?>

<p class="content-wrapper error"><?=$error?></p>

<?php else: ?>

<div class="products content-wrapper">

    <h1>Search Results for "<?=$search_query?>"</h1>

    <p><?=$total_products?> Products</p>

    <div class="products-wrapper">
        <?php foreach ($products as $product): ?>
        <a href="<?=url('index.php?page=product&id=' . ($product['url_structure'] ? $product['url_structure']  : $product['id']))?>" class="product">
            <?php if (!empty($product['img']) && file_exists('imgs/' . $product['img'])): ?>
            <img src="<?=base_url?>imgs/<?=$product['img']?>" width="200" height="200" alt="<?=$product['name']?>">
            <?php endif; ?>
            <span class="name"><?=$product['name']?></span>
            <span class="price">
                <?=currency_code?><?=number_format($product['price'],2)?>
                <?php if ($product['rrp'] > 0): ?>
                <span class="rrp"><?=currency_code?><?=number_format($product['rrp'],2)?></span>
                <?php endif; ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>

</div>

<?php endif; ?>

<?=footer_template()?>
