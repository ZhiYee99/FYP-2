<?php

defined('tpwatchshop') or exit;
// get latest products
$statement = $pdo->prepare('SELECT * FROM products WHERE inactive = 0 ORDER BY date_added DESC LIMIT 4');
$statement->execute();
$recently_added_products = $statement->fetchAll(PDO::FETCH_ASSOC);
?>
<?=header_template('Home')?>

<div class="featured" style="background-image:url(<?=banner?>)">
    <h2>TP Watch Shop</h2>
    <p>Official branded watch store with viable range of price.</p>
</div>


<div class="recentlyadded content-wrapper">
    <h2>Recently Added Products</h2>
    <div class="products">
        <?php foreach ($recently_added_products as $product): ?>
        <a href="<?=url('index.php?page=product&id=' . ($product['url_structure'] ? $product['url_structure']  : $product['id']))?>" class="product">
            <?php if (!empty($product['img']) && file_exists('imgs/' . $product['img'])): ?>
            <img src="imgs/<?=$product['img']?>" width="200" height="200" alt="<?=$product['name']?>">
            <?php endif; ?>
            <span class="name"><?=$product['name']?></span>
            <span class="price">
                <?=currency_code?><?=number_format($product['price'],2)?>
                <?php if ($product['rrp'] > 0): ?>
                <span class="rrp"><?=currency_code?><?=number_format($product['rrp'],2)?></span>
                <?php endif; ?>
            </span><br>
            <span class='quantity' style="font-size:12px;">
                <?php if ($product['quantity'] == 0): ?>Out of Stock
                <?php else:?>Left:  <?=$product['quantity'];?>     
                <?php endif; ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?=footer_template()?>
