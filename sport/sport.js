const scheduleTable = document.getElementById("schedule");
const local_url = "http://localhost:8000/sport/soccerSchedule";
const data_url = "https://csds285-project1.herokuapp.com/index.php/sport/soccerSchedule";
const vm_url = "http://eecslab-22.case.edu/~hcn6/csds285_project1/backend/index.php/sport/soccerSchedule";
fetch(data_url)
    .then((response) => response.json())
    .then((data) => {
        scheduleTable.innerHTML = "";
        soccer_data = data.football
        soccer_data.forEach((match) => {
            const row = document.createElement("tr");
            // console.log(match.date)
            league = match.tournament
            date = match.start.split(" ")[0]
            start = match.start.split(" ")[1]
            home = match.match.split("vs")[0].trim()
            away = match.match.split("vs")[1].trim()

            row.innerHTML = `
                  <td>${league}</td>
                  <td>${date}</td>
                  <td>${start}</td>
                  <td>${home}</td>
                  <td>${away}</td>
                `;
            scheduleTable.appendChild(row);
        });
    })
    .catch((error) => {
        console.log(error)
        scheduleTable.innerHTML = `
                <tr>
                  <td colspan="4">Error loading schedule.</td>
                </tr>
              `;
    });