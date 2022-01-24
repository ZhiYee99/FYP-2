<?php

defined('tpwatchshop') or exit;
if (isset($_POST['product_id'], $_POST['quantity']) && is_numeric($_POST['product_id']) && is_numeric($_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = abs((int)$_POST['quantity']);
    $options = '';
    $options_price = 0.00;
    foreach ($_POST as $m => $n) {
        if (strpos($m, 'option-') !== false) {
            $options .= str_replace('option-', '', $m) . '-' . $n . ',';
            $statement = $pdo->prepare('SELECT * FROM products_options WHERE title = ? AND name = ? AND product_id = ?');
            $statement->execute([ str_replace('option-', '', $m), $n, $product_id ]);
            $option = $statement->fetch(PDO::FETCH_ASSOC);
            $options_price += $option['price'];
        }
    }
    $options = rtrim($options, ',');
    $statement = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $statement->execute([ $_POST['product_id'] ]);
    $product = $statement->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $quantity > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $cart_product = get_cart_product($product_id, $options);
        if ($cart_product) {
            // Product exists, update quanity
            $cart_product['quantity'] += $quantity;
        } else {
            // Product not in cart, add product
            $_SESSION['cart'][] = [
                'id' => $product_id,
                'quantity' => $quantity,
                'options' => $options,
                'options_price' => $options_price,
                'shipping_price' => 0.00
            ];
        }
    }
    header('location: ' . url('index.php?page=cart'));
    exit;
}
// delete product
if (isset($_GET['remove']) && is_numeric($_GET['remove']) && isset($_SESSION['cart']) && isset($_SESSION['cart'][$_GET['remove']])) {
    array_splice($_SESSION['cart'], $_GET['remove'], 1);
    header('location: ' . url('index.php?page=cart'));
    exit;
}
// Empty the cart (remove all)
if (isset($_POST['emptycart']) && isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
    header('location: ' . url('index.php?page=cart'));
    exit;
}
// Update product quantities
if ((isset($_POST['update']) || isset($_POST['checkout'])) && isset($_SESSION['cart'])) {
    foreach ($_POST as $m => $n) {
        if (strpos($m, 'quantity') !== false && is_numeric($n)) {
            $id = str_replace('quantity-', '', $m);
            $quantity = abs((int)$n);
            if (is_numeric($id) && isset($_SESSION['cart'][$id]) && $quantity > 0) {
                $_SESSION['cart'][$id]['quantity'] = $quantity;
            }
        }
    }
    // Update shipping method
    if (isset($_POST['shipping_method'])) {
        $_SESSION['shipping_method'] = $_POST['shipping_method'];
    }
    // Update discount code
    if (isset($_POST['discount_code']) && !empty($_POST['discount_code'])) {
        $_SESSION['discount'] = $_POST['discount_code'];
    } else if (isset($_POST['discount_code']) && empty($_POST['discount_code']) && isset($_SESSION['discount'])) {
        unset($_SESSION['discount']);
    }
    // Send order
    if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
        header('Location: ' . url('index.php?page=checkout'));
        exit;
    }
    header('location: ' . url('index.php?page=cart'));
    exit;
}
 

$products_in_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0.00;
$discounttotal = 0.00;
$shippingtotal = 0.00;
$selected_shipping_method = isset($_SESSION['shipping_method']) ? $_SESSION['shipping_method'] : null;
$shipping_available = false;
date_default_timezone_set("Asia/Kuala_Lumpur");
$current_date = strtotime((new DateTime())->format('Y-m-d H:i:s'));

// If there are products in cart
if ($products_in_cart) {
    $array_to_question_marks = implode(',', array_fill(0, count($products_in_cart), '?'));
    $statement = $pdo->prepare('SELECT p.id, pc.category_id, p.* FROM products p LEFT JOIN products_categories pc ON p.id = pc.product_id LEFT JOIN categories c ON c.id = pc.category_id WHERE p.id IN (' . $array_to_question_marks . ') GROUP BY p.id');
    $statement->execute(array_column($products_in_cart, 'id'));
    $products = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    //get discount code
    if (isset($_SESSION['discount'])) {
        $statement = $pdo->prepare('SELECT * FROM discounts WHERE discount_code = ?');
        $statement->execute([ $_SESSION['discount'] ]);
        $discount = $statement->fetch(PDO::FETCH_ASSOC);
    }
    // get shipping methods
    $statement = $pdo->query('SELECT * FROM shipping');
    $shipping_methods = $statement->fetchAll(PDO::FETCH_ASSOC);
    $selected_shipping_method = $selected_shipping_method == null && $shipping_methods ? $shipping_methods[0]['name'] : $selected_shipping_method;
    
    //calculate total price
    foreach ($products_in_cart as &$cart_product) {
        foreach ($products as $product) {
            if ($cart_product['id'] == $product['id']) {
                $cart_product['meta'] = $product;
                
                $product_price = $cart_product['options_price'] > 0 ? (float)$cart_product['options_price'] : (float)$product['price'];
                $subtotal += $product_price * (int)$cart_product['quantity'];
                
                foreach ($shipping_methods as $shipping_method) {
                    if ($shipping_method['name'] == $selected_shipping_method && $product_price >= $shipping_method['price_from'] && $product_price <= $shipping_method['price_to'] && $product['weight'] >= $shipping_method['weight_from'] && $product['weight'] <= $shipping_method['weight_to']) {
                        $shippingtotal += (float)$shipping_method['price'] * (int)$cart_product['quantity'];
                        $shipping_available = true;
                    } else if ($product_price >= $shipping_method['price_from'] && $product_price <= $shipping_method['price_to'] && $product['weight'] >= $shipping_method['weight_from'] && $product['weight'] <= $shipping_method['weight_to']) {
                        $shipping_available = true;
                    }
                }
                
                if (isset($discount) && $discount && $current_date >= strtotime($discount['start_date']) && $current_date <= strtotime($discount['end_date'])) {
                    if ((empty($discount['category_ids']) && empty($discount['product_ids'])) || in_array($product['id'], explode(',', $discount['product_ids'])) || (!empty($product['category_id']) && in_array($product['category_id'], explode(',', $discount['category_ids'])))) {
                        $cart_product['discounted'] = true;
                    }
                }
            }
        }
    }


    // Number of discounted products
    $num_discounted_products = count(array_column($products_in_cart, 'discounted'));
    foreach ($products_in_cart as &$cart_product) {
        if (isset($cart_product['discounted']) && $cart_product['discounted']) {
            if ($cart_product['options_price'] > 0) {
                $price = &$cart_product['options_price'];
            } else {
                $price = &$cart_product['meta']['price'];
            }
            if ($discount['discount_type'] == 'Percentage') {
                $d = round((float)$price * ((float)$discount['discount_value']/100), 2) * (int)$cart_product['quantity'];
                $discounttotal += $d;
            }
            if ($discount['discount_type'] == 'Fixed') {
                $d = round((float)$discount['discount_value'] / $num_discounted_products, 2);
                $discounttotal += $d;
            }
        }
    }
}
?>
<?=header_template('Shopping Cart')?>

<div class="cart content-wrapper">

    <h1>Shopping Cart</h1>

    <form action="" method="post">
        <table>
            <thead>
                <tr>
                    <td colspan="2">Product</td>
                    <td></td>
                    <td class="rhide">Price</td>
                    <td>Quantity</td>
                    <td>Total</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products_in_cart)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No product in your Shopping Cart.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($products_in_cart as $num => $product): ?>
                <tr>
                    <td class="img">
                        <?php if (!empty($product['meta']['img']) && file_exists('imgs/' . $product['meta']['img'])): ?>
                        <a href="<?=url('index.php?page=product&id=' . $product['id'])?>">
                            <img src="<?=base_url?>imgs/<?=$product['meta']['img']?>" width="50" height="50" alt="<?=$product['meta']['name']?>">
                        </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?=url('index.php?page=product&id=' . $product['id'])?>"><?=$product['meta']['name']?></a>
                        <br>
                        <a href="<?=url('index.php?page=cart&remove=' . $num)?>" class="remove">Remove</a>
                    </td>
                    <td class="price">
                        <?=$product['options']?>
                        <input type="hidden" name="options" value="<?=$product['options']?>">
                    </td>
                    <?php if ($product['options_price'] > 0): ?>
                    <td class="price rhide"><?=currency_code?><?=number_format($product['options_price'],2)?></td>
                    <?php else: ?>
                    <td class="price rhide"><?=currency_code?><?=number_format($product['meta']['price'],2)?></td>
                    <?php endif; ?>
                    <td class="quantity">
                        <input type="number" class="ajax-update" name="quantity-<?=$num?>" value="<?=$product['quantity']?>" min="1" <?php if ($product['meta']['quantity'] != -1): ?>max="<?=$product['meta']['quantity']?>"<?php endif; ?> placeholder="Quantity" required>
                    </td>
                    <?php if ($product['options_price'] > 0): ?>
                    <td class="price product-total"><?=currency_code?><?=number_format($product['options_price'] * $product['quantity'],2)?></td>
                    <?php else: ?>
                    <td class="price product-total"><?=currency_code?><?=number_format($product['meta']['price'] * $product['quantity'],2)?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (isset($shipping_methods) && $shipping_available): ?>
        <div class="shipping-methods">
            <h2>Shipping Method</h2>
            <?php foreach(array_unique(array_column($shipping_methods, 'name')) as $m => $n): ?>
            <div class="shipping-method">
                <input type="radio" class="ajax-update" id="sm<?=$m?>" name="shipping_method" value="<?=$n?>"<?=$selected_shipping_method==$n?' checked':''?>>
                <label for="sm<?=$m?>"><?=$n?></label>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="discount-code">
            <input type="text" class="ajax-update" name="discount_code" placeholder="Discount Code" value="<?=isset($_SESSION['discount']) ? $_SESSION['discount'] : ''?>">
            <span class="result">
                <?php if (isset($_SESSION['discount'], $discount) && !$discount): ?>
                Incorrect discount code!
                <?php elseif (isset($_SESSION['discount'], $discount) && $current_date > strtotime($discount['end_date'])): ?>
                Discount code expired! <?=$current_date?>
                <?php endif; ?>
            </span>
        </div>

        <div class="subtotal">
            <span class="text">Subtotal</span>
            <span class="price"><?=currency_code?><?=number_format($subtotal,2)?></span>
        </div>

        <div class="shipping">
            <span class="text">Shipping</span>
            <span class="price"><?=currency_code?><?=number_format($shippingtotal,2)?></span>
        </div>

        <div class="discount">
            <?php if ($discounttotal > 0): ?>
            <span class="text">Discount</span>
            <span class="price">-<?=currency_code?><?=number_format($discounttotal,2)?></span>
            <?php endif; ?>
        </div>

        <div class="total">
            <span class="text">Total</span>
            <span class="price"><?=currency_code?><?=number_format(($subtotal-round($discounttotal,2))+$shippingtotal,2)?></span>
        </div>

        <div class="buttons">
            <input type="submit" value="Update" name="update">
            <input type="submit" value="Empty Cart" name="emptycart">
            <input type="submit" value="Checkout" name="checkout">
        </div>

    </form>

</div>

<?=footer_template()?>
