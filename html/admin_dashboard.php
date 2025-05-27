<?php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in'] || strcasecmp(trim($_SESSION['user']['user_type']), 'Admin') !== 0) {
    header("Location: http://localhost/COS221/login.php?user_type=Admin");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../CSS/admin.css">
</head>
<body>
    <div class="content-wrapper">
        <div class="sidebar">
            <h2>Filters</h2>
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" placeholder="Search products...">
            </div>
            <div class="filter-group">
                <label for="brand">Brand</label>
                <select id="brand">
                    <option value="">All Brand</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="color">Color</label>
                <select id="color">
                    <option value="">All Colors</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="size">Size</label>
                <select id="size">
                    <option value="">All Sizes</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="sort">Sort By</label>
                <select id="sort">
                    <option value="Name ASC">Name (A-Z)</option>
                    <option value="Release_Date DESC">Release Date (Newest)</option>
                    <option value="Name DESC">Name (Z-A)</option>
                    <option value="Release_Date ASC">Release Date (Oldest)</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="limit">Items Per Page</label>
                <select id="limit">
                    <option value="12">12</option>
                    <option value="24">24</option>
                    <option value="48">48</option>
                    <option value="96">96</option>
                </select>
            </div>
            <button id="apply-filters">Apply Filters</button>
        </div>

        <div class="main-content">
            <nav class="navbar">
                <ul class="nav">
                    <li class="dropdown">
                        <span class="nav-label">Top Rated</span>
                        <ul class="dropdown-menu">
                            <li><a href="#">ALL</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <span class="nav-label">ALL</span>
                        <ul class="dropdown-menu">
                            <!-- No additional items needed for "ALL" -->
                        </ul>
                    </li>
                    <li class="dropdown">
                        <span class="nav-label">Jordan ▼</span>
                        <ul class="dropdown-menu">
                            <li><a href="#">Jordan 1</a></li>
                            <li><a href="#">Jordan 4</a></li>
                            <li><a href="#">Jordan 11</a></li>
                            <li><a href="#">Jordan 12</a></li>
                            <li><a href="#">Jordan 14</a></li>
                            <li><a href="#">Jordan 3</a></li>
                            <li><a href="#">Jordan 5</a></li>
                            <li><a href="#">Jordan 6</a></li>
                            <li><a href="#">Jordan Jumpman</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <span class="nav-label">Nike ▼</span>
                        <ul class="dropdown-menu">
                            <li><a href="#">Air Force 1</a></li>
                            <li><a href="#">Air Max Plus</a></li>
                            <li><a href="#">SB Dunk</a></li>
                            <li><a href="#">P-6000</a></li>
                            <li><a href="#">Air Max</a></li>
                            <li><a href="#">Kobe</a></li>
                            <li><a href="#">Foamposite</a></li>
                            <li><a href="#">Zoom Vomero</a></li>
                            <li><a href="#">ReactX</a></li>
                            <li><a href="#">Zoom Pegasus</a></li>
                            <li><a href="#">Ja</a></li>
                            <li><a href="#">KD</a></li>
                            <li><a href="#">GT Cut</a></li>
                            <li><a href="#">Diamond Turf</a></li>
                            <li><a href="#">Field Jaxx</a></li>
                            <li><a href="#">Dunk</a></li>
                            <li><a href="#">Air Zoom</a></li>
                            <li><a href="#">Air DT</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <span class="nav-label">Adidas ▼</span>
                        <ul class="dropdown-menu">
                            <li><a href="#">Yeezy Boost 350 V2</a></li>
                            <li><a href="#">Yeezy Boost 700</a></li>
                            <li><a href="#">Yeezy Slide</a></li>
                            <li><a href="#">Yeezy Foam RNR</a></li>
                            <li><a href="#">Yeezy 500</a></li>
                            <li><a href="#">Yeezy 450</a></li>
                            <li><a href="#">Handball Spezial</a></li>
                            <li><a href="#">Samba</a></li>
                            <li><a href="#">Campus</a></li>
                            <li><a href="#">Gazelle</a></li>
                            <li><a href="#">Climacool</a></li>
                            <li><a href="#">Ballerina</a></li>
                            <li><a href="#">Taekwondo</a></li>
                            <li><a href="#">Response CL</a></li>
                            <li><a href="#">AE 1</a></li>
                            <li><a href="#">Adizero</a></li>
                            <li><a href="#">BW Army</a></li>
                            <li><a href="#">Harden Vol. 8</a></li>
                            <li><a href="#">Bad Bunny</a></li>
                            <li><a href="#">Slides</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <span class="nav-label">Puma ▼</span>
                        <ul class="dropdown-menu">
                            <li><a href="#">MB.01</a></li>
                            <li><a href="#">MB.02</a></li>
                            <li><a href="#">MB.03</a></li>
                            <li><a href="#">MB.04</a></li>
                            <li><a href="#">Suede</a></li>
                            <li><a href="#">Speedcat</a></li>
                            <li><a href="#">Mostro</a></li>
                            <li><a href="#">Easy Rider</a></li>
                            <li><a href="#">KidSuper</a></li>
                            <li><a href="#">AC Milan</a></li>
                            <li><a href="#">Avanti</a></li>
                            <li><a href="#">Ghostbusters</a></li>
                            <li><a href="#">Teenage Mutant Ninja Turtles</a></li>
                        </ul>
                    </li>
                    <li><a href="http://localhost/COS221/logout.php">Logout</a></li>
                </ul>
            </nav>

            <h2>All Shoes</h2>
            <div id="products-container">
                <div class="loading">Loading products...</div>
            </div>
            <div id="pagination" class="pagination"></div>
        </div>
    </div>
    <script src="admin.js"></script>
</body>
</html>