<?php
// Database Connection
$conn = new mysqli("localhost", "root", "", "flavour_hub");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = "";
$result = null;
$rows   = [];

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

    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();

    // Collect rows so we can render list and details in different places
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
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

    .container { padding:20px; text-align:center; }

    /* Card grid */
    #recipe-list { display:flex; flex-wrap:wrap; justify-content:center; gap:20px; }
    .recipe-card { 
        width:280px;
        border:1px solid #ddd;
        border-radius:10px;
        background:#fff;
        text-align:center;
        box-shadow:0 2px 5px rgba(0,0,0,0.1);
        cursor:pointer;
        overflow:hidden;
        transition:transform .2s ease;
    }
    .recipe-card:hover { transform:scale(1.02); }
    .recipe-card img { width:100%; height:200px; object-fit:cover; }
    .recipe-title { padding:10px; font-size:18px; font-weight:bold; background:#eee; }

    /* Full page view */
    .recipe-full { 
        display:none;
        max-width:900px;
        margin:20px auto;
        background:#fff;
        padding:20px;
        border-radius:10px;
        box-shadow:0 2px 10px rgba(0,0,0,0.15);
        text-align:left;
    }
    .recipe-full img { width:100%; border-radius:10px; margin-bottom:15px; }
    .back-btn {
        display:inline-block; margin-bottom:20px; padding:8px 14px;
        background:#333; color:#fff; border-radius:6px; text-decoration:none; cursor:pointer;
    }
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
<?php if (count($rows) > 0) { ?>
    <!-- LIST (cards only) -->
    <div id="recipe-list">
        <?php foreach ($rows as $row) {
            // SAFELY pick an id field (fixes "Undefined index: id" notices)
            $rid = isset($row['id']) ? (int)$row['id']
                : (isset($row['recipe_id']) ? (int)$row['recipe_id']
                : (isset($row['rid']) ? (int)$row['rid']
                : abs(crc32($row['recipename'])))); // stable fallback
        ?>
            <div class="recipe-card" role="button" tabindex="0"
                 onclick="openRecipe('recipe-<?php echo $rid; ?>')"
                 onkeypress="if(event.key==='Enter'){openRecipe('recipe-<?php echo $rid; ?>')}">
                <?php if (!empty($row['image'])) { ?>
                    <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" 
                         alt="<?php echo htmlspecialchars($row['recipename']); ?>">
                <?php } else { ?>
                    <img src="default.png" alt="No image available">
                <?php } ?>
                <div class="recipe-title"><?php echo htmlspecialchars($row['recipename']); ?></div>
            </div>
        <?php } ?>
    </div>

    <!-- DETAILS (outside the list!) -->
    <?php foreach ($rows as $row) {
        $rid = isset($row['id']) ? (int)$row['id']
            : (isset($row['recipe_id']) ? (int)$row['recipe_id']
            : (isset($row['rid']) ? (int)$row['rid']
            : abs(crc32($row['recipename']))));
    ?>
        <div class="recipe-full" id="recipe-<?php echo $rid; ?>">
            <span class="back-btn" onclick="closeRecipe()">← Back to Recipes</span>
            <?php if (!empty($row['image'])) { ?>
                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" 
                     alt="<?php echo htmlspecialchars($row['recipename']); ?>">
            <?php } else { ?>
                <img src="default.png" alt="No image available">
            <?php } ?>
            <h1><?php echo htmlspecialchars($row['recipename']); ?></h1>
            <p><strong>Ingredients:</strong><br><?php echo nl2br(htmlspecialchars($row['ingredients'])); ?></p>
            <p><strong>Steps:</strong><br><?php echo nl2br(htmlspecialchars($row['recipesteps'])); ?></p>
            <?php if (!empty($row['youtube_link'])) { ?>
                <p><a href="<?php echo htmlspecialchars($row['youtube_link']); ?>" target="_blank">📺 Watch on YouTube</a></p>
            <?php } ?>
        </div>
    <?php } ?>

<?php } elseif(isset($_GET['search'])) { ?>
    <p style="color:red; font-size:18px;">No recipes found for "<?php echo htmlspecialchars($search); ?>"</p>
<?php } else { ?>
    <p style="color:gray;">Enter ingredients or recipe name to search.</p>
<?php } ?>
</div>

<script>
function openRecipe(detailId) {
    // hide list
    const list = document.getElementById('recipe-list');
    if (list) list.style.display = 'none';

    // hide any open detail
    document.querySelectorAll('.recipe-full').forEach(el => el.style.display = 'none');

    // show selected detail
    const target = document.getElementById(detailId);
    if (target) target.style.display = 'block';
}

function closeRecipe() {
    // hide details
    document.querySelectorAll('.recipe-full').forEach(el => el.style.display = 'none');
    // show list
    const list = document.getElementById('recipe-list');
    if (list) list.style.display = 'flex';
}
</script>

</body>
</html>