<!DOCTYPE html>
<html>
<head>
    <title>Search Recipe</title>
</head>
<body>
    <h2>Search for a Recipe</h2>
    <form method="get" action="view.php">
        <input type="text" name="query" placeholder="Enter recipe name or ingredients..." required>
        <input type="submit" value="Search">
    </form>
    <hr>
</body>
</html>

<?php
// Database Connection
$conn = new mysqli("localhost", "root", "", "flavour_hub");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search input from user
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    // Split input into words (ingredients or keywords)
    $keywords = explode(",", $search); // user enters: "egg, milk, sugar"
    $keywords = array_map('trim', $keywords);

    // Prepare SQL conditions
    $conditions = [];
    foreach ($keywords as $word) {
        $word_safe = $conn->real_escape_string($word);
        $conditions[] = "(recipe_name LIKE '%$word_safe%' OR ingredients LIKE '%$word_safe%')";
    }

    // Combine with AND — recipe must have all ingredients
    $whereClause = implode(" AND ", $conditions);

    $sql = "SELECT * FROM recipes WHERE $whereClause";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<h3>" . htmlspecialchars($row['recipe_name']) . "</h3>";
            echo "<p>Ingredients: " . htmlspecialchars($row['ingredients']) . "</p>";
            echo "<p>Recipe: " . htmlspecialchars($row['recipe']) . "</p>";
            echo "<hr>";
        }
    } else {
        echo "No recipes found.";
    }
}
?>
