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


function insertRandomUserFeedback() {
  const users = ['U101', 'U102', 'U103', 'U104', 'U105'];
  const comments = [
    "Looks amazing with any fit.",
    "Feels a bit tight but stylish.",
    "Great grip and comfort.",
    "High quality material and finish.",
    "A must-have for sneakerheads!"
  ];

  const getShoesQuery = "SELECT Shoe_ID FROM Shoe_Products";

  connection.query(getShoesQuery, (err, results) => {
    if (err) {
      console.error("❌ Failed to fetch shoe IDs:", err.message);
      connection.end();
      return;
    }

    const shoeIDs = results.map(row => row.Shoe_ID);
    const queries = [];

    users.forEach(user => {
      const shuffled = shoeIDs.sort(() => 0.5 - Math.random());
      const picked = shuffled.slice(0, 3);

      picked.forEach(shoeID => {
        const score = Math.floor(Math.random() * 3) + 3;
        const comment = comments[Math.floor(Math.random() * comments.length)];

        queries.push(new Promise((res, rej) => {
          connection.query("INSERT IGNORE INTO Rating (UserID, Shoe_ID, Score) VALUES (?, ?, ?)",
            [user, shoeID, score], (err) => err ? rej(err) : res());
        }));

        queries.push(new Promise((res, rej) => {
          connection.query("INSERT IGNORE INTO Review (UserID, Shoe_ID, Comment) VALUES (?, ?, ?)",
            [user, shoeID, comment], (err) => err ? rej(err) : res());
        }));

        queries.push(new Promise((res, rej) => {
          connection.query("INSERT IGNORE INTO Feedback (UserID, Shoe_ID) VALUES (?, ?)",
            [user, shoeID], (err) => err ? rej(err) : res());
        }));
      });
    });

    Promise.all(queries)
      .then(() => {
        console.log("🎉 User ratings, reviews, and feedback added.");
        connection.end();
      })
      .catch((err) => {
        console.error("❌ Error inserting user feedback:", err.message);
        connection.end();
      });
  });
}

insertRandomUserFeedback();
