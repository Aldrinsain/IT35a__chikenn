<?php
session_start();
include("../db/config.php");

if(!isset($_SESSION['username']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

/* =====================
   OVERVIEW STATISTICS
=====================*/
$total_chickens = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM chickens"))['total'];

$total_farmers = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM users WHERE role='user'"))['total'];

$outside = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM chickens WHERE boundary_status='Outside Boundary'"))['total'];

/* =====================
   ZONE DATA
=====================*/
$zone_labels = [];
$zone_values = [];

$zone = mysqli_query($conn,"SELECT current_zone, COUNT(*) as total FROM chickens GROUP BY current_zone");

while($row = mysqli_fetch_assoc($zone)){
    $zone_labels[] = $row['current_zone'];
    $zone_values[] = $row['total'];
}

/* =====================
   BOUNDARY DATA
=====================*/
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

<title>Admin Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
font-family:Segoe UI;
margin:0;
background:#f4f6f8;
}

header{
background:#007bff;
color:white;
padding:20px;
text-align:center;
}

.container{
width:90%;
margin:auto;
margin-top:30px;
}

.cards{
display:flex;
gap:20px;
flex-wrap:wrap;
}

.card{
flex:1;
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 4px 10px rgba(0,0,0,0.1);
text-align:center;
}

.card h2{
color:#007bff;
margin:0;
}

table{
width:100%;
border-collapse:collapse;
margin-top:20px;
background:white;
}

table th{
background:#007bff;
color:white;
padding:10px;
}

table td{
padding:10px;
border-bottom:1px solid #ddd;
}

.section{
margin-top:40px;
}

.charts{
display:flex;
gap:30px;
flex-wrap:wrap;
margin-top:20px;
}

.chart-box{
flex:1;
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

</style>

</head>

<body>

<header>

<h1>Admin Dashboard</h1>
<p>Welcome <?php echo $_SESSION['username']; ?></p>
<a href="../logout.php" style="color:white;">Logout</a>

</header>

<div class="container">

<!-- OVERVIEW -->

<div class="cards">

<div class="card">
<h2><?php echo $total_chickens; ?></h2>
<p>Total Chickens</p>
</div>

<div class="card">
<h2><?php echo $total_farmers; ?></h2>
<p>Total Farmers</p>
</div>

<div class="card">
<h2><?php echo $outside; ?></h2>
<p>Outside Boundary</p>
</div>

</div>

<!-- CHICKEN MANAGEMENT -->

<div class="section">

<h2>Chicken Management</h2>

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

</div>


<!-- FARMER MANAGEMENT -->

<div class="section">

<h2>Farmer Management</h2>

<table>

<tr>
<th>ID</th>
<th>Username</th>
<th>Role</th>
</tr>

<?php

$users = mysqli_query($conn,"SELECT * FROM users WHERE role='user'");

while($row = mysqli_fetch_assoc($users)){

echo "<tr>

<td>".$row['user_id']."</td>
<td>".$row['username']."</td>
<td>".$row['role']."</td>

</tr>";

}

?>

</table>

</div>


<!-- CHARTS -->

<div class="section">

<h2>Monitoring Charts</h2>

<div class="charts">

<div class="chart-box">

<h3>Grazing Zone Chart</h3>

<canvas id="zoneChart"></canvas>

</div>

<div class="chart-box">

<h3>Boundary Chart</h3>

<canvas id="boundaryChart"></canvas>

</div>

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
backgroundColor:"#007bff"
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