<?php
require_once 'includesconfig.php';
requireLogin();

$listingId = (int)($_GET['listing'] ?? 0);
if (!$listingId) { header('Location: listings.php'); exit(); }

$db = getDB();
$stmt = $db->prepare("
    SELECT l.*, u.full_name AS seller_name, u.email AS seller_email
    FROM listings l JOIN users u ON l.user_id = u.user_id
    WHERE l.listing_id = ? AND l.status = 'active'
");
$stmt->bind_param("i", $listingId);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();

if (!$listing) { header('Location: listings.php?error=notfound'); exit(); }
if ($listing['user_id'] === $_SESSION['user_id']) { header('Location: listing.php?id='.$listingId.'&error=ownseller'); exit(); }

$error = '';
$buyerId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['shipping_address'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');

    if (!$address) {
        $error = 'Please provide a shipping  collection address.';
    } else {
         Create order
        $stmt = $db->prepare("INSERT INTO orders (buyer_id, listing_id, amount, shipping_address, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidss", $buyerId, $listingId, $listing['price'], $address, $notes);
        $stmt->execute();
        $orderId = $db->insert_id;

         Create pending payment
        $stmt2 = $db->prepare("INSERT INTO payments (order_id, amount, status) VALUES (?, ?, 'pending')");
        $stmt2->bind_param("id", $orderId, $listing['price']);
        $stmt2->execute();

         Mark listing as pending (so it's not double-sold)
        $db->query("UPDATE listings SET status='pending' WHERE listing_id=$listingId");

         In production: redirect to PayFast with order details
         For demo, we show success
         Send notification message to buyer from system
$systemMsg = "Your order #$orderId has been placed successfully! Total: R " . number_format($listing['price'], 2) . ". The seller will contact you soon to arrange delivery.";
$adminId = 1;  System notification sender
$stmt3 = $db->prepare("INSERT INTO messages (sender_id, receiver_id, listing_id, message_text) VALUES (?, ?, ?, ?)");
$stmt3->bind_param("iiis", $adminId, $buyerId, $listingId, $systemMsg);
$stmt3->execute();

header("Location: dashboard.php?order=$orderId&success=ordered");
exit();
        exit();
    }
}

$pageTitle = 'Checkout';
include 'includesheader.php';
?>

<div class="container" style="padding-top:2.5rem;padding-bottom:4rem;max-width:700px">
  <div class="section-label">Checkout<div>
  <h1 style="margin-bottom:1.5rem">Complete your order<h1>

  <?php if ($error): ?><div class="alert-kitchen error mb-3"><?= h($error) ?><div><?php endif; ?>

  <div class="row g-4">
    <!-- Order summary -->
    <div class="col-md-5">
      <div class="kitchen-card" style="position:sticky;top:84px">
        <div style="font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;color:var(--kitchen-gray);margin-bottom:1rem">Order summary<div>
        <div style="background:var(--kitchen-light);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1rem">
          <div style="font-weight:600;margin-bottom:0.3rem"><?= h($listing['title']) ?><div>
          <div style="font-size:13px;color:var(--kitchen-gray)">Sold by <?= h($listing['seller_name']) ?><div>
          <div style="font-size:12px;color:var(--kitchen-gray)"><?= h($listing['category']) ?><div>
        <div>
        <div class="d-flex justify-content-between mb-2" style="font-size:14px">
          <span>Item price<span>
          <span>R <?= number_format($listing['price'], 2) ?><span>
        <div>
        <div class="d-flex justify-content-between mb-2" style="font-size:14px;color:var(--kitchen-gray)">
          <span>Platform fee<span>
          <span>R 0.00<span>
        <div>
        <hr style="border-color:var(--kitchen-border)">
        <div class="d-flex justify-content-between">
          <strong>Total<strong>
          <strong class="listing-price" style="font-size:1.2rem">R <?= number_format($listing['price'], 2) ?><strong>
        <div>
        <div class="mt-3" style="font-size:12px;color:var(--kitchen-gray);background:var(--kitchen-light);border-radius:var(--radius-sm);padding:0.8rem">
          <i class="bi bi-shield-check text-green"><i>
          Payment processed securely via <strong>PayFast<strong>. Your money is protected until delivery is confirmed.
        <div>
      <div>
    <div>

    <!-- Checkout form -->
    <div class="col-md-7">
      <div class="kitchen-card">
        <h4 style="font-size:1.1rem;margin-bottom:1.2rem">Delivery  collection details<h4>
        <form method="POST" data-validate>
          <div class="mb-3">
            <label class="form-label">Your address or collection point *<label>
            <textarea name="shipping_address" class="form-control" rows="3" required placeholder="e.g. 12 Mokoena Street, Soweto, 1804"><textarea>
          <div>
          <div class="mb-4">
            <label class="form-label">Notes to seller<label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions for the seller…"><textarea>
          <div>
          <button type="submit" class="btn-kitchen-primary w-100" style="justify-content:center;padding:0.9rem;font-size:15px">
            <i class="bi bi-lock-fill"><i> Confirm &amp; Pay – R <?= number_format($listing['price'], 2) ?>
          <button>
          <p style="font-size:12px;text-align:center;color:var(--kitchen-gray);margin-top:0.8rem">
            You will be redirected to PayFast to complete payment securely.
          <p>
        <form>
      <div>
    <div>
  <div>
<div>

<?php include 'includesfooter.php'; ?>
