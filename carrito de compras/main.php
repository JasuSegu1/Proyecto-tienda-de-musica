<?php

include 'connect.php';

session_start();

function e($string){
   return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function valid_id(string $id, int $len = 20): bool {
   return preg_match('/^[a-zA-Z0-9]{'.$len.'}$/', $id) === 1;
}

if(!isset($_SESSION['user_id']) || !is_string($_SESSION['user_id']) || !valid_id($_SESSION['user_id'])){
   $_SESSION['user_id'] = create_unique_id();
}

$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){

if(isset($_POST['add_to_cart'])){

   $cart_id = create_unique_id();
   $product_id = trim($_POST['product_id'] ?? '');
   $quantity = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);
   $color = trim($_POST['color'] ?? '');
   $size = trim($_POST['size'] ?? '');

   $allowed_colors = ['red', 'green', 'blue'];
   $allowed_size = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

   if(!valid_id($product_id)){
      $error_msg[] = 'Invalid Product ID';
   }

   if($quantity === false || $quantity < 1 || $quantity > 10){
      $error_msg[] = 'Invalid quantity. Must be between 1 and 10';
   }

   if(!in_array($color, $allowed_colors, true)){
      $error_msg[] = 'Invalid color selected';
   }

   if(!in_array($size, $allowed_size, true)){
      $error_msg[] = 'Invalid size selected';
   }

   if(empty($error_msg)){

      $check_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
      $check_product->execute([$product_id]);

      if(!$check_product->rowCount()){
         $error_msg[] = 'Product not found!';
      }else{
         
         $check_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
         $check_cart->execute([$user_id, $product_id, $color, $size]);

         if($check_cart->rowCount() > 0){
            $existing = $check_cart->fetch(PDO::FETCH_ASSOC);
            $new_qty = min($existing['qty'] + $quantity, 10);

            $update = $conn->prepare("UPDATE cart set qty = ? WHERE id = ?");
            $update->execute([$new_qty, $existing['id']]);

            $info_msg[] = 'Cart quantity updated!';
         }else{
            $insert_cart = $conn->prepare("INSERT INTO cart (id, user_id, product_id, qty, color, size) VALUES (?,?,?,?,?,?)");
            $insert_cart->execute([$cart_id, $user_id, $product_id, $quantity, $color, $size]);

            $success_msg[] = 'Item added to your cart!';
         }

      }

   }   

}

if(isset($_POST['update_qty'])){
   $cart_id = $_POST['cart_id'] ?? '';
   $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

   if(!valid_id($cart_id)){
      $error_msg[] = 'Invalid cart ID!';
   }elseif($qty !== false && $qty >= 1 && $qty <= 10){
      $stmt = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND user_id = ?");
      $stmt->execute([$qty, $cart_id, $user_id]);
      $success_msg[] = 'Cart quantity updated!';
   }else{
      $error_msg[] = 'Invalid cart quantity!';
   }
}

if(isset($_POST['delete_item'])){
   $cart_id = $_POST['cart_id'] ?? '';

   if(!valid_id($cart_id)){
      $error_msg[] = 'Invalid cart ID';
   }else{
      $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
      $stmt->execute([$cart_id, $user_id]);
      $success_msg[] = 'Cart item removed!';
   }
}

if(isset($_POST['clear_all'])){

   $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
   $stmt->execute([$user_id]);
   $success_msg[] = 'Cart cleared!';

}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shopping Cart</title>

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<!-- products section starts  -->

<section class="products">

<?php
   $count_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
   $count_cart->execute([$user_id]);
   $cart_total = $count_cart->rowCount();
?>

<div class="heading">
   <h3>all products</h3>
   <div id="open-cart"><i class="fas fa-shopping-cart"></i><span><?= $cart_total; ?></span></div>
</div>

<div class="box-container">

<?php
   $select_products = $conn->prepare("SELECT * FROM products");
   $select_products->execute();

   if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
?>
   <form action="" method="POST" class="box">
      <input type="hidden" name="product_id" value="<?= e($fetch_product['id']); ?>">
      <img src="images/<?= e($fetch_product['image']); ?>" alt="<?= e($fetch_product['name']); ?>" class="img">
      <div class="flex">
         <h3><?= e($fetch_product['name']); ?></h3>
         <p class="price"><span>₹</span><?= number_format($fetch_product['price'], 2); ?></p>
      </div>
      <div class="flex">
         <p class="title">select quantity</p>
         <input type="number" name="qty" class="qty" min="1" max="10" maxlength="2" value="1" required>
      </div>
      <div class="flex">
         <p class="title">select color</p>
         <select name="color" required>
            <option value="blue">blue</option>
            <option value="red">red</option>
            <option value="green">green</option>
         </select>
      </div>
      <div class="flex">
         <p class="title">select size</p>
         <select name="size" required>
            <option value="XS">XS</option>
            <option value="S">S</option>
            <option value="M">M</option>
            <option value="L" selected>L</option>
            <option value="XL">XL</option>
            <option value="XXL">XXL</option>
            <option value="XXXL">XXXL</option>
         </select>
      </div>
      <input type="submit" value="add to cart" class="btn" name="add_to_cart">
   </form>
<?php
      }
   }else{
      echo '<p class="empty">no products found!</p>';
   }
?>

</div>

</section>

<!-- products section ends -->

<!-- cart section starts  -->

<section class="cart">

   <div class="heading">
      <h3>shopping cart</h3>
      <div class="fas fa-xmark" id="close-cart"></div>
   </div>

   <div class="box-container">

   <?php
      $grand_total = 0;
      $product_missing = false;

      $select_cart = $conn->prepare("SELECT c.*, p.name, p.price, p.image FROM cart c LEFT JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
      $select_cart->execute([$user_id]);

      if($select_cart->rowCount() > 0){
         while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
            if(is_null($fetch_cart['name']) || is_null($fetch_cart['price']) || is_null($fetch_cart['image'])){
               $product_missing = true;
   ?>
   <div class="box">
      <h3 class="name">this product is no logner available!</h3>
      <form action="" method="POST">
         <input type="hidden" name="cart_id" value="<?= e($fetch_cart['id']); ?>">
         <button type="submit" value="1" name="delete_item" class="del-btn">remove</button>
      </form>
   </div>
   <?php
      continue;
         }
      $sub_total = $fetch_cart['price'] * $fetch_cart['qty'];
      $grand_total += $sub_total;
   ?>
   <div class="box">
      <img src="images/<?= e($fetch_cart['image']); ?>" alt="<?= e($fetch_cart['name']); ?>">

      <div class="flex">
         <h3 class="name"><?= e($fetch_cart['name']); ?></h3>
         <p class="price"><span>₹</span><?= number_format($fetch_cart['price'], 2); ?></p>
      </div>

      <div class="flex">
         <p class="title">color</p>
         <p class="title"><?= e($fetch_cart['color']); ?></p>
      </div>

      <div class="flex">
         <p class="title">size</p>
         <p class="title"><?= e($fetch_cart['size']); ?></p>
      </div>

      <div class="flex">
         <p class="title">quantity</p>
         <form action="" method="POST">
            <input type="hidden" name="cart_id" value="<?= e($fetch_cart['id']); ?>">
            <input type="number" name="qty" class="qty-input" min="1" max="10" maxlength="2" value="<?= e($fetch_cart['qty']); ?>" required>
            <button type="submit" class="fas fa-edit update-btn" name="update_qty" disabled></button>
         </form>
      </div>

      <div class="flex">
         <p class="title">sub total</p>
         <p class="sub-total">₹<?= number_format($sub_total, 2); ?></p>
      </div>

      <div class="flex">
         <form action="" method="POST">
            <input type="hidden" name="cart_id" value="<?= e($fetch_cart['id']); ?>">
            <button type="submit" value="1" name="delete_item" class="del-btn" onclick="return confirm(event, 'Delete this from cart?');">remove</button>
         </form>
         <a href="checkout.php?id=<?= e($fetch_cart['id']); ?>" class="btn">buy now</a>
      </div>
   </div>
   <?php
         }

         if($product_missing){
            echo '<p class="empty">some products in your cart are no longer available.</p>';
         }

         if($grand_total == 0 && !$product_missing){
            echo '<p class="empty">no items to show in your cart.</p>';
         }

      }else{
         echo '<p class="empty">your cart is empty.</p>';
      }
   ?>
   </div>

   <?php if ($grand_total > 0): ?>
      <div class="cart-total">
         <p class="total">grand total : <span>₹<?= number_format($grand_total, 2); ?></span></p>
         <form action="" method="POST">
            <button type="submit" name="clear_all" class="del-btn" value="1" onclick="return confirm(event, 'Delete all from cart?');">clear all</button>
            <a href="checkout.php" class="btn">proceed to checkout</a>
         </form>
      </div>
   <?php endif; ?>   

</section>

<!-- cart section ends -->

















<!-- sweet alert js cdn link  -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

<?php include 'alert.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>
   
</body>
</html>