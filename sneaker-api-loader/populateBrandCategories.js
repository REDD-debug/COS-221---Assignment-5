const mysql = require("mysql");

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

// Category mapping
const categoryMap = {
  // Jordan
  "Jordan 1": "CJ01",
  "Jordan 4": "CJ02",
  "Jordan 11": "CJ03",
  "Jordan 12": "CJ04",
  "Jordan 14": "CJ05",
  "Jordan 3": "CJ06",
  "Jordan 5": "CJ07",
  "Jordan 6": "CJ08",
  "Jordan Jumpman": "CJ09",

  // Nike
  "Air Force 1": "CN01",
  "Air Max Plus": "CN02",
  "SB Dunk": "CN03",
  "P-6000": "CN04",
  "Air Max ": "CN05",
  "Kobe": "CN06",
  "Foamposite": "CN07",
  "Zoom Vomero": "CN08",
  "ReactX": "CN09",
  "Zoom Pegasus": "CN10",
  "Ja": "CN11",
  "KD": "CN12",
  "GT Cut": "CN13",
  "Diamond Turf": "CN14",
  "Field Jaxx": "CN15",
  "Dunk": "CN16",
  "Air Zoom": "CN17",
  "Air DT": "CN18",

  // Adidas
  "Yeezy Boost 350 V2": "CA01",
  "Yeezy Boost 700": "CA02",
  "Yeezy Slide": "CA03",
  "Yeezy Foam RNR": "CA04",
  "Yeezy 500": "CA05",
  "Yeezy 450": "CA06",
  "Handball Spezial": "CA07",
  "Samba": "CA08",
  "Campus": "CA09",
  "Gazelle": "CA10",
  "Climacool": "CA11",
  "Ballerina": "CA12",
  "Taekwondo": "CA13",
  "Response CL": "CA14",
  "AE 1": "CA15",
  "Adizero": "CA16",
  "BW Army": "CA17",
  "Harden Vol. 8": "CA18",
  "Bad Bunny": "CA19",
  "Slides": "CA20",

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
  "AC Milan": "CP10",
  "Avanti": "CP11",
  "Ghostbusters": "CP12",
  "Teenage Mutant Ninja Turtles": "CP13"
};


// Get all shoes from Shoe_Products
function getAllShoes() {
  return new Promise((resolve, reject) => {
    connection.query("SELECT Shoe_ID, Brand_ID, Name FROM Shoe_Products", (err, results) => {
      if (err) return reject(err);
      resolve(results);
    });
  });
}

// Insert into brand table (Nike, Jordan, etc.)
function insertIntoBrandTable(shoeID, brand, shoeName) {
  const brandTable = brand;
  let categoryID = null;
  let model = null;

  const sortedKeys = Object.keys(categoryMap).sort((a, b) => b.length - a.length);
  for (const key of sortedKeys) {
    if (shoeName.toLowerCase().includes(key.toLowerCase())) {
      categoryID = categoryMap[key];
      model = key;
      break;
    }
  }

  if (!categoryID) return Promise.resolve();

  const sql = `
    INSERT IGNORE INTO ${brandTable} (Shoe_ID, Category_ID, Model)
    VALUES (?, ?, ?)
  `;
  const values = [shoeID, categoryID, model];

  return new Promise((resolve, reject) => {
    connection.query(sql, values, (err) => err ? reject(err) : resolve({ shoeID, categoryID }));
  });
}

// Insert into As_Per
function insertIntoAsPer(shoeID, categoryID) {
  const sql = `
    INSERT IGNORE INTO As_Per (Shoe_ID, Category_ID)
    VALUES (?, ?)
  `;
  return new Promise((resolve, reject) => {
    connection.query(sql, [shoeID, categoryID], (err) => {
      if (err) return reject(err);
      resolve();
    });
  });
}

// Main population flow
async function populateBrandAndAsPer() {
  const shoes = await getAllShoes();
  for (const shoe of shoes) {
    try {
      const result = await insertIntoBrandTable(shoe.Shoe_ID, shoe.Brand_ID, shoe.Name);
      if (result && result.categoryID) {
        await insertIntoAsPer(result.shoeID, result.categoryID);
      }
    } catch (err) {
      console.warn(`⚠️ Failed for ${shoe.Name}:`, err.message);
    }
  }

  console.log("🎉 Brand tables + As_Per synced.");
  connection.end();
}

// Run
populateBrandAndAsPer();
