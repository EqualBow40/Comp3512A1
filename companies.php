<?php


// connection
try {
    $pdo = new PDO("sqlite:data/data/stocks.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e){
    die("Database connection failed: " . $e->getMessage());
}


// get symbol from URL safely
$symbol = $_GET['symbol'] ?? null;

// list of companies
$sqlCompanies = "SELECT symbol, name FROM companies ORDER BY name";
$stmt = $pdo->query($sqlCompanies);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


// variables for company
$company = null;
$stats = null;
$history = [];
 

// clicking company
if($symbol){

    // company details
    $sql = "SELECT * FROM companies WHERE symbol = :symbol";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['symbol'=>$symbol]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);


    // statistics
    $sql = "
        SELECT
            MAX(high) AS history_high,
            MIN(low) AS history_low,
            SUM(volume) AS total_volume,
            AVG(volume) AS average_volume
        FROM history
        WHERE symbol = :symbol
    "; //: symbol is a placeholder - PHP will replace it with the actual value.

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['symbol'=>$symbol]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);


    // history table
    $sql = "
        SELECT date, volume, open, close, high, low
        FROM history
        WHERE symbol = :symbol
        ORDER BY date ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['symbol'=>$symbol]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Companies</title>

<style>

body{
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
/*This is for the green header look across the page    */

.nav {
            margin-top: 0.5rem;
        }

        .nav a {
            color: white;
            text-decoration: none;
            margin-right: 1rem;
            font-weight: bold;
        }
.navi
{ 
    color: #121725;
    
}

.container{
    display:flex;
}

.sidebar{
    width:250px;
   

     background: white;
     padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.main{
    padding:20px;
    flex:1;
    
}

.company-link{
    display:block;
    padding:5px;
    text-decoration:none;
}

.company-link:hover{
    text-decoration: underline;
    background:#eee;
     
}

.statbox{
    display:inline-block;
    padding:15px;
    margin:10px;
    border:1px solid #ccc;
    border-radius:8px;
    width:150px;
    text-align:center;
      background: #eef4ff;
    
}

table{
    border-collapse:collapse;
    width:100%;
}

th{
    background:#145e30;
    color:white;
    padding:8px;
    text-align:left;
}

td{
    border:1px solid #ccc;
    padding:8px;
}
 tr:nth-child(even) {
            background: #f8f9fb;
        }

</style>

</head>

<body>

<header><h1>Stock Companies</h1> 

<nav class = "nav">  

<a href="index.php">Home</a> 
<a href="companies.php">Companies</a>  
<a href="about.php">About</a>
</nav>

</header> 

<div class="container">

<!-- LEFT PART-->

<div class="sidebar">

<h3>Companies</h3>

<?php foreach($companies as $c): ?>

<a class="company-link"
href="companies.php?symbol=<?=$c['symbol']?>">

<nav class = "navi" ><?=$c['name']?>
</nav> </a>

<?php endforeach; ?>

</div>


<!-- RIGHT  -->

<div class="main">

<?php if(!$symbol): ?>

<p>Please select a company to view details.</p>

<?php else: ?>

    <!-- company details --->
<h2><?=$company['name']?> (<?=$company['symbol']?>)</h2>

<ul>
    <li><p><b>Sector:</b> <?=$company['sector']?></p></li>
    <li><p><b>Sub-Industry:</b> <?=$company['subindustry']?></p></li>
    <li><p><b>Exchange:</b> <?=$company['exchange']?></p></li>
    <li><p><b>Website:</b> <a href="<?=$company['website']?>"><?=$company['website']?></a></p></li>
    <li><p><?=$company['description']?></p></li>
</ul>

<!--- statistic boxes --->
<div class="statbox">
History High<br><br>
<b>$<?=number_format($stats['history_high'],2)?></b>
</div>

<div class="statbox">
History Low<br><br>
<b>$<?=number_format($stats['history_low'],2)?></b>
</div>

<div class="statbox">
Total Volume<br><br>
<b><?=number_format($stats['total_volume'])?></b>
</div>

<div class="statbox">
Average Volume<br><br>
<b><?=number_format($stats['average_volume'],2)?></b>
</div>

<!---company block/ tablr --->
<h3>Company History</h3>

<table>

<tr>
<th>Date</th>
<th>Volume</th>
<th>Open</th>
<th>Close</th>
<th>High</th>
<th>Low</th>
</tr>

<?php foreach($history as $row): ?>

<tr>

<td><?=$row['date']?></td>
<td><?=number_format($row['volume'])?></td>
<td>$<?=number_format($row['open'],2)?></td>
<td>$<?=number_format($row['close'],2)?></td>
<td>$<?=number_format($row['high'],2)?></td>
<td>$<?=number_format($row['low'],2)?></td>

</tr>

<?php endforeach; ?>

</table>

<?php endif; ?>

</div>

</div>

</body>
</html>