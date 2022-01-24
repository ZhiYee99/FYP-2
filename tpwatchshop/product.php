<?php

defined('tpwatchshop') or exit;
if (isset($_GET['id'])) {
    $statement = $pdo->prepare('SELECT * FROM products WHERE id = ? OR url_structure = ?');
    $statement->execute([ $_GET['id'], $_GET['id'] ]);
    $product = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        http_response_code(404);
        exit('Product does not exist!');
    }
    
    $statement = $pdo->prepare('SELECT * FROM products_images WHERE product_id = ?  ');
    $statement->execute([ $product['id'] ]);
    $product_imgs = $statement->fetchAll(PDO::FETCH_ASSOC);
    $statement = $pdo->prepare('SELECT title, GROUP_CONCAT(name) AS options, GROUP_CONCAT(price) AS prices FROM products_options WHERE product_id = ? GROUP BY title');
    $statement->execute([ $product['id'] ]);
    $product_options = $statement->fetchAll(PDO::FETCH_ASSOC);
    $meta = '
        <meta property="og:url" content="' . url('index.php?page=product&id=' . ($product['url_structure'] ? $product['url_structure']  : $product['id'])) . '">
        <meta property="og:title" content="' . $product['name'] . '">
    ';
    if (!empty($product['img']) && file_exists('imgs/' . $product['img'])) {
        $meta .= '<meta property="og:image" content="' . base_url . 'imgs/' . $product['img'] . '">';
    }
} else {
    http_response_code(404);
    exit('Product does not exist!');
}
?>
<?=header_template($product['name'], $meta)?>

<?php if ($error): ?>

<p class="content-wrapper error"><?=$error?></p>

<?php else: ?>

<div class="product content-wrapper">

    <div class="product-imgs">

        <?php if (!empty($product['img']) && file_exists('imgs/' . $product['img'])): ?>
        <img class="product-img-large" src="<?=base_url?>imgs/<?=$product['img']?>" width="500" height="500" alt="<?=$product['name']?>">
        <?php endif; ?>

        <div class="product-small-imgs">
            <?php foreach ($product_imgs as $product_img): ?>
            <img class="product-img-small<?=$product_img['img']==$product['img']?' selected':''?>" src="<?=base_url?>imgs/<?=$product_img['img']?>" width="150" height="150" alt="<?=$product['name']?>">
            <?php endforeach; ?>
        </div>

    </div>

    <div class="product-wrapper">

        <h1 class="name"><?=$product['name']?></h1>

        <span class="price">
            <?=currency_code?><?=number_format($product['price'],2)?>
            <?php if ($product['rrp'] > 0): ?>
            <span class="rrp"><?=currency_code?><?=number_format($product['rrp'],2)?></span>
            <?php endif; ?>
        </span>

        <form id="product-form" action="<?=url('index.php?page=cart')?>" method="post">
            <?php foreach ($product_options as $option): ?>
            <select name="option-<?=$option['title']?>" required>
                <option value="" selected disabled style="display:none"><?=$option['title']?></option>
                <?php
                $options_names = explode(',', $option['options']);
                $options_prices = explode(',', $option['prices']);
                ?> 
                <?php foreach ($options_names as $m => $name): ?>
                <option value="<?=$name?>" data-price="<?=$options_prices[$m]?>"><?=$name?></option>
                <?php endforeach; ?>
            </select>
            <?php endforeach; ?>

            <input type="number" name="quantity" value="1" min="1" <?php if ($product['quantity'] != -1): ?>max="<?=$product['quantity']?>"<?php endif; ?> placeholder="Quantity" required>
            <input type="hidden" name="product_id" value="<?=$product['id']?>">
            <?php if ($product['quantity'] == 0): ?>
            <input type="submit" value="Out of Stock" disabled>
            <?php else: ?>
            <input type="submit" value="Add To Cart">
            <?php endif; ?>    
            
        </form>


    

        <div class="description">
            <?=$product['description']?>
            <h4>Features</h4>
            <?=$product['features']?>
        </div>

    </div>

</div>

<?php endif; ?>

<?=footer_template()?>
