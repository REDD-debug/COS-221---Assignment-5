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
    
      <button id="apply-filters" style="width: 100%;">Apply Filters</button>
    </div>
    
    <div class="main-content">
      <nav class="navbar">
        <ul class="nav">
          <li class="dropdown">
            <a href="index.html" class="nav-label">ALL</a>
          </li>
    
          <!-- Jordan -->
          <li class="dropdown">
            <a href="jordan.html" class="nav-label">Jordan ▼</a>
            <ul class="dropdown-menu" data-brand="Jordan">
              <li data-category="CJ01">Jordan 1</li>
              <li data-category="CJ02">Jordan 4</li>
              <li data-category="CJ03">Jordan 11</li>
              <li data-category="CJ04">Jordan 12</li>
              <li data-category="CJ05">Jordan 14</li>
              <li data-category="CJ06">Jordan 3</li>
              <li data-category="CJ07">Jordan 5</li>
              <li data-category="CJ08">Jordan 6</li>
              <li data-category="CJ09">Jordan Jumpman</li>
            </ul>
          </li>
    
          <!-- Nike -->
          <li class="dropdown">
            <a href="nike.html" class="nav-label">Nike ▼</a>
            <ul class="dropdown-menu" data-brand="Nike">
              <li data-category="CN01">Air Force 1</li>
              <li data-category="CN02">Air Max Plus</li>
              <li data-category="CN03">SB Dunk</li>
              <li data-category="CN04">P-6000</li>
              <li data-category="CN05">Air Max</li>
              <li data-category="CN06">Kobe</li>
              <li data-category="CN07">Foamposite</li>
              <li data-category="CN08">Zoom Vomero</li>
              <li data-category="CN09">ReactX</li>
              <li data-category="CN10">Zoom Pegasus</li>
              <li data-category="CN11">Ja</li>
              <li data-category="CN12">KD</li>
              <li data-category="CN13">GT Cut</li>
              <li data-category="CN14">Diamond Turf</li>
              <li data-category="CN15">Field Jaxx</li>
              <li data-category="CN16">Dunk</li>
              <li data-category="CN17">Air Zoom</li>
              <li data-category="CN18">Air DT</li>
            </ul>
          </li>
    
          <!-- Adidas -->
          <li class="dropdown">
            <a href="adidas.html" class="nav-label">Adidas ▼</a>
            <ul class="dropdown-menu" data-brand="Adidas">
              <li data-category="CA01">Yeezy Boost 350 V2</li>
              <li data-category="CA02">Yeezy Boost 700</li>
              <li data-category="CA03">Yeezy Slide</li>
              <li data-category="CA04">Yeezy Foam RNR</li>
              <li data-category="CA05">Yeezy 500</li>
              <li data-category="CA06">Yeezy 450</li>
              <li data-category="CA07">Handball Spezial</li>
              <li data-category="CA08">Samba</li>
              <li data-category="CA09">Campus</li>
              <li data-category="CA10">Gazelle</li>
              <li data-category="CA11">Climacool</li>
              <li data-category="CA12">Ballerina</li>
              <li data-category="CA13">Taekwondo</li>
              <li data-category="CA14">Response CL</li>
              <li data-category="CA15">AE 1</li>
              <li data-category="CA16">Adizero</li>
              <li data-category="CA17">BW Army</li>
              <li data-category="CA18">Harden Vol. 8</li>
              <li data-category="CA19">Bad Bunny</li>
              <li data-category="CA20">Slides</li>
            </ul>
          </li>
    
          <!-- Puma -->
          <li class="dropdown">
            <a href="puma.html" class="nav-label">Puma ▼</a>
            <ul class="dropdown-menu" data-brand="Puma">
              <li data-category="CP01">MB.01</li>
              <li data-category="CP02">MB.02</li>
              <li data-category="CP03">MB.03</li>
              <li data-category="CP04">MB.04</li>
              <li data-category="CP05">Suede</li>
              <li data-category="CP06">Speedcat</li>
              <li data-category="CP07">Mostro</li>
              <li data-category="CP08">Easy Rider</li>
              <li data-category="CP09">KidSuper</li>
              <li data-category="CP10">AC Milan</li>
              <li data-category="CP11">Avanti</li>
              <li data-category="CP12">Ghostbusters</li>
              <li data-category="CP13">Teenage Mutant Ninja Turtles</li>
            </ul>
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
    
    <button onclick="document.getElementById('id01').style.display='block'" style="width:auto; position: fixed; bottom: 20px; right: 20px;">Login</button>
    
    <div id="id01" class="modal">
      <form class="modal-content animate" action="../html/index.php" method="post">
        <div class="imgcontainer">
          <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">&times;</span>
        </div>
    
        <div class="container">
          <label for="uname"><b>Username</b></label>
          <input type="text" placeholder="Enter Username" name="uname" required>
    
          <label for="psw"><b>Password</b></label>
          <input type="password" placeholder="Enter Password" name="psw" required>
            
          <button type="submit">Login</button>
          <label>
            <input type="checkbox" checked="checked" name="remember"> Remember me
          </label>
        </div>
    
        <div class="container" style="background-color:#f1f1f1">
          <button type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Cancel</button>
          <span class="psw">Forgot <a href="#">password?</a></span>
        </div>
      </form>
    </div>
    
    <script src="../sneaker-api-loader/index.js"></script>
</body>
</html>