<?php
session_start();
include("../db/config.php");

// Login check
if(!isset($_SESSION['username'])){
    header("Location: ../login.php");
    exit();
}

// Handle AJAX request to update time
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tag_number'], $_POST['log_type'], $_POST['time'])){
    $tag = mysqli_real_escape_string($conn, $_POST['tag_number']);
    $log = $_POST['log_type'] === 'loc1' ? 'location1_time' : 'location2_time';
    $time = mysqli_real_escape_string($conn, $_POST['time']);

    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT $log FROM chickens WHERE tag_number='$tag'"));
    $existing = $row[$log] ?: '';
    $new = $existing === '' ? $time : $existing . ', ' . $time;

    mysqli_query($conn, "UPDATE chickens SET $log='$new' WHERE tag_number='$tag'");
    echo json_encode(['status'=>'success','new'=>$new]);
    exit;
}

// Fetch all chickens
$chickens = [];
$result = mysqli_query($conn, "SELECT tag_number, breed, current_zone, boundary_status, grazing_time, location1_time, location2_time FROM chickens");
while($row = mysqli_fetch_assoc($result)){
    $chickens[$row['tag_number']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chicken Monitoring</title>
<style>
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        background:#f4f6f8; 
        margin:0; 
        padding:40px; 
        color:#333;
    }

    /* Header */
    .header {
        display:flex; 
        align-items:center; 
        justify-content:space-between; 
        margin-bottom:30px;
    }
    .header h1 {
        color:#28a745;
        font-size:28px;
        margin:0;
    }
    .back-btn {
        padding:10px 20px;
        background:#28a745;
        color:white;
        text-decoration:none;
        border-radius:8px;
        font-weight:bold;
        transition:0.3s;
        box-shadow:0 4px 10px rgba(0,0,0,0.1);
    }
    .back-btn:hover {
        background:#218838;
        box-shadow:0 6px 12px rgba(0,0,0,0.15);
    }

    /* Table */
    table {
        width:100%;
        border-collapse:collapse;
        background:white;
        box-shadow:0 4px 15px rgba(0,0,0,0.05);
        border-radius:10px;
        overflow:hidden;
    }
    th, td {
        padding:12px 15px;
        text-align:center;
    }
    th {
        background:#28a745;
        color:white;
        text-transform:uppercase;
        font-weight:600;
        letter-spacing:0.5px;
    }
    tr {
        transition:0.2s;
    }
    tr:hover {
        background:#f1f9f5;
    }
    td {
        color:#555;
        font-weight:500;
    }

    /* Responsive for small screens */
    @media(max-width:800px){
        table, thead, tbody, th, td, tr { display:block; }
        tr { margin-bottom:15px; }
        th { display:none; }
        td { text-align:right; padding-left:50%; position:relative; }
        td::before {
            content: attr(data-label);
            position:absolute;
            left:15px;
            width:45%;
            text-align:left;
            font-weight:bold;
            color:#28a745;
        }
    }
</style>
</head>
<body>

<div class="header">
    <h1>Chicken Monitoring</h1>
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
</div>

<table id="chickenTable">
<tr>
    <th>Tag Number</th>
    <th>Breed</th>
    <th>Zone</th>
    <th>Boundary</th>
    <th>Grazing Time</th>
    <th>Location 1 Time</th>
    <th>Location 2 Time</th>
</tr>

<?php
foreach($chickens as $tag => $chicken){
    $grazing = $chicken['grazing_time'] ?? '-';
    $loc1 = $chicken['location1_time'] ?? '-';
    $loc2 = $chicken['location2_time'] ?? '-';

    echo "<tr data-tag='".$chicken['tag_number']."'>
        <td data-label='Tag Number'>".htmlspecialchars($chicken['tag_number'])."</td>
        <td data-label='Breed'>".htmlspecialchars($chicken['breed'])."</td>
        <td data-label='Zone'>".htmlspecialchars($chicken['current_zone'])."</td>
        <td data-label='Boundary'>".htmlspecialchars($chicken['boundary_status'])."</td>
        <td data-label='Grazing Time' class='grazing'>".htmlspecialchars($grazing)."</td>
        <td data-label='Location 1 Time' class='loc1'>".htmlspecialchars($loc1)."</td>
        <td data-label='Location 2 Time' class='loc2'>".htmlspecialchars($loc2)."</td>
    </tr>";
}
?>
</table>

<script>
// AUTO UPDATE Location1 & Location2 TIMES & SAVE TO DB
function updateTimes(){
    const now = new Date();
    const hour = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2,'0');
    const timeStr = hour + ":" + minutes;

    const table = document.getElementById('chickenTable');
    for(let i=1; i<table.rows.length; i++){
        const row = table.rows[i];
        const tag = row.dataset.tag;
        let logType, cell;

        if(hour >= 8 && hour <= 12){
            logType = 'loc1';
            cell = row.querySelector('.loc1');
        } else if(hour >= 13 && hour <= 16){
            logType = 'loc2';
            cell = row.querySelector('.loc2');
        } else continue;

        if(!cell.innerText.includes(timeStr)){
            cell.innerText = cell.innerText === '-' ? timeStr : cell.innerText + ", " + timeStr;

            const formData = new FormData();
            formData.append('tag_number', tag);
            formData.append('log_type', logType);
            formData.append('time', timeStr);

            fetch(window.location.href, { method:'POST', body:formData });
        }
    }
}

// Update every 5 seconds
setInterval(updateTimes,5000);
updateTimes();

// Optional: CLEAR Location1 & Location2 every 3 minutes for live dashboard view
setInterval(()=>{
    const table = document.getElementById('chickenTable');
    for(let i=1; i<table.rows.length; i++){
        table.rows[i].querySelector('.loc1').innerText = '';
        table.rows[i].querySelector('.loc2').innerText = '';
    }
},180000);
</script>

</body>
</html>