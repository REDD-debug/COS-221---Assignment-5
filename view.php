<?php
// Start session or include necessary authentication
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product View</title>
    <link rel="stylesheet" href="CSS/view.css"> <!-- Adjusted to match folder structure -->
    <style>
        .back-button {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
            color: #000;
            text-decoration: none;
        }
        .back-button:hover {
            color: #555;
        }
    </style>
</head>
<body>
    <a href="html/index.php" class="back-button">×</a> <!-- Back button to index.php -->
    <div class="content-wrapper">
        <div class="container">
            <div class="product-view">
                <!-- Carousel for product images -->
                <div class="carousel">
                    <button class="prev">❮</button>
                    <div class="carousel-images">
                        <!-- Images will be populated by view.js -->
                    </div>
                    <button class="next">❯</button>
                </div>

                <!-- Product Information -->
                <div class="product-info">
                    <h1 id="product-name"></h1>
                    <p class="product-price" id="product-price"></p>
                    <p class="product-description" id="product-description"></p>
                    <div class="product-rating">
                        <strong>Rating:</strong> <span id="product-rating"></span>
                    </div>
                    <div class="product-review">
                        <strong>Review:</strong> <span id="product-review"></span>
                    </div>
                    <div class="add-rating-review">
                        <a href="#" id="add-rating-review-link">Add Rating/Review</a>
                    </div>
                    <div class="product-store-link">
                        <strong>Buy Now:</strong> <a id="product-buy-link" href="#" target="_blank">Visit Store</a>
                    </div>
