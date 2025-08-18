<?php
// Database Connection
$conn = new mysqli("localhost", "root", "", "flavour_hub");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = "";
$result = null;

if (isset($_GET['search']) && trim($_GET['search']) != "") {
    $search = trim($_GET['search']);

    // Split input into words by comma or space
    $ingredients = preg_split("/[\s,]+/", $search, -1, PREG_SPLIT_NO_EMPTY);

    // Build WHERE conditions dynamically
    $conditions = [];
    $params = [];
    foreach ($ingredients as $ingredient) {
        $conditions[] = "(ingredients LIKE ? OR recipename LIKE ?)";
        $params[] = "%$ingredient%";
        $params[] = "%$ingredient%";
    }

    $sql = "SELECT * FROM recipes WHERE " . implode(" AND ", $conditions) . " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);

    // Bind all parameters dynamically
    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Recipes - FlavourHub</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f4f4f4; }
        .navbar { display:flex; justify-content:space-between; align-items:center; background:#333; padding:10px 20px; }
        .navbar .logo { height:50px; }
        .navbar form { display:flex; }
        .navbar input { padding:5px; width:250px; }
        .navbar button { padding:5px; margin-left:5px; cursor:pointer; }
        .nav-links { list-style:none; display:flex; margin:0; padding:0; }
        .nav-links li { margin:0 10px; }
        .nav-links a { color:white; text-decoration:none; }
        .recipe-card { border:1px solid #ccc; padding:10px; margin:10px; border-radius:5px; width:300px; display:inline-block; vertical-align:top; background:#fff; text-align:left; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        .recipe-card img { width:100%; height:auto; border-radius:5px; }
        .container { padding:20px; text-align:center; }
        h3 { margin:10px 0; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo-search">
        <img src="logomake.png" alt="FlavourHub Logo" class="logo" />
        <form method="get" action="">
            <input type="text" name="search" placeholder="Enter ingredients (e.g., chicken, onion, tomato)" 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
    </div>
    <ul class="nav-links">
        <li><a href="index.html#home">Home</a></li>
        <li><a href="index.html#recipes">Recipes</a></li>
        <li><a href="index.html#upload">Upload</a></li>
        <li><a href="index.html#categories">Categories</a></li>
    </ul>
</nav>

<div class="container">
    <?php 
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) { ?>
            <div class="recipe-card">
                <?php if (!empty($row['image'])) { ?>
                    <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" 
                         alt="<?php echo htmlspecialchars($row['recipename']); ?>">
                <?php } else { ?>
                    <img src="default.png" alt="No image available">
                <?php } ?>
                <h3><?php echo htmlspecialchars($row['recipename']); ?></h3>
                <p><strong>Ingredients:</strong><br>
                   <?php echo nl2br(htmlspecialchars($row['ingredients'])); ?></p>
                <p><strong>Steps:</strong><br>
                   <?php echo nl2br(htmlspecialchars($row['recipesteps'])); ?></p>
                <?php if (!empty($row['youtube_link'])) { ?>
                    <p><a href="<?php echo htmlspecialchars($row['youtube_link']); ?>" target="_blank">
                        📺 Watch on YouTube</a></p>
                <?php } ?>
            </div>
    <?php 
        }
    } elseif(isset($_GET['search'])) { ?>
        <p style="color:red; font-size:18px;">No recipes found for "<?php echo htmlspecialchars($search); ?>"</p>
    <?php } else { ?>
        <p style="color:gray;">Enter ingredients or recipe name to search.</p>
    <?php } ?>
</div>

</body>
</html>
