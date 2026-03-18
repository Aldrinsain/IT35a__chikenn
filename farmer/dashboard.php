<?php
session_start();
include("../db/config.php");

if(!isset($_SESSION['username'])){
    header("Location: ../login.php");
    exit();
}

/* DATA */
$total_chickens = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM chickens"))['total'];
$inside = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM chickens WHERE boundary_status='Inside Boundary'"))['total'];
$outside = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM chickens WHERE boundary_status='Outside Boundary'"))['total'];

/* ZONE DATA */
$zone_labels=[]; $zone_values=[];
$res=mysqli_query($conn,"SELECT current_zone,COUNT(*) as total FROM chickens GROUP BY current_zone");
while($r=mysqli_fetch_assoc($res)){
    $zone_labels[]=$r['current_zone'];
    $zone_values[]=$r['total'];
}

/* BOUNDARY DATA */
$boundary_labels=[]; $boundary_values=[];
$res=mysqli_query($conn,"SELECT boundary_status,COUNT(*) as total FROM chickens GROUP BY boundary_status");
while($r=mysqli_fetch_assoc($res)){
    $boundary_labels[]=$r['boundary_status'];
    $boundary_values[]=$r['total'];
}

/* FETCH CHICKENS */
$chickens=[];
$res=mysqli_query($conn,"SELECT * FROM chickens");
while($r=mysqli_fetch_assoc($res)){
    $chickens[]=$r;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<meta charset="UTF-8">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6f8;
    margin: 0;
    color: #333;
}

.farmer-name {
    text-align:center;
    font-size:28px;
    font-weight:700;
    color:#28a745;
    margin-top:20px;
}

/* STATISTICS CARDS */
.cards {
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    justify-content:center;
    margin:15px auto 30px auto;
    max-width:1200px;
}
.card {
    background:white;
    padding:25px 30px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.15);
    text-align:center;
    min-width:150px;
    flex:1;
    transition: transform 0.2s;
}
.card:hover { transform: scale(1.05); }
.card h3 {
    margin:0;
    font-size:28px;
    color:#28a745;
    font-weight:700;
}
.card p {
    margin:10px 0 0 0;
    font-size:18px;
    font-weight:500;
}

.container {
    display:flex;
    gap:20px;
    max-width:1300px;
    margin:0 auto;
    padding:20px;
    align-items:flex-start;
}
.left { flex:2; }
.right { flex:1; display:flex; flex-direction:column; gap:20px; }

.box {
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 3px 10px rgba(0,0,0,0.1);
}
#map { width:100%; height:600px; border-radius:12px; }

.charts-row {
    display:flex;
    flex-direction:column;
    gap:20px;
    text-align:center;
}
.charts-row .box { width:100%; height:250px; }
.charts-row canvas { width:100% !important; height:180px !important; }

table {
    width:90%;
    margin:40px auto;
    border-collapse:collapse;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 3px 10px rgba(0,0,0,0.1);
}
th, td { padding:12px 15px; text-align:center; }
th { background-color:#28a745; color:#fff; font-weight:600; text-transform:uppercase; }
tr:nth-child(even) { background-color:#f9f9f9; }
tr:hover { background-color:#e6f4ea; }
td { color:#555; }

.logout-btn {
    display:block;
    width:150px;
    margin:30px auto 50px auto;
    padding:10px 15px;
    background:#ff4d4f;
    color:white;
    border:none;
    border-radius:8px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:background 0.2s;
}
.logout-btn:hover { background:#e04446; }

h2, h3, h4 { margin:0 0 10px 0; }
h2 { text-align:center; margin-top:40px; color:#28a745; }

@media(max-width:1000px){
    .container { flex-direction:column; }
    #map { height:400px; }
    .card { min-width:120px; padding:15px; }
    .card h3 { font-size:24px; }
    .card p { font-size:16px; }
}
</style>
</head>
<body>

<div class="farmer-name"><?php echo $_SESSION['username']; ?> - Farmer</div>

<div class="cards">
    <div class="card">
        <h3><?php echo $total_chickens; ?></h3>
        <p>Total Chickens</p>
    </div>
    <div class="card">
        <h3><?php echo $inside; ?></h3>
        <p>Inside Boundary</p>
    </div>
    <div class="card">
        <h3><?php echo $outside; ?></h3>
        <p>Outside Boundary</p>
    </div>
</div>

<div class="container">
    <div class="left">
        <div class="box">
            <h3>MAP</h3>
            <div id="map"></div>
        </div>
    </div>
    <div class="right">
        <h3 style="text-align:center;">Monitoring Charts</h3>
        <div class="charts-row">
            <div class="box">
                <h4>Boundary Monitoring</h4>
                <canvas id="boundaryChart"></canvas>
            </div>
            <div class="box">
                <h4>Grazing Zone</h4>
                <canvas id="grazingChart"></canvas>
            </div>
        </div>
    </div>
</div>

<h2>Chicken Monitoring</h2>
<table>
    <tr>
        <th>Tag</th>
        <th>Breed</th>
        <th>Zone</th>
        <th>Boundary</th>
        <th>Grazing</th>
        <th>L1</th>
        <th>L2</th>
    </tr>
    <?php
    foreach($chickens as $c){
        $color = ($c['boundary_status']=="Inside Boundary")?"green":"red";
        echo "<tr>
            <td>{$c['tag_number']}</td>
            <td>{$c['breed']}</td>
            <td>{$c['current_zone']}</td>
            <td style='color:$color;font-weight:bold'>{$c['boundary_status']}</td>
            <td>{$c['grazing_time']}</td>
            <td>{$c['location1_time']}</td>
            <td>{$c['location2_time']}</td>
        </tr>";
    }
    ?>
</table>

<form action="../logout.php" method="post">
    <button type="submit" class="logout-btn">Log Out</button>
</form>

<script>
/* MAP */
const map = L.map('map').setView([8.3697,124.8644],15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
setTimeout(() => { map.invalidateSize(); }, 200);

/* BOUNDARY POLYGON */
const boundaryCoords = [
    [8.36995,124.86223],
    [8.37040,124.86313],
    [8.36960,124.86383],
    [8.36890,124.86323],
    [8.36920,124.86213]
];
const boundary = L.polygon(boundaryCoords, { color:'red' }).addTo(map);

/* MARKERS INSIDE & OUTSIDE POLYGON */
let markers = [];
const chickens = <?php echo json_encode($chickens); ?>;

// Function to generate random point inside polygon bounds
function randomPointInPolygon(bounds){
    const latMin = bounds.getSouthWest().lat;
    const latMax = bounds.getNorthEast().lat;
    const lngMin = bounds.getSouthWest().lng;
    const lngMax = bounds.getNorthEast().lng;
    return [latMin + Math.random()*(latMax-latMin), lngMin + Math.random()*(lngMax-lngMin)];
}

// Function to check if inside polygon
function isInsidePolygon(latlng, polygon){
    return leafletPip.pointInLayer([latlng[1], latlng[0]], polygon).length > 0;
}

// Generate markers
chickens.forEach(() => {
    let insidePolygon = Math.random() < 0.5; // 50% chance
    let pos;
    const bounds = boundary.getBounds();

    if(insidePolygon){
        // Generate inside
        do { pos = randomPointInPolygon(bounds); }
        while(!boundary.getBounds().contains(pos));
    } else {
        // Generate outside (slightly offset)
        pos = [bounds.getSouthWest().lat + Math.random()*(bounds.getNorthEast().lat-bounds.getSouthWest().lat + 0.002),
               bounds.getSouthWest().lng + Math.random()*(bounds.getNorthEast().lng-bounds.getSouthWest().lng + 0.002)];
        if(boundary.getBounds().contains(pos)) pos[0] += 0.002; // push outside if accidentally inside
    }

    let marker = L.marker(pos).addTo(map);
    markers.push(marker);
});

// Update markers dynamically
function updateMarkers(){
    markers.forEach(m=>{
        const bounds = boundary.getBounds();
        let insidePolygon = Math.random()<0.5;
        let pos;
        if(insidePolygon){
            do{ pos = randomPointInPolygon(bounds); } while(!bounds.contains(pos));
        }else{
            pos = [bounds.getSouthWest().lat + Math.random()*(bounds.getNorthEast().lat-bounds.getSouthWest().lat + 0.002),
                   bounds.getSouthWest().lng + Math.random()*(bounds.getNorthEast().lng-bounds.getSouthWest().lng + 0.002)];
            if(bounds.contains(pos)) pos[0]+=0.002;
        }
        m.setLatLng(pos);
        let icon = bounds.contains(pos) ? 'https://maps.google.com/mapfiles/ms/icons/green-dot.png' : 'https://maps.google.com/mapfiles/ms/icons/red-dot.png';
        m.setIcon(new L.Icon({ iconUrl: icon, iconSize: [32,32] }));
    });
}
setInterval(updateMarkers,5000);

/* CHARTS */
const grazingColors = ['#ff4d4f','#ffa500','#1890ff'];

new Chart(document.getElementById("grazingChart"), {
    type:"bar",
    data:{
        labels: <?php echo json_encode($zone_labels); ?>,
        datasets:[{
            data: <?php echo json_encode($zone_values); ?>,
            backgroundColor: <?php 
                $colors=['#ff4d4f','#ffa500','#1890ff'];
                $bgColors=[];
                for($i=0;$i<count($zone_labels);$i++){ $bgColors[]=$colors[$i % count($colors)]; }
                echo json_encode($bgColors);
            ?>
        }]
    },
    options:{responsive:true,plugins:{legend:{display:false}}}
});

new Chart(document.getElementById("boundaryChart"),{
    type:"doughnut",
    data:{labels: <?php echo json_encode($boundary_labels); ?>, datasets:[{data: <?php echo json_encode($boundary_values); ?>, backgroundColor:['green','red']}]},
    options:{responsive:true}
});
</script>

<!-- leaflet-pip library for polygon point check -->
<script src="https://unpkg.com/leaflet-pip/leaflet-pip.min.js"></script>
</body>
</html>