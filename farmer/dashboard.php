<?php
session_start();
include("../db/config.php");

/* FARMER LOGIN PROTECTION */
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

/* FETCH CHICKEN DATA FOR MAP AND TIME LOG */
$chickens = [];
$result = mysqli_query($conn,"SELECT tag_number, breed, current_zone, boundary_status, grazing_time, location1_time, location2_time FROM chickens");
while($row = mysqli_fetch_assoc($result)){
    $chickens[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Farmer Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
body{margin:0;font-family:Segoe UI;background:#f4f6f8;}
.sidebar{position:fixed;left:0;top:0;width:220px;height:100%;background:#28a745;color:white;padding:20px;}
.sidebar h2{text-align:center;}
.sidebar a{display:block;color:white;text-decoration:none;padding:10px;margin-top:10px;border-radius:5px;}
.sidebar a:hover{background:#218838;}
.main{margin-left:240px;padding:30px;}
.cards{display:flex;gap:20px;flex-wrap:wrap;}
.card{background:white;padding:20px;flex:1;border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,0.1);text-align:center;}
.card h2{margin:0;color:#28a745;}
table{width:100%;border-collapse:collapse;background:white;margin-top:20px;}
table th{background:#28a745;color:white;padding:10px;}
table td{padding:10px;border-bottom:1px solid #ddd;}
.charts{display:flex;gap:30px;flex-wrap:wrap;margin-top:30px;}
.chart-box{background:white;padding:20px;border-radius:10px;width:400px;height:320px;box-shadow:0 3px 10px rgba(0,0,0,0.1);}
canvas{width:100% !important;height:250px !important;}
.map-box{margin-top:30px;background:white;padding:20px;border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,0.1);}
#map{width:100%;height:500px;}
</style>

</head>

<body>



<div class="sidebar">

<h2>Farmer Panel</h2>

<a href="#">Dashboard</a>
<a href="#chickens">My Chickens</a>
<a href="#charts">Monitoring</a>
<a href="#map">Chicken Map</a>
<a href="../logout.php">Logout</a>

</div>



<div class="main">

<h1>Welcome <?php echo $_SESSION['username']; ?></h1>



<div class="cards">
<div class="card"><h2><?php echo $total_chickens; ?></h2><p>Total Chickens</p></div>
<div class="card"><h2><?php echo $inside; ?></h2><p>Inside Boundary</p></div>
<div class="card"><h2><?php echo $outside; ?></h2><p>Outside Boundary</p></div>

</div>




<h2 id="chickens">Chicken Monitoring</h2>

<table>

<tr>
<th>Tag Number</th>
<th>Breed</th>
<th>Zone</th>
<th>Boundary</th>
<th>Grazing Time</th>
<th><a href="location1_log.php" style="color:white;text-decoration:none;">Location 1 Time</a></th>
<th>Location 2 Time</th>
</tr>

<?php
foreach($chickens as $row){
    echo "<tr>
        <td>".$row['tag_number']."</td>
        <td>".$row['breed']."</td>
        <td>".$row['current_zone']."</td>
        <td>".$row['boundary_status']."</td>
        <td>".$row['grazing_time']."</td>
        <td>".$row['location1_time']."</td>
        <td>".$row['location2_time']."</td>
    </tr>";

    }

?>

</table>




<h2 id="charts">Monitoring Charts</h2>

<div class="charts">
<div class="chart-box"><h3>Grazing Zone</h3><canvas id="zoneChart"></canvas></div>
<div class="chart-box"><h3>Boundary Monitoring</h3><canvas id="boundaryChart"></canvas></div>
</div>

<div class="map-box">
<h2 id="map">Chicken Grazing Map (Manolo Fortich)</h2>
<div id="map"></div>
</div>

</div>

<script>
/* ZONE CHART */
new Chart(document.getElementById("zoneChart"),{
    type:"bar",
    data:{labels: <?php echo json_encode($zone_labels); ?>,
    datasets:[{label:"Chickens per Zone",data: <?php echo json_encode($zone_values); ?>,backgroundColor:"#28a745"}]}
});

/* BOUNDARY CHART */
new Chart(document.getElementById("boundaryChart"),{
    type:"doughnut",
    data:{labels: <?php echo json_encode($boundary_labels); ?>,
    datasets:[{data: <?php echo json_encode($boundary_values); ?>,backgroundColor:["green","red"]}]}
});

/* MAP */
const map = L.map('map').setView([8.3697,124.8644],15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(map);

const chickens = <?php echo json_encode($chickens); ?>;
let markers = [];

function getCurrentArea(){
    const hour = new Date().getHours();
    if(hour >= 8 && hour <= 12) return [8.3697,124.8644];
    else if(hour >= 13 && hour <= 16) return [8.3685,124.8655];
    else return [8.3690,124.8635];
}

chickens.forEach(chick=>{
    let area = getCurrentArea();
    let lat = area[0] + (Math.random()-0.5)*0.001;
    let lng = area[1] + (Math.random()-0.5)*0.001;
    let marker = L.marker([lat,lng]).addTo(map)
        .bindPopup("<b>"+chick.tag_number+"</b><br>Location 1: "+chick.location1_time+"<br>Location 2: "+chick.location2_time+"<br>Grazing: "+chick.grazing_time);
    markers.push({marker:marker,chick:chick});
});

function moveMarkers(){
    let area = getCurrentArea();
    markers.forEach(obj=>{
        let lat = area[0] + (Math.random()-0.5)*0.001;
        let lng = area[1] + (Math.random()-0.5)*0.001;
        obj.marker.setLatLng([lat,lng]);
    });
}
setInterval(moveMarkers,5000);

/* --- UPDATE TABLE WITH FULL HISTORY --- */
let chickenTimes = [];
let table = document.querySelector("table"); 
let rows = table.querySelectorAll("tr");

rows.forEach((row,index)=>{
    if(index===0) return;
    chickenTimes[index] = {
        loc1: row.cells[5].innerText ? row.cells[5].innerText.split(", ") : [],
        loc2: row.cells[6].innerText ? row.cells[6].innerText.split(", ") : []
    };
});

function updateTableTime(){
    const now = new Date();
    const hour = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2,'0');
    const timeStr = hour + ":" + minutes;

    rows.forEach((row,index)=>{
        if(index===0) return;
        let loc1Cell = row.cells[5];
        let loc2Cell = row.cells[6];

        if(hour >= 8 && hour <= 12){
            if(!chickenTimes[index].loc1.includes(timeStr)){
                chickenTimes[index].loc1.push(timeStr);
                loc1Cell.innerText = chickenTimes[index].loc1.join(", ");
            }
        } else if(hour >= 13 && hour <= 16){
            if(!chickenTimes[index].loc2.includes(timeStr)){
                chickenTimes[index].loc2.push(timeStr);
                loc2Cell.innerText = chickenTimes[index].loc2.join(", ");
            }
        }
    });
}

/* Update every 5 seconds */
setInterval(updateTableTime,5000);
updateTableTime();

/* CLEAR previous times every 3 minutes */
setInterval(()=>{
    rows.forEach((row,index)=>{
        if(index===0) return;
        row.cells[5].innerText = "";
        row.cells[6].innerText = "";
        chickenTimes[index].loc1 = [];
        chickenTimes[index].loc2 = [];
    });
},180000);
</script>

</body>
</html>