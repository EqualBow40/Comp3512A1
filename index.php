<?php


try {
    $pdo = new PDO("sqlite:data/data/stocks.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// safe query 
$userId = $_GET['userId'] ?? null;

// /////////////////////////////////////
// get all users for left panel
// ------------------------------------
$sqlUsers = "
    SELECT id, firstname, lastname
    FROM users
    ORDER BY lastname, firstname";

$stmt = $pdo->query($sqlUsers);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user = null;
$summary = null;
$portfolioRows = [];

if ($userId) {

    // ///////////////////////////////////
    // selected user 
    // ------------------------------------
    $sqlUser = "
        SELECT id, firstname, lastname, city, country, email
        FROM users
        WHERE id = :userId
    ";
    $stmt = $pdo->prepare($sqlUser);
    $stmt->execute(['userId' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // //////////////////////////////////////////
    // portfolio summary
    // total shares = SUM(amount)
    // number of companies = COUNT(*)
    // total value = SUM(amount * latest close)
    // ------------------------------------
    
    $sqlSummary = "
     SELECT
            SUM(p.amount) AS total_shares,
            COUNT(*) AS total_companies,
            SUM(p.amount * h.close) AS total_value
        FROM portfolio p
        JOIN history h
            ON p.symbol = h.symbol
        WHERE p.userId = :userId
          AND h.date = (
              SELECT MAX(h2.date)
              FROM history h2
              WHERE h2.symbol = p.symbol)";

    $stmt = $pdo->prepare($sqlSummary);
    $stmt->execute(['userId' => $userId]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // /////////////////////////////////////
    // portfolio detail rows
    // value = amount * latest close
    // ------------------------------------

    $sqlPortfolio = "
        SELECT
            p.symbol,
            c.name,
            p.amount,
            h.close,
            (p.amount * h.close) AS value
        FROM portfolio p
        JOIN companies c
            ON p.symbol = c.symbol
        JOIN history h
            ON p.symbol = h.symbol
        WHERE p.userId = :userId
          AND h.date = (
              SELECT MAX(h2.date)
              FROM history h2
              WHERE h2.symbol = p.symbol
          )
        ORDER BY c.name ";

    $stmt = $pdo->prepare($sqlPortfolio);
    $stmt->execute(['userId' => $userId]);
    $portfolioRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
///// helper functions //////////
function h($value) {
    return htmlspecialchars((string)$value);
}

function money($value) {
    return '$' . number_format((float)$value, 2);
}

function num($value) {
    return number_format((float)$value);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Portfolio</title>
   
   
   
   <style>

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6f9;
            color: #222;
        }

        header {
            background: #145e30;
            color: white;
            padding: 1rem 2rem;
        }

        .nav {
            margin-top: 0.5rem;
        }

        .nav a {
            color: white;
            text-decoration: none;
            margin-right: 1rem;
            font-weight: bold;
        }

        .layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .panel {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .user-list a {
            display: block;
            padding: 0.6rem 0;
            text-decoration: none;
            color: #121725;
            border-bottom: 1px solid #eee;
        }
         /* shows underlined and light background on hover */
        .user-list a:hover {
            text-decoration: underline;
            background: #eee 
        }

        .message {
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .user-header h2 {
            margin: 0 0 0.25rem 0;
        }

        .user-header p {
            margin-top: 0;
            color: #666;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(180px, 1fr));
            gap: 1rem;
            margin: 1.25rem 0 1.5rem 0;
        }

        .stat-box {
            background: #eef3ff;
            border-radius: 8px;
            padding: 1rem;
        }

        .stat-box h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
            color:  #145e30;
        }

        .stat-box p {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 0.7rem;
            text-align: left;
        }

        th {
            background:  #145e30;
            color: white;
        }
        /* For pattern look tables */
        tr:nth-child(even) {
            background: #f8f9fb;
        }
    </style>



</head>
<body>

<header>
    <h1>Customer Portfolio</h1>
    <nav class="nav">
        <a href="index.php">Home</a> 
        <a href="companies.php">Companies</a> 
        <a href="about.php">About</a>
    </nav>
</header>

<main class="layout">

    <aside class="panel">
        <h2>Customers</h2>
        <div class="user-list">
            <?php foreach ($users as $u): ?>
                <a href="index.php?userId=<?= urlencode($u['id']) ?>">
                    <?=  h($u['firstname']) ?> <?= h($u['lastname']) ?>   
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <section>
        <?php if (!$userId || !$user): ?>
            <div class="message">
        
                <p>Choose a customer to view portfolio summary and holdings.</p>
            </div>
        <?php else: ?>
            <div class="panel">
                <div class="user-header">

                    <h2><?= h($user['firstname']) ?> <?= h($user['lastname']) ?></h2>
                            
                    <p>
                        <?= h($user['city']) ?><?= $user['city'] && $user['country'] ? ', ' : '' ?><?= h($user['country']) ?> <!--if city exists and country exixsts shows comma else shows nothing--->
                    </p>

                </div>

                <div class="stats">
                    <div class="stat-box">
                        <h3># Shares</h3>
                        <p><?= num($summary['total_shares'] ?? 0) ?></p>
                    </div>

                    <div class="stat-box">
                        <h3># Companies</h3>
                        <p><?= num($summary['total_companies'] ?? 0) ?></p>
                    </div>

                    <div class="stat-box">
                        <h3>Total Value</h3>
                        <p><?= money($summary['total_value'] ?? 0) ?></p>
                    </div>
                </div>

                <h3>Portfolio Holdings</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Name</th>
                            <th>Share Amount</th>
                            <th>Latest Close</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portfolioRows as $row): ?>
                            <tr>
                                <td><?= h($row['symbol']) ?></td>
                                <td><?= h($row['name']) ?></td>
                                <td><?= num($row['amount']) ?></td>
                                <td><?= money($row['close']) ?></td>
                                <td><?= money($row['value']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</main>

</body>
</html>