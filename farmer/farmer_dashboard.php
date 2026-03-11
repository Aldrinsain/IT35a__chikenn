<?php
session_start();
include("../db/config.php");

// Allow only farmer
if(!isset($_SESSION['username']) || $_SESSION['role'] != 'user'){
    header("Location: ../login.php");
    exit();
}

/* STATISTICS */
$total_chickens = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM chickens"))['total'];
$inside = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM chickens WHERE boundary_status='Inside Boundary'"))['total'];
$outside = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM chickens WHERE boundary_status='Outside Boundary'"))['total'];

/* GRAZING ZONE DATA */
$zone_labels = [];
$zone_values = [];

$zones = mysqli_query($conn,"SELECT current_zone, COUNT(*) as total FROM chickens GROUP BY current_zone");

while($row = mysqli_fetch_assoc($zones)){
    $zone_labels[] = $row['current_zone'];
    $zone_values[] = $row['total'];
}

/* BOUNDARY DATA */
$boundary_labels = [];
$boundary_values = [];

$boundary = mysqli_query($conn,"SELECT boundary_status, COUNT(*) as total FROM chickens GROUP BY boundary_status");

while($row = mysqli_fetch_assoc($boundary)){
    $boundary_labels[] = $row['boundary_status'];
    $boundary_values[] = $row['total'];
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Farmer Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
margin:0;
font-family:Segoe UI;
background:#f4f6f8;
}

/* SIDEBAR */

.sidebar{
position:fixed;
left:0;
top:0;
width:220px;
height:100%;
background:#28a745;
color:white;
padding:20px;
}

.sidebar h2{
text-align:center;
}

.sidebar a{
display:block;
color:white;
text-decoration:none;
padding:10px;
margin-top:10px;
border-radius:5px;
}

.sidebar a:hover{
background:#218838;
}

/* MAIN CONTENT */

.main{
margin-left:240px;
padding:30px;
}

/* CARDS */

.cards{
display:flex;
gap:20px;
flex-wrap:wrap;
}

.card{
background:white;
padding:20px;
flex:1;
border-radius:10px;
box-shadow:0 3px 10px rgba(0,0,0,0.1);
text-align:center;
}

.card h2{
margin:0;
color:#28a745;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
background:white;
margin-top:20px;
}

table th{
background:#28a745;
color:white;
padding:10px;
}

table td{
padding:10px;
border-bottom:1px solid #ddd;
}

/* CHARTS */

.charts{
display:flex;
gap:30px;
flex-wrap:wrap;
margin-top:30px;
}

.chart-box{
background:white;
padding:20px;
border-radius:10px;
width:400px;
height:320px;
box-shadow:0 3px 10px rgba(0,0,0,0.1);
}

canvas{
width:100% !important;
height:250px !important;
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

<h2>Farmer Panel</h2>

<a href="#">Dashboard</a>
<a href="#chickens">My Chickens</a>
<a href="#charts">Monitoring</a>
<a href="../logout.php">Logout</a>

</div>

<!-- MAIN CONTENT -->

<div class="main">

<h1>Welcome <?php echo $_SESSION['username']; ?></h1>

<!-- OVERVIEW CARDS -->

<div class="cards">

<div class="card">
<h2><?php echo $total_chickens; ?></h2>
<p>Total Chickens</p>
</div>

<div class="card">
<h2><?php echo $inside; ?></h2>
<p>Inside Boundary</p>
</div>

<div class="card">
<h2><?php echo $outside; ?></h2>
<p>Outside Boundary</p>
</div>

</div>


<!-- CHICKEN TABLE -->

<h2 id="chickens">Chicken Monitoring</h2>

<table>

<tr>
<th>Tag Number</th>
<th>Breed</th>
<th>Current Zone</th>
<th>Boundary Status</th>
</tr>

<?php

$result = mysqli_query($conn,"SELECT * FROM chickens");

while($row = mysqli_fetch_assoc($result)){

echo "<tr>

<td>".$row['tag_number']."</td>
<td>".$row['breed']."</td>
<td>".$row['current_zone']."</td>
<td>".$row['boundary_status']."</td>

</tr>";

}

?>

</table>


<!-- CHARTS -->

<h2 id="charts">Monitoring Charts</h2>

<div class="charts">

<div class="chart-box">

<h3>Grazing Zone</h3>
<canvas id="zoneChart"></canvas>

</div>

<div class="chart-box">

<h3>Boundary Monitoring</h3>
<canvas id="boundaryChart"></canvas>

</div>

</div>

</div>

<script>

new Chart(document.getElementById("zoneChart"),{

type:"bar",

data:{
labels: <?php echo json_encode($zone_labels); ?>,

datasets:[{
label:"Chickens per Zone",
data: <?php echo json_encode($zone_values); ?>,
backgroundColor:"#28a745"
}]
}

});


new Chart(document.getElementById("boundaryChart"),{

type:"doughnut",

data:{
labels: <?php echo json_encode($boundary_labels); ?>,

datasets:[{
data: <?php echo json_encode($boundary_values); ?>,
backgroundColor:["green","red"]
}]
}

});

</script>

</body>
</html>