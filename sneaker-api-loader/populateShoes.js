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

// Retailer IDs
const retailerMap = {
  "stockX": "R001",
  "flightClub": "R002",
  "goat": "R003",
  "stadiumGoods": "R004"
};

// Category detection
const categoryMap = {
  // Jordan
  "Jordan 1": "CJ01",
  "Jordan 4": "CJ02",
  "Jordan 11": "CJ03",
  "Jordan 12": "CJ04",
  "Jordan 14": "CJ05",
  // Nike
  "Air Force 1": "CN01",
  "Air Max Plus": "CN02",
  "SB Dunk": "CN03",
  "P-6000": "CN04",
  // Adidas
  "Yeezy Boost 350 V2": "CA01",
  "Yeezy Boost 700": "CA02",
  "Samba": "CA03",
  "Handball Spezial": "CA04",
  // Puma
  "MB.01": "CP01",
  "MB.02": "CP02",
  "MB.03": "CP03",
  "MB.04": "CP04",
  "Suede": "CP05",
  "Speedcat": "CP06",
  "Mostro": "CP07",
  "Easy Rider": "CP08",
  "KidSuper": "CP09",
  "AC Milan": "CP10"
};

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

function insertIntoBrandTable(shoeID, brand, shoeName) {
  const brandTable = brand;
  let model = null;
  let categoryID = null;

  // Sort keys by length descending to prioritize longer matches like "Jordan 12"
  const sortedKeys = Object.keys(categoryMap).sort((a, b) => b.length - a.length);

  for (const key of sortedKeys) {
    if (shoeName.toLowerCase().includes(key.toLowerCase())) {
      categoryID = categoryMap[key];
      model = key;
      break;
    }
  }

  if (!categoryID) return Promise.resolve(); // No match

  const sql = `
    INSERT IGNORE INTO ${brandTable} (Shoe_ID, Category_ID, Model)
    VALUES (?, ?, ?)
  `;

  const values = [shoeID, categoryID, model];

  return new Promise((resolve, reject) => {
    connection.query(sql, values, (err) => {
      if (err) {
        console.error(`❌ Insert into ${brandTable} failed:`, err.message);
        return reject(err);
      }
      resolve();
    });
  });
}

function insertPrices(styleID, shoe) {
  const inserts = [];
  const resellLinks = shoe.resellLinks || {};
  const lowestResellPrice = shoe.lowestResellPrice || {};

  for (const retailer of Object.keys(lowestResellPrice)) {
    const retailerID = retailerMap[retailer];
    const rawPrice = lowestResellPrice[retailer];
    const parsedPrice = parseFloat(rawPrice);

    if (!retailerID || isNaN(parsedPrice)) continue;

    const sql = `
      INSERT IGNORE INTO PriceListing (Price_ID, Shoe_ID, Price, Retailer_ID, Buy_Link)
      VALUES (?, ?, ?, ?, ?)
    `;

    const values = [
      `${styleID}_${retailerID}`,
      styleID,
      parsedPrice,
      retailerID,
      resellLinks[retailer] || null
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

function getProductDetailsAsync(styleID) {
  return new Promise((resolve, reject) => {
    sneaks.getProductPrices(styleID, (err, shoe) => {
      if (err || !shoe) return reject(err);
      resolve(shoe);
    });
  });
}

async function populateShoes() {
  const brands = ["Nike", "Jordan", "Adidas", "Puma"];

  for (const brand of brands) {
    const products = await getProductsAsync(brand);
    console.log(`🔍 ${brand}: Found ${products.length} products`);

    for (const product of products) {
      try {
        await insertSneaker(product, brand);
        await insertIntoBrandTable(product.styleID, brand, product.shoeName);
        const detailed = await getProductDetailsAsync(product.styleID);
        await insertPrices(product.styleID, detailed);
      } catch (err) {
        console.warn(`⚠️ Skipped ${product.shoeName}:`, err.message);
      }
    }

    console.log(`✅ Inserted all ${brand} sneakers`);
  }

  console.log("🎉 Shoe + price + brand category population complete.");
  connection.end();
}

populateShoes();
