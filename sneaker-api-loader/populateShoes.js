const mysql = require("mysql");
const SneaksAPI = require("sneaks-api");
const sneaks = new SneaksAPI();

const connection = mysql.createConnection({
  host: "localhost",
  port: 3307,
  user: "root",
  password: "Mishka28",
  database: "shoe_api"
});

connection.connect((err) => {
  if (err) throw err;
  console.log("✅ Connected to MySQL");
});

// Retailer mapping to DB IDs
const retailerMap = {
  "stockX": "R001",
  "flightClub": "R002",
  "goat": "R003",
  "stadiumGoods": "R004"
};

// Insert into Shoe_Products
function insertSneaker(product, brand) {
  return new Promise((resolve, reject) => {
    const sql = `
      INSERT IGNORE INTO Shoe_Products 
      (Shoe_ID, Name, Brand_ID, Color, Size, UserID, image_URL, Release_Date, Description)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;

    const values = [
      product.styleID || product.shoeName.replace(/\s/g, "").substring(0, 10),
      product.shoeName,
      brand,
      product.colorway || "N/A",
      9,
      "U101",
      product.thumbnail || null,
      product.releaseDate || '2000-01-01',
      product.description || 'No description available'
    ];

    connection.query(sql, values, (err) => {
      if (err) {
        console.error("❌ Sneaker insert failed:", err.message);
        return reject(err);
      }
      resolve();
    });
  });
}

// Insert into PriceListing
function insertPrices(styleID, shoe) {
  const inserts = [];

  const resellLinks = shoe.resellLinks || {};
  const lowestResellPrice = shoe.lowestResellPrice || {};

  for (const retailer of Object.keys(lowestResellPrice)) {
    const key = retailer;
    const retailerID = retailerMap[key];
    const rawPrice = lowestResellPrice[retailer];
    const parsedPrice = parseFloat(rawPrice);

    console.log(`🛒 Retailer: ${retailer} (${key}), Mapped: ${retailerID}, Price: ${rawPrice}`);

    if (!retailerID || isNaN(parsedPrice)) continue;

    const sql = `
      INSERT IGNORE INTO PriceListing (Price_ID, Shoe_ID, Price, Retailer_ID, Buy_Link)
      VALUES (?, ?, ?, ?, ?)
    `;

    const values = [
      `${styleID}_${retailerID}`, // Price_ID
      styleID,                    // Shoe_ID
      parsedPrice,                // Price
      retailerID,                 // Retailer_ID
      resellLinks[retailer] || null // Buy_Link
    ];

    inserts.push(new Promise((resolve, reject) => {
      connection.query(sql, values, (err) => {
        if (err) {
          console.error(`❌ Insert failed for ${retailer}:`, err.message);
          return reject(err);
        }
        resolve();
      });
    }));
  }

  return Promise.all(inserts);
}

// Fetch products for a brand
function getProductsAsync(brand) {
  return new Promise((resolve) => {
    sneaks.getProducts(brand, 63, (err, products) => {
      if (err || !products) {
        console.error(`⚠️ Failed to fetch ${brand} products`);
        return resolve([]);
      }
      resolve(products);
    });
  });
}

// Fetch product details by styleID
function getProductDetailsAsync(styleID) {
  return new Promise((resolve, reject) => {
    sneaks.getProductPrices(styleID, (err, shoe) => {
      if (err || !shoe) return reject(err);
      resolve(shoe);
    });
  });
}

// Populate shoes + prices
async function populateShoes() {
  const brands = ["Nike", "Jordan", "Adidas", "Puma"];

  for (const brand of brands) {
    const products = await getProductsAsync(brand);
    console.log(`🔍 ${brand}: Found ${products.length} products`);

    for (const product of products) {
      try {
        await insertSneaker(product, brand);
        const detailed = await getProductDetailsAsync(product.styleID);
        await insertPrices(product.styleID, detailed);
      } catch (err) {
        console.warn(`⚠️ Skipped ${product.shoeName}:`, err.message);
      }
    }

    console.log(`✅ Inserted all ${brand} sneakers`);
  }

  console.log("🎉 Shoe + price population complete.");
  connection.end();
}

populateShoes();
