<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Products</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
    <div class="sidebar">
      <h2>Filters</h2>
    
      <div class="filter-group">
        <label for="search">Search</label>
        <input type="text" id="search" placeholder="Search by name, brand, etc.">
      </div>
    
      <div class="filter-group">
        <label for="brand">Brand</label>
        <select id="brand">
          <option value="">All Brands</option>
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
          <option value="Name">Name (A-Z)</option>
          <option value="Release_Date">Release Date (Newest)</option>
          <option value="Name DESC">Name (Z-A)</option>
          <option value="Release_Date DESC">Release Date (Oldest)</option>
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
    
      <button id="apply-filters" >Apply Filters</button>
    </div>
    
    <div class="main-content">
      <nav class="navbar">
        <ul class="nav">
          <li class="dropdown">
          <a href="http://localhost/COS221/html/top-rated.html" class="nav-label">Top Rated</a>
        </li>

          <li class="dropdown">
            <a href="http://localhost/COS221/html/index.php" class="nav-label">ALL</a>
          </li>
    
           <li>
            <button id="logout-btn" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Logout</button>
          </li>
        </ul>
      </nav>
      
      <h2>All Shoes</h2>
      <div id="products-container">
        <div class="loading">Loading products...</div>
      </div>
      
      <div class="pagination" id="pagination">
        <!-- Pagination will be added by JavaScript -->
      </div>
    </div>
    
   
    
    <script src="../sneaker-api-loader/index.js"></script>
    <script>
        document.getElementById('logout-btn').addEventListener('click', async function() {
            try {
                // Clear localStorage
                localStorage.removeItem('apiKey');
                localStorage.removeItem('userType');
                localStorage.removeItem('userName');

                // Clear apiKey cookie
                document.cookie = 'apiKey=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';

                // Make a request to logout.php to invalidate server-side session
                await fetch('http://localhost/COS221/html/homepage.html', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                // Redirect to login page
                window.location.replace('http://localhost/COS221/html/homepage.html');
            } catch (error) {
                console.error('Logout error:', error);
                // Redirect to login page even if the request fails
                window.location.replace('http://localhost/COS221/html/homepage.html');
            }
        });
    </script>
</body>
</html>
