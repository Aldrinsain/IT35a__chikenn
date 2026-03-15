function updateTableTime(){
    const now = new Date();
    const hour = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2,'0');
    const timeStr = hour + ":" + minutes;

    rows.forEach((row,index)=>{
        if(index===0) return;
        let tag = row.cells[0].innerText;

        let grazingCell = row.cells[4];
        let loc1Cell = row.cells[5];
        let loc2Cell = row.cells[6];

        let data = { tag_number: tag, grazing: null, loc1: null, loc2: null };

        if(hour >= 8 && hour <= 12){
            if(!chickenTimes[index].grazing.includes(timeStr)){
                chickenTimes[index].grazing.push(timeStr);
                grazingCell.innerText = chickenTimes[index].grazing.join(", ");
                data.grazing = timeStr;
            }
        }
        else if(hour >= 13 && hour <= 16){
            if(!chickenTimes[index].loc1.includes(timeStr)){
                chickenTimes[index].loc1.push(timeStr);
                loc1Cell.innerText = chickenTimes[index].loc1.join(", ");
                data.loc1 = timeStr;
            }
        }
        else{
            if(!chickenTimes[index].loc2.includes(timeStr)){
                chickenTimes[index].loc2.push(timeStr);
                loc2Cell.innerText = chickenTimes[index].loc2.join(", ");
                data.loc2 = timeStr;
            }
        }

        // Send AJAX request to save in DB
        fetch('save_time.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    });
}